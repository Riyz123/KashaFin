<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreLoanAmortizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.gt' => 'El monto debe ser mayor a cero.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $loan = $this->route('loan');
            $amount = (float) $this->input('amount');

            if ($loan && $amount > $loan->remaining_amount) {
                $validator->errors()->add(
                    'amount',
                    'No puedes pagar más de lo que falta de la deuda (S/ '.number_format($loan->remaining_amount, 2).').'
                );
            }
        });
    }
}
