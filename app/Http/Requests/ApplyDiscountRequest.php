<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApplyDiscountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'manager_pin' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Enforce manager/admin PIN requirement when discount exceeds 10%.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
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
