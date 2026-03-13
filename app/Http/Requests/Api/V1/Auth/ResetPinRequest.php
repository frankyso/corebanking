<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ResetPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'otp_code' => ['required', 'string', 'digits:6'],
            'new_pin' => ['required', 'string', 'digits:6', 'confirmed'],
        ];
    }
}
