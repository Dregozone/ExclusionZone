<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'role_key' => ['required', 'string', 'exists:roles,key'],
        ];
    }
}
