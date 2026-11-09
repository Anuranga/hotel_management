<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize guest inputs and special instructions prior to validation.
     */
    protected function prepareForValidation(): void
    {
        $items = collect((array) $this->input('items', []))
            ->map(function (mixed $item): array {
                if (! is_array($item)) {
                    return [];
                }

                return [
                    'menu_item_id' => $item['menu_item_id'] ?? null,
                    'quantity' => $item['quantity'] ?? null,
                    'notes' => isset($item['notes']) && $item['notes'] !== ''
                        ? trim(strip_tags((string) $item['notes']))
                        : null,
                ];
            })
            ->all();

        $this->merge([
            'customer_name' => trim(strip_tags((string) $this->input('customer_name', ''))),
            'customer_phone' => preg_replace('/\s+/', '', strip_tags((string) $this->input('customer_phone', ''))),
            'items' => $items,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'restaurant_table_id' => ['required', 'integer', 'exists:restaurant_tables,id'],
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:20', 'regex:/^(?:0|94|\+94)?7[0-9]{8}$/'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_phone.regex' => 'Please enter a valid Sri Lankan mobile number (e.g., 0771234567 or +94771234567).',
            'items.required' => 'Please select at least one menu item before placing your order.',
            'items.*.quantity.min' => 'Each selected item must have a quantity of at least 1.',
        ];
    }
}
