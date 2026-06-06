<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates wedding-website content. Authorization is handled upstream by the
 * `permission:website,write` route middleware.
 */
class WeddingWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_published' => ['boolean'],
            'headline' => ['nullable', 'string', 'max:200'],
            'welcome_message' => ['nullable', 'string', 'max:2000'],
            'our_story' => ['nullable', 'string', 'max:5000'],
            'venue_name' => ['nullable', 'string', 'max:200'],
            'venue_address' => ['nullable', 'string', 'max:255'],
            'ceremony_time' => ['nullable', 'string', 'max:100'],
            'dress_code' => ['nullable', 'string', 'max:100'],
            'hero_image_url' => ['nullable', 'url', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
        ]);
    }
}
