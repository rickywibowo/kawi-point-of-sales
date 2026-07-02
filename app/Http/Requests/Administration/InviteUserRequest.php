<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class InviteUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:160'],
            'password' => ['nullable', 'string', 'min:8', 'max:160'],
            'branch_id' => ['nullable', 'integer'],
            'is_owner' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*.role_id' => ['required', 'integer'],
            'roles.*.branch_id' => ['nullable', 'integer'],
        ];
    }
}
