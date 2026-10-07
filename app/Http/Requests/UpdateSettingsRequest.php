<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'in:PEN,USD'],
            'theme' => ['required', 'in:light,dark'],
            'language' => ['required', 'in:es,en,qu'],
            'week_start_day' => ['required', 'integer', 'between:0,6'],
            'liquidity_threshold' => ['required', 'numeric', 'min:0'],
            'notify_low_liquidity_by_email' => ['nullable', 'boolean'],
            'starting_balance' => ['required', 'numeric'],
        ];
    }
}
