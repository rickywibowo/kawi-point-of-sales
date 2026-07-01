<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class PostStockAdjustmentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer'],
            'adjustment_number' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_of_measure_id' => ['nullable', 'integer'],
            'items.*.quantity_delta' => ['required', 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
