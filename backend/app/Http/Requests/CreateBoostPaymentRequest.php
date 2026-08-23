<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBoostPaymentRequest extends FormRequest
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
            'boost_plan_id' => ['required', 'integer', Rule::exists('boost_plans', 'id')->where('is_active', true)],
            'provider' => ['required', Rule::in([Payment::PROVIDER_WAVE, Payment::PROVIDER_ORANGE_MONEY])],
        ];
    }
}
