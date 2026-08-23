<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidSenegalPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'password' => ['required', 'string'],
        ];
    }
}
