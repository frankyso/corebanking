<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MobileBanking\Auth\VerifyOtp;
use App\Enums\CustomerType;
use App\Enums\OtpPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpdatePhoneRequest;
use App\Http\Resources\Api\V1\CustomerProfileResource;
use App\Http\Resources\Api\V1\IndividualDetailResource;
use App\Models\Customer;
use App\Models\MobileUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $this->mobileUser($request)->customer()->with('branch')->first();

        return CustomerProfileResource::make($customer)->response();
    }

    public function detail(Request $request): JsonResponse
    {
        $customer = $this->customer($request);

        if ($customer->customer_type === CustomerType::Individual) {
            return IndividualDetailResource::make($customer->individualDetail)->response();
        }

        return response()->json(['data' => [
            'company_name' => $customer->corporateDetail?->company_name,
            'legal_type' => $customer->corporateDetail?->legal_type,
            'nib' => $customer->corporateDetail?->nib,
            'npwp_company' => $customer->corporateDetail?->npwp_company,
            'business_sector' => $customer->corporateDetail?->business_sector,
            'address_company' => $customer->corporateDetail?->address_company,
            'city' => $customer->corporateDetail?->city,
            'province' => $customer->corporateDetail?->province,
            'phone_office' => $customer->corporateDetail?->phone_office,
            'email' => $customer->corporateDetail?->email,
            'contact_person_name' => $customer->corporateDetail?->contact_person_name,
            'contact_person_phone' => $customer->corporateDetail?->contact_person_phone,
            'contact_person_position' => $customer->corporateDetail?->contact_person_position,
        ]]);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => ['required', 'string'],
            'device_id' => ['required', 'string'],
        ]);

        $mobileUser = $this->mobileUser($request);
        $device = $mobileUser->devices()->where('device_id', $request->input('device_id'))->first();

        if ($device) {
            $device->update(['fcm_token' => $request->input('fcm_token')]);
        }

        return response()->json(['message' => 'FCM token updated.']);
    }

    public function updatePhone(UpdatePhoneRequest $request, VerifyOtp $verifyOtp): JsonResponse
    {
        $mobileUser = $this->mobileUser($request);

        try {
            $verifyOtp->execute(
                phoneNumber: $mobileUser->phone_number,
                otpCode: $request->string('otp_code')->value(),
                purpose: OtpPurpose::Transaction,
            );

            $mobileUser->update([
                'phone_number' => $request->string('phone_number')->value(),
            ]);

            return response()->json(['message' => 'Nomor telepon berhasil diperbarui.']);
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private function mobileUser(Request $request): MobileUser
    {
        /** @var MobileUser */
        return $request->user('mobile');
    }

    private function customer(Request $request): Customer
    {
        /** @var Customer */
        return $this->mobileUser($request)->customer;
    }
}
