<?php

namespace App\Actions\MobileBanking\Auth;

use App\DTOs\MobileBanking\RegisterMobileUserData;
use App\Enums\CustomerStatus;
use App\Exceptions\MobileBanking\MobileUserNotActiveException;
use App\Models\Customer;
use App\Models\MobileUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterMobileUser
{
    public function execute(RegisterMobileUserData $dto): MobileUser
    {
        $customer = Customer::where('cif_number', $dto->cifNumber)->first();

        if (! $customer) {
            throw MobileUserNotActiveException::customerNotActive();
        }

        if ($customer->status !== CustomerStatus::Active) {
            throw MobileUserNotActiveException::customerNotActive();
        }

        if ($customer->mobileUser()->exists()) {
            throw MobileUserNotActiveException::alreadyRegistered();
        }

        return DB::transaction(function () use ($customer, $dto): MobileUser {
            return MobileUser::create([
                'customer_id' => $customer->id,
                'phone_number' => $dto->phoneNumber,
                'pin_hash' => Hash::make($dto->pin),
                'is_active' => true,
            ]);
        });
    }
}
