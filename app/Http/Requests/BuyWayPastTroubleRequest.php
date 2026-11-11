<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyWayPastTroubleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * The quantity of each item to hand over, keyed by item id.
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
