<?php

namespace App\Http\Requests;

use App\EquipSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnequipItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'slot' => ['required', Rule::enum(EquipSlot::class)],
        ];
    }
}
