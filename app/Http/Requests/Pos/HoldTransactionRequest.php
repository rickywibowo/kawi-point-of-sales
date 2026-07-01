<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class HoldTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cashier_shift_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'hold_number' => ['required', 'string', 'max:80'],
            'payload' => ['required', 'array'],
        ];
    }
}
