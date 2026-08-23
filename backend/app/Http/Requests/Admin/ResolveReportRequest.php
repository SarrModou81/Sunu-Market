<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isModerator() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['reviewed', 'dismissed', 'action_taken'])],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
            // Si vrai et que le signalement porte sur une annonce, bloque également l'annonce.
            'block_reportable' => ['sometimes', 'boolean'],
        ];
    }
}
