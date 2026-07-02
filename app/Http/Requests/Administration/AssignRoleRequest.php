<?php

namespace App\Http\Requests\Administration;

use Illuminate\Foundation\Http\FormRequest;

class AssignRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer'],
            'branch_id' => ['nullable', 'integer'],
        ];
    }
}
