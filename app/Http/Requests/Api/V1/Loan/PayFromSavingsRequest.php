<?php

namespace App\Http\Requests\Api\V1\Loan;

use Illuminate\Foundation\Http\FormRequest;

class PayFromSavingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'savings_account_number' => ['required', 'string', 'exists:savings_accounts,account_number'],
            'amount' => ['required', 'numeric', 'min:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.min' => 'Jumlah pembayaran minimal Rp 1.000.',
        ];
    }
}
