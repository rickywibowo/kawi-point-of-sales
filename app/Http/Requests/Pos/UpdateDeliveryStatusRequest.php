<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'assigned', 'picked_up', 'delivered', 'cancelled'])],
            'courier_name' => ['nullable', 'string', 'max:160'],
            'courier_phone' => ['nullable', 'string', 'max:40'],
        ];
    }
}
