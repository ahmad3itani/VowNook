<?php

namespace App\Http\Requests;

use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates vendor payloads. Cost/paid arrive as decimal dollars and are
 * converted to integer cents by the controller. Authorization is handled
 * upstream by the `permission:vendors,write` route middleware.
 */
class VendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::enum(VendorCategory::class)],
            'status' => ['required', Rule::enum(VendorStatus::class)],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'price_level' => ['nullable', 'integer', 'min:1', 'max:4'],
            'contact_name' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'cost_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'paid_amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'paid_amount' => $this->input('paid_amount', 0),
        ]);
    }
}
