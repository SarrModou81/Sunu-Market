<?php

namespace App\Http\Requests;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreReviewRequest extends FormRequest
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
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var User $seller */
            $seller = $this->route('seller');
            $user = $this->user();

            if ($seller->id === $user->id) {
                $validator->errors()->add('seller', 'Vous ne pouvez pas vous évaluer vous-même.');

                return;
            }

            if ($user->reviewsGiven()->where('seller_id', $seller->id)->exists()) {
                $validator->errors()->add('seller', 'Vous avez déjà évalué ce vendeur.');

                return;
            }

            // Prévention des évaluations abusives : seuls les acheteurs ayant
            // effectivement contacté le vendeur peuvent laisser un avis.
            $hasInteracted = Conversation::where('seller_id', $seller->id)
                ->where('buyer_id', $user->id)
                ->exists();

            if (! $hasInteracted) {
                $validator->errors()->add('seller', "Vous devez avoir contacté ce vendeur avant de pouvoir l'évaluer.");
            }
        });
    }
}
