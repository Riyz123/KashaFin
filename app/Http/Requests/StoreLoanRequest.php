<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'person_name' => ['required', 'string', 'max:255'],
            'person_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'direction' => ['required', 'in:prestado,recibido'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'El monto debe ser mayor a cero.',
        ];
    }
}
