<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDiningTableRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:50'],
            'section' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['available', 'occupied', 'reserved', 'cleaning', 'inactive'])],
        ];
    }
}
