<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'string', 'max:5000'],
            'category_id' => ['sometimes', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'price' => ['sometimes', 'integer', 'min:0', 'max:999999999'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:10000'],
            'condition' => ['sometimes', Rule::in(['neuf', 'tres_bon', 'bon', 'moyen'])],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
            'delivery_available' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['ACTIVE', 'VENDUE', 'ARCHIVEE'])],
            'new_images' => ['sometimes', 'array', 'max:8'],
            'new_images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
