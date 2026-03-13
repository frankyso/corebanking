<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ChangePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'new_pin' => ['required', 'string', 'digits:6', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'new_pin.digits' => 'PIN baru harus 6 digit.',
            'new_pin.confirmed' => 'Konfirmasi PIN tidak cocok.',
        ];
    }
}
