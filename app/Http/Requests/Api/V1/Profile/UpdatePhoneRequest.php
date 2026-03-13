<?php

namespace App\Http\Requests\Api\V1\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'digits_between:10,15', 'unique:mobile_users,phone_number'],
            'otp_code' => ['required', 'string', 'digits:6'],
        ];
    }
}
