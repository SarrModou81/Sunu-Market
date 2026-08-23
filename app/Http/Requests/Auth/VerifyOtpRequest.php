<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidSenegalPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Connexion sans mot de passe : vérifie le code OTP (motif "login") et connecte l'utilisateur.
 */
class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', new ValidSenegalPhone],
            'code' => ['required', 'string', 'size:'.config('otp.length')],
        ];
    }
}
