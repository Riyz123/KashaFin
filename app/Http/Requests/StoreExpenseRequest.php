<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'type' => ['required', 'in:fijo,variable'],
            'frequency' => ['required_if:type,fijo', 'nullable', 'in:semanal,quincenal,mensual'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'El monto debe ser mayor a cero.',
            'frequency.required_if' => 'Selecciona una frecuencia para los gastos fijos.',
        ];
    }
}
