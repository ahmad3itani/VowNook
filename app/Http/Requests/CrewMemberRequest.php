<?php

namespace App\Http\Requests;

use App\Enums\CrewRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates wedding-party / crew payloads. Authorization is handled upstream by
 * the `permission:crew,write` route middleware.
 */
class CrewMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'role' => ['required', Rule::enum(CrewRole::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
