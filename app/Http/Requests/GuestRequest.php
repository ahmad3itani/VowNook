<?php

namespace App\Http\Requests;

use App\Enums\AgeGroup;
use App\Enums\GuestSide;
use App\Enums\RsvpStatus;
use App\Support\CurrentWedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates guest create/update payloads. Authorization is handled upstream
 * by the `permission:guests,write` route middleware.
 */
class GuestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $weddingId = app(CurrentWedding::class)->id();

        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'side' => ['required', Rule::enum(GuestSide::class)],
            'age_group' => ['required', Rule::enum(AgeGroup::class)],
            'is_plus_one' => ['boolean'],
            'rsvp_status' => ['required', Rule::enum(RsvpStatus::class)],
            'meal_choice' => ['nullable', 'string', 'max:120'],
            'appetizer_choice' => ['nullable', 'string', 'max:120'],
            'dessert_choice' => ['nullable', 'string', 'max:120'],
            'dietary_notes' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'group_id' => [
                'nullable',
                Rule::exists('guest_groups', 'id')->where('wedding_id', $weddingId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_plus_one' => $this->boolean('is_plus_one'),
        ]);
    }
}
