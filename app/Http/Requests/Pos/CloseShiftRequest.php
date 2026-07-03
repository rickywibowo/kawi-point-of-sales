<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class CloseShiftRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'drawer_counts' => ['nullable', 'array'],
            'drawer_counts.*.denomination' => ['required_with:drawer_counts', 'numeric', 'min:0'],
            'drawer_counts.*.quantity' => ['required_with:drawer_counts', 'integer', 'min:0'],
            'drawer_counts.*.label' => ['nullable', 'string', 'max:40'],
            'variance_reason' => ['nullable', 'string', 'max:1000'],
            'variance_approved' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
