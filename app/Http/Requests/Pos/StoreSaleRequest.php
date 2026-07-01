<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cashier_shift_id' => ['required', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'sale_number' => ['required', 'string', 'max:80'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', Rule::in(['dine_in', 'takeaway', 'delivery'])],
            'service_charge_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_total' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.modifier_id' => ['required', 'integer'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', Rule::in(['cash', 'card', 'transfer', 'qris'])],
            'payments.*.amount' => ['required', 'numeric', 'gt:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
            'payments.*.metadata' => ['nullable', 'array'],
        ];
    }
}
