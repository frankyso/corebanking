<?php

namespace App\Http\Requests\Api\V1\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'source_account_number' => ['required', 'string', 'exists:savings_accounts,account_number'],
            'destination_account_number' => ['required', 'string', 'exists:savings_accounts,account_number', 'different:source_account_number'],
            'amount' => ['required', 'numeric', 'min:1000'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'destination_account_number.different' => 'Rekening tujuan harus berbeda dengan rekening sumber.',
            'destination_account_number.exists' => 'Rekening tujuan tidak ditemukan.',
            'amount.min' => 'Jumlah transfer minimal Rp 1.000.',
        ];
    }
}
