<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SwitchContextRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'business_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }
}
