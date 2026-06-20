<?php

namespace App\Http\Requests;

use App\Services\Scanner\UrlNormalizer;
use Illuminate\Foundation\Http\FormRequest;

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
}
