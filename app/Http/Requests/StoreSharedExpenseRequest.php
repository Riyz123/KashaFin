<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSharedExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'gt:0'],
            'my_share' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*.name' => ['required', 'string', 'max:255'],
            'participants.*.amount_owed' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $myShare = (float) ($this->input('my_share') ?: 0);
            $total = (float) $this->input('total_amount');
            $participantsTotal = collect($this->input('participants', []))
                ->sum(fn ($participant) => (float) ($participant['amount_owed'] ?? 0));

            if (round($myShare + $participantsTotal, 2) !== round($total, 2)) {
                $validator->errors()->add(
                    'total_amount',
                    'Tu parte más lo que deben los demás (S/ '.number_format($myShare + $participantsTotal, 2).') debe sumar el total (S/ '.number_format($total, 2).').'
                );
            }
        });
    }
}
