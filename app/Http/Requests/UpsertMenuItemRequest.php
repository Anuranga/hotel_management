<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertMenuItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Sanitize menu item inputs prior to validation.
     */
    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        $this->merge([
            'name' => trim(strip_tags((string) $this->input('name', ''))),
            'description' => $description !== null && $description !== ''
                ? trim(strip_tags((string) $description))
                : null,
            'is_available' => $this->boolean('is_available', true),
            'is_vat_exempt' => $this->boolean('is_vat_exempt', false),
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'cost_price' => ['required', 'numeric', 'min:0', 'max:9999999.99'],
            'is_available' => ['required', 'boolean'],
            'is_vat_exempt' => ['required', 'boolean'],
            'current_stock' => ['required', 'integer', 'min:-1', 'max:100000'],
            'image_path' => ['nullable', 'string', 'max:500'],
        ];
    }
}
