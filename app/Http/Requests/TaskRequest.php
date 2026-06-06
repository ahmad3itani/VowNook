<?php

namespace App\Http\Requests;

use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Support\CurrentWedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates checklist task payloads. The optional assignee must be a member of
 * the active wedding. Authorization is handled upstream by the
 * `permission:checklist,write` route middleware.
 */
class TaskRequest extends FormRequest
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
            'category' => ['required', Rule::enum(TaskCategory::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'due_date' => ['nullable', 'date'],
            'is_complete' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'assigned_to' => [
                'nullable',
                Rule::exists('wedding_user', 'user_id')->where('wedding_id', $weddingId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_complete' => $this->boolean('is_complete'),
        ]);
    }
}
