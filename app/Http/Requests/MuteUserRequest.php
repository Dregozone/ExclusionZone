<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MuteUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:10080'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
