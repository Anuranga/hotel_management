<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AppendOrderItemsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Sanitize item notes prior to validation.
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Please select at least one add-on item.',
            'items.*.quantity.min' => 'Add-on item quantities must be greater than zero.',
        ];
    }
}
