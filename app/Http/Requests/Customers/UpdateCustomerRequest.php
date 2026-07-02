<?php

namespace App\Http\Requests\Customers;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:160'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'email' => ['sometimes', 'nullable', 'email', 'max:160'],
            'address' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'receivable_balance' => ['sometimes', 'numeric', 'min:0'],
            'loyalty_points' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
