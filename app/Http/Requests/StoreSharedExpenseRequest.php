<?php

namespace App\Http\Requests;

use App\Models\User;
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
            'participants.*.user_id' => ['required', 'integer', 'exists:users,id'],
            'participants.*.amount_owed' => ['required', 'numeric', 'gt:0'],
            'participants.*.daily_commitment' => ['nullable', 'numeric', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'participants.*.user_id.required' => 'Elige a la persona de la lista de resultados antes de guardar — no se puede dejar una fila sin nadie seleccionado.',
            'participants.*.user_id.exists' => 'Esa persona ya no existe.',
            'participants.*.amount_owed.required' => 'Falta el monto que le corresponde a esta persona.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $myShare = (float) ($this->input('my_share') ?: 0);
            $total = (float) $this->input('total_amount');
            $participantsInput = collect($this->input('participants', []));

            $participantsTotal = $participantsInput->sum(fn ($participant) => (float) ($participant['amount_owed'] ?? 0));

            if (round($myShare + $participantsTotal, 2) !== round($total, 2)) {
                $validator->errors()->add(
                    'total_amount',
                    'Tu parte más lo que deben los demás (S/ '.number_format($myShare + $participantsTotal, 2).') debe sumar el total (S/ '.number_format($total, 2).').'
                );
            }

            $userIds = $participantsInput->pluck('user_id')->filter();

            if ($userIds->contains((string) $this->user()->id) || $userIds->contains($this->user()->id)) {
                $validator->errors()->add('participants', 'No puedes agregarte a ti mismo como participante.');
            }

            if ($userIds->duplicates()->isNotEmpty()) {
                $validator->errors()->add('participants', 'No puedes agregar a la misma persona dos veces.');
            }

            if ($userIds->isNotEmpty()) {
                $validCount = User::whereIn('id', $userIds)->where('role', '!=', 'admin')->count();

                if ($validCount !== $userIds->unique()->count()) {
                    $validator->errors()->add('participants', 'Uno de los participantes seleccionados no es una cuenta válida.');
                }
            }
        });
    }
}
