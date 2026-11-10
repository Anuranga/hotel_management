<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SettleOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Sanitize extra charge label before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('extra_charge_label')) {
            $this->merge([
                'extra_charge_label' => trim(strip_tags((string) $this->input('extra_charge_label', ''))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method' => ['required', 'string', Rule::in(['cash', 'card', 'online'])],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_charge_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'extra_charge_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999.99'],
            'extra_charge_label' => ['nullable', 'string', 'max:100'],
            'manager_pin' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Enforce manager/admin PIN when settling with a discount exceeding 10%.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('discount_percentage')) {
                    return;
                }

                $maxStandard = (float) config('hotel.max_cashier_discount_percentage', 10.00);
                $discount = (float) $this->input('discount_percentage', 0);

                if ($discount > $maxStandard && trim((string) $this->input('manager_pin', '')) === '') {
                    $validator->errors()->add(
                        'manager_pin',
                        "Discounts exceeding {$maxStandard}% require a Manager or Admin PIN code."
                    );
                }
            },
        ];
    }
}
