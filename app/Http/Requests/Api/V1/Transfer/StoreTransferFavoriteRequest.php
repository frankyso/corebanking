<?php

namespace App\Http\Requests\Api\V1\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransferFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'account_number' => ['required', 'string', 'exists:savings_accounts,account_number'],
            'alias' => ['required', 'string', 'max:100'],
        ];
    }
}
