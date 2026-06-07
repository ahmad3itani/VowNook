<?php

namespace App\Http\Requests;

use App\Enums\SeatingElementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates floor-plan element payloads. Authorization is handled upstream by
 * the `permission:seating,write` route middleware.
 */
class SeatingElementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(SeatingElementType::class)],
            'label' => ['nullable', 'string', 'max:80'],
            'position_x' => ['required', 'integer', 'min:0', 'max:100'],
            'position_y' => ['required', 'integer', 'min:0', 'max:100'],
            'width' => ['required', 'integer', 'min:4', 'max:100'],
            'height' => ['required', 'integer', 'min:4', 'max:100'],
            'rotation' => ['nullable', 'integer', 'min:0', 'max:359'],
        ];
    }
}
