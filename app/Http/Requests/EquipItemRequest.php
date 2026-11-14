<?php

namespace App\Http\Requests;

use App\EquipSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'slot' => ['nullable', Rule::enum(EquipSlot::class)],
        ];
    }
}
