<?php

namespace App\Http\Requests;

use App\Models\Neighborhood;
use App\Models\Product;
use App\Models\Subcategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'subcategory_id' => ['nullable', 'integer', 'exists:subcategories,id'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'condition' => ['required', Rule::in(['neuf', 'tres_bon', 'bon', 'moyen'])],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'neighborhood_id' => ['nullable', 'integer', 'exists:neighborhoods,id'],
            'delivery_available' => ['sometimes', 'boolean'],
            'is_draft' => ['sometimes', 'boolean'],
            'images' => ['sometimes', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('subcategory_id') && $this->filled('category_id')) {
                $belongs = Subcategory::where('id', $this->integer('subcategory_id'))
                    ->where('category_id', $this->integer('category_id'))
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('subcategory_id', 'La sous-catégorie ne correspond pas à la catégorie choisie.');
                }
            }

            if ($this->filled('neighborhood_id') && $this->filled('city_id')) {
                $belongs = Neighborhood::where('id', $this->integer('neighborhood_id'))
                    ->where('city_id', $this->integer('city_id'))
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('neighborhood_id', 'Le quartier ne correspond pas à la ville choisie.');
                }
            }

            if (! $this->boolean('is_draft') && count($this->file('images') ?? []) < 1) {
                $validator->errors()->add('images', 'Au moins une photo est requise pour publier une annonce (ou enregistrez-la en brouillon).');
            }

            if (! $this->boolean('is_draft')) {
                $user = $this->user();
                $limit = $user->sellerProfile?->activeProductsLimit();

                if ($limit !== null) {
                    $activeCount = Product::where('user_id', $user->id)->where('status', Product::STATUS_ACTIVE)->count();
                    if ($activeCount >= $limit) {
                        $validator->errors()->add(
                            'quota',
                            "Vous avez atteint la limite de {$limit} annonces actives du compte Standard. Passez au compte Pro pour publier sans limite."
                        );
                    }
                }
            }
        });
    }
}
