<?php

namespace App\Http\Requests\Offline;

use Illuminate\Foundation\Http\FormRequest;

class SyncOfflineSalesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'batch_key' => ['required', 'string', 'max:120'],
            'sales' => ['required', 'array', 'min:1'],
            'sales.*.client_uuid' => ['required', 'string', 'max:120'],
            'sales.*.payload' => ['required', 'array'],
        ];
    }
}
