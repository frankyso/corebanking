<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\MobileBanking\Auth\ChangeMobilePin;
use App\Actions\MobileBanking\Auth\LoginMobileUser;
use App\Actions\MobileBanking\Auth\RegisterDevice;
use App\Actions\MobileBanking\Auth\RegisterMobileUser;
use App\Actions\MobileBanking\Auth\RequestOtp;
use App\Actions\MobileBanking\Auth\ResetMobilePin;
use App\Actions\MobileBanking\Auth\VerifyOtp;
use App\DTOs\MobileBanking\RegisterDeviceData;
use App\DTOs\MobileBanking\RegisterMobileUserData;
use App\Enums\OtpPurpose;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePinRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterDeviceRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\RequestOtpRequest;
use App\Http\Requests\Api\V1\Auth\ResetPinRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Models\MobileUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterMobileUser $registerMobileUser,
        private readonly LoginMobileUser $loginMobileUser,
        private readonly RequestOtp $requestOtp,
        private readonly VerifyOtp $verifyOtp,
        private readonly ChangeMobilePin $changeMobilePin,
        private readonly ResetMobilePin $resetMobilePin,
        private readonly RegisterDevice $registerDevice,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $this->verifyOtp->execute(
                $request->string('phone_number')->value(),
                $request->string('otp_code')->value(),
                OtpPurpose::Registration,
            );

            $mobileUser = $this->registerMobileUser->execute(new RegisterMobileUserData(
                cifNumber: $request->string('cif_number')->value(),
                phoneNumber: $request->string('phone_number')->value(),
                pin: $request->string('pin')->value(),
            ));

            return response()->json([
                'data' => $mobileUser,
                'message' => 'Registrasi berhasil.',
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->loginMobileUser->execute(
                $request->string('phone_number')->value(),
                $request->string('pin')->value(),
                $request->string('device_name')->value(),
            );

            return response()->json([
                'data' => [
                    'token' => $result['token'],
                    'mobile_user' => $result['mobile_user'],
                ],
                'message' => 'Login berhasil.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var PersonalAccessToken $token */
        $token = $this->mobileUser($request)->currentAccessToken();
        $token->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }

    public function requestOtp(RequestOtpRequest $request): JsonResponse
    {
        try {
            $purpose = OtpPurpose::from($request->string('purpose')->value());

            $this->requestOtp->execute(
                $request->string('phone_number')->value(),
                $purpose,
            );

            return response()->json([
                'message' => 'Kode OTP telah dikirim.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $purpose = OtpPurpose::from($request->string('purpose')->value());

            $this->verifyOtp->execute(
                $request->string('phone_number')->value(),
                $request->string('otp_code')->value(),
                $purpose,
            );

            return response()->json([
                'message' => 'Verifikasi OTP berhasil.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function changePin(ChangePinRequest $request): JsonResponse
    {
        try {
            $this->changeMobilePin->execute(
                $this->mobileUser($request),
                $request->string('new_pin')->value(),
            );

            return response()->json([
                'message' => 'PIN berhasil diubah.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function requestPinReset(Request $request): JsonResponse
    {
        try {
            $mobileUser = $this->mobileUser($request);

            $this->requestOtp->execute(
                $mobileUser->phone_number,
                OtpPurpose::PinReset,
                $mobileUser->id,
            );

            return response()->json([
                'message' => 'Kode OTP untuk reset PIN telah dikirim.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function resetPin(ResetPinRequest $request): JsonResponse
    {
        try {
            $mobileUser = $this->mobileUser($request);

            $this->verifyOtp->execute(
                $mobileUser->phone_number,
                $request->string('otp_code')->value(),
                OtpPurpose::PinReset,
            );

            $this->resetMobilePin->execute(
                $mobileUser,
                $request->string('new_pin')->value(),
            );

            return response()->json([
                'message' => 'PIN berhasil direset.',
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function registerDevice(RegisterDeviceRequest $request): JsonResponse
    {
        try {
            $fcmToken = $request->string('fcm_token')->value();
            $device = $this->registerDevice->execute(
                $this->mobileUser($request),
                new RegisterDeviceData(
                    deviceId: $request->string('device_id')->value(),
                    deviceName: $request->string('device_name')->value(),
                    platform: $request->string('platform')->value(),
                    fcmToken: $fcmToken !== '' ? $fcmToken : null,
                ),
            );

            return response()->json([
                'data' => $device,
                'message' => 'Perangkat berhasil didaftarkan.',
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function mobileUser(Request $request): MobileUser
    {
        /** @var MobileUser */
        return $request->user('mobile');
    }
}
