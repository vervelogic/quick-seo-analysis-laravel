<?php

namespace App\Http\Requests;

use App\Services\Scanner\PublicUrlGuard;
use App\Services\Scanner\UrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreKeywordFocusAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
            'target_keywords' => ['required', 'array', 'min:1', 'max:20'],
            'target_keywords.*' => ['string', 'max:120'],
            'scan_input_had_scheme' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('url')) {
            $rawUrl = trim($this->string('url')->toString());
            $inputHadScheme = (bool) preg_match('/^https?:\/\//i', $rawUrl);
            $normalized = app(UrlNormalizer::class)->normalize($rawUrl);

            $this->merge([
                'url' => $normalized,
                'original_url' => $rawUrl,
                'normalized_url' => $normalized,
                'scan_input_had_scheme' => $inputHadScheme,
            ]);
        }

        $this->merge([
            'target_keywords' => $this->normalizeKeywords((string) $this->input('target_keywords', '')),
        ]);
    }

    public function messages(): array
    {
        return [
            'target_keywords.required' => 'Enter at least one target keyword.',
            'target_keywords.min' => 'Enter at least one target keyword.',
            'target_keywords.max' => 'Enter up to 20 target keywords for now.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $url = (string) $this->input('normalized_url');

                if ($url !== '' && ! app(PublicUrlGuard::class)->isAllowed($url)) {
                    $validator->errors()->add('url', 'Enter a public website URL. Private IPs, localhost, credentials, and custom ports cannot be scanned.');
                }
            },
        ];
    }

    private function normalizeKeywords(string $value): array
    {
        $keywords = [];

        foreach (preg_split('/\r\n|\r|\n/', $value) ?: [] as $line) {
            $keyword = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
            $key = strtolower($keyword);

            if ($keyword !== '' && ! isset($keywords[$key])) {
                $keywords[$key] = $keyword;
            }
        }

        return array_values($keywords);
    }
}
