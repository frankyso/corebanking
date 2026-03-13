<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string'],
            'otp_code' => ['required', 'string', 'digits:6'],
            'purpose' => ['required', 'string', 'in:registration,transaction,pin_reset'],
        ];
    }
}
