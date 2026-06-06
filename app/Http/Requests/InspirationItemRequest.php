<?php

namespace App\Http\Requests;

use App\Enums\InspirationCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates inspiration-board payloads. Authorization is handled upstream by the
 * `permission:inspiration,write` route middleware.
 */
class InspirationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'category' => ['required', Rule::enum(InspirationCategory::class)],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'link_url' => ['nullable', 'url', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
