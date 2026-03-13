<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RequestOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'phone_number' => ['required', 'string', 'digits_between:10,15'],
            'purpose' => ['required', 'string', 'in:registration,transaction,pin_reset'],
        ];
    }
}
