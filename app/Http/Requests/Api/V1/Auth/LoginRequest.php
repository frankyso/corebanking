<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'pin' => ['required', 'string', 'digits:6'],
            'device_id' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:android,ios'],
        ];
    }
}
