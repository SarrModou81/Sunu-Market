<?php

namespace App\Http\Requests\Auth;

use App\Rules\ValidSenegalPhone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ChangePhoneRequest extends FormRequest
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
            'new_phone' => ['required', 'string', new ValidSenegalPhone],
            'code' => ['required', 'string', 'size:'.config('otp.length')],
        ];
    }
}
