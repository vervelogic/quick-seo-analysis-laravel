<?php

namespace App\Http\Requests;

use App\Services\Scanner\PublicUrlGuard;
use App\Services\Scanner\UrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreScanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('url')) {
            $normalized = app(UrlNormalizer::class)->normalize($this->string('url')->toString());

            $this->merge([
                'url' => $normalized,
                'normalized_url' => $normalized,
            ]);
        }
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
}
