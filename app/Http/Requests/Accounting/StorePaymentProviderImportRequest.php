<?php

namespace App\Http\Requests\Accounting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentProviderImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'payment_settlement_id' => ['required', 'integer'],
            'import_number' => ['required', 'string', 'max:80'],
            'provider' => ['required', 'string', 'max:80'],
            'method' => ['required', Rule::in(['card', 'transfer', 'qris'])],
            'settlement_date' => ['nullable', 'date'],
            'csv_content' => ['required_without:rows', 'nullable', 'string'],
            'rows' => ['required_without:csv_content', 'nullable', 'array'],
            'rows.*.reference' => ['required_with:rows', 'string', 'max:120'],
            'rows.*.amount' => ['required_with:rows', 'numeric', 'min:0'],
            'rows.*.fee_amount' => ['nullable', 'numeric', 'min:0'],
            'rows.*.settled_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
