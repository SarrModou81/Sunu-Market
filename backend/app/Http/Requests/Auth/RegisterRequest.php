<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidSenegalPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:profiles,email'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }
}
