<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PerformCityActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'city_action_id' => ['required', 'integer', 'exists:city_actions,id'],
        ];
    }
}
