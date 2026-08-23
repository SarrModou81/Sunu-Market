<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\ValidSenegalPhone;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->input('purpose') === 'change_phone') {
            return $this->user() !== null;
        }

        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new ValidSenegalPhone],
            'purpose' => ['required', Rule::in(['register', 'login', 'reset_password', 'change_phone'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $phone = PhoneNumber::normalize((string) $this->input('phone'));
            if (! $phone) {
                return;
            }

            $exists = User::where('phone', $phone)->exists();

            match ($this->input('purpose')) {
                'register', 'change_phone' => $exists && $validator->errors()->add('phone', 'Ce numéro est déjà utilisé par un compte.'),
                'login', 'reset_password' => ! $exists && $validator->errors()->add('phone', "Aucun compte n'est associé à ce numéro."),
                default => null,
            };
        });
    }
}
