<?php

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableReservationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reservation_number' => ['required', 'string', 'max:80'],
            'customer_id' => ['nullable', 'integer'],
            'guest_name' => ['required', 'string', 'max:160'],
            'guest_phone' => ['nullable', 'string', 'max:40'],
            'party_size' => ['required', 'integer', 'min:1', 'max:50'],
            'reserved_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
