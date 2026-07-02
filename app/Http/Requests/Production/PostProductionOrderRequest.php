<?php

namespace App\Http\Requests\Production;

use Illuminate\Foundation\Http\FormRequest;

class PostProductionOrderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer'],
            'recipe_id' => ['required', 'integer'],
            'production_number' => ['required', 'string', 'max:80'],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'actual_quantity' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
