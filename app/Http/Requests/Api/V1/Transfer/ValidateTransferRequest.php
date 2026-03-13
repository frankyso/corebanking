<?php

namespace App\Http\Requests\Api\V1\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'destination_account_number' => ['required', 'string'],
        ];
    }
}
