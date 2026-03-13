<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'cif_number' => ['required', 'string', 'exists:customers,cif_number'],
            'phone_number' => ['required', 'string', 'digits_between:10,15', 'unique:mobile_users,phone_number'],
            'pin' => ['required', 'string', 'digits:6'],
            'otp_code' => ['required', 'string', 'digits:6'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'cif_number.exists' => 'Nomor CIF tidak ditemukan.',
            'phone_number.unique' => 'Nomor telepon sudah terdaftar.',
            'pin.digits' => 'PIN harus 6 digit.',
            'otp_code.digits' => 'Kode OTP harus 6 digit.',
        ];
    }
}
