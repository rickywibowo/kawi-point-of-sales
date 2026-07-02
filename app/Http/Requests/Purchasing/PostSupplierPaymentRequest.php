<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class PostSupplierPaymentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_number' => ['required', 'string', 'max:80'],
            'payment_date' => ['nullable', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string', 'max:60'],
            'reference_number' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
