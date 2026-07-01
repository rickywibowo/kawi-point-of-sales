<?php

namespace App\Http\Requests\MasterData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'integer'],
            'unit_of_measure_id' => ['nullable', 'integer'],
            'tax_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['goods', 'food', 'beverage', 'service'])],
            'sku' => ['nullable', 'string', 'max:80'],
            'barcode' => ['nullable', 'string', 'max:80'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'track_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'branch_prices' => ['nullable', 'array'],
            'branch_prices.*.branch_id' => ['required', 'integer'],
            'branch_prices.*.price' => ['required', 'numeric', 'min:0'],
            'branch_prices.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
