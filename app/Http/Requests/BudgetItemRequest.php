<?php

namespace App\Http\Requests;

use App\Support\CurrentWedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates budget line-item payloads. Amounts arrive as decimal dollars and
 * are converted to integer cents by the controller. Authorization is handled
 * upstream by the `permission:budget,write` route middleware.
 */
class BudgetItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $weddingId = app(CurrentWedding::class)->id();

        return [
            'name' => ['required', 'string', 'max:160'],
            'estimated_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'actual_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'paid_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'category_id' => [
                'nullable',
                Rule::exists('budget_categories', 'id')->where('wedding_id', $weddingId),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paid_amount' => $this->input('paid_amount', 0),
        ]);
    }
}
