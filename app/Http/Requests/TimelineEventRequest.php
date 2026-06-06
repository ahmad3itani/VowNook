<?php

namespace App\Http\Requests;

use App\Enums\EventType;
use App\Support\CurrentWedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates timeline event payloads. The optional vendor must belong to the
 * active wedding. Authorization is handled upstream by the
 * `permission:timeline,write` route middleware.
 */
class TimelineEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $weddingId = app(CurrentWedding::class)->id();

        return [
            'title' => ['required', 'string', 'max:200'],
            'type' => ['required', Rule::enum(EventType::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'vendor_id' => [
                'nullable',
                Rule::exists('vendors', 'id')->where('wedding_id', $weddingId),
            ],
        ];
    }
}
