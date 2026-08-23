<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidSenegalPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
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
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
