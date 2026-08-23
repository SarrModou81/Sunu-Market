<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreReportRequest extends FormRequest
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
            // Whitelist stricte : le type signalé doit être un alias de morph map connu,
            // jamais un nom de classe PHP arbitraire fourni par le client.
            'reportable_type' => ['required', Rule::in(['product', 'user'])],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', Rule::in([
                'produit_frauduleux', 'faux_produit', 'prix_trompeur',
                'vendeur_suspect', 'annonce_incorrecte', 'harcelement', 'arnaque', 'autre',
            ])],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('reportable_type');
            $id = $this->input('reportable_id');
            $modelClass = $type === 'product' ? Product::class : User::class;

            if (! $modelClass::whereKey($id)->exists()) {
                $validator->errors()->add('reportable_id', "L'élément signalé est introuvable.");
            } elseif ($type === 'user' && (int) $id === $this->user()->id) {
                $validator->errors()->add('reportable_id', 'Vous ne pouvez pas vous signaler vous-même.');
            }
        });
    }
}
