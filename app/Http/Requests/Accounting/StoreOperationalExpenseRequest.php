<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperationalExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'expense_number' => ['required', 'string', 'max:80'],
            'expense_date' => ['nullable', 'date'],
            'account_id' => ['required', 'integer'],
            'cash_account_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:120'],
            'payee' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'reference_number' => ['nullable', 'string', 'max:120'],
        ];
    }
}
