<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'device_id' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:android,ios'],
            'fcm_token' => ['nullable', 'string'],
        ];
    }
}
