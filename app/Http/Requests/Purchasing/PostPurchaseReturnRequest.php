<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class PostPurchaseReturnRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer'],
            'goods_receipt_id' => ['required', 'integer'],
            'return_number' => ['required', 'string', 'max:80'],
            'return_date' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.goods_receipt_item_id' => ['nullable', 'integer'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_of_measure_id' => ['nullable', 'integer'],
            'items.*.quantity_returned' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
