<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:160'],
            'yield_quantity' => ['nullable', 'numeric', 'gt:0'],
            'yield_unit_id' => ['nullable', 'integer'],
            'waste_percentage' => ['nullable', 'numeric', 'min:0'],
            'version' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ingredient_product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_of_measure_id' => ['nullable', 'integer'],
            'items.*.waste_percentage' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
