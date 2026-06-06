<?php

namespace App\Http\Requests;

use App\Enums\TableShape;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates reception table payloads. Authorization is handled upstream by the
 * `permission:seating,write` route middleware.
 */
class SeatingTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'shape' => ['required', Rule::enum(TableShape::class)],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'position_x' => ['nullable', 'integer', 'min:0', 'max:100'],
            'position_y' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
