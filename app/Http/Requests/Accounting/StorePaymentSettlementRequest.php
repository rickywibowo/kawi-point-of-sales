<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentSettlementRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'settlement_number' => ['required', 'string', 'max:80'],
            'method' => ['required', Rule::in(['cash', 'card', 'transfer', 'qris'])],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'reported_amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
