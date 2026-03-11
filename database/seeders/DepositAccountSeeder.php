<?php

namespace Database\Seeders;

use App\Actions\Deposit\PlaceDeposit;
use App\DTOs\Deposit\PlaceDepositData;
use App\Enums\CustomerStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Models\Customer;
use App\Models\DepositProduct;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DepositAccountSeeder extends Seeder
{
    public function run(): void
    {
        $placeDeposit = app(PlaceDeposit::class);
        $admin = User::where('email', 'admin@corebanking.test')->first();
        $product = DepositProduct::where('code', 'D01')->first();

        if (! $admin || ! $product) {
            return;
        }

        $activeCustomers = Customer::where('status', CustomerStatus::Active)->limit(3)->get();

        if ($activeCustomers->isEmpty()) {
            return;
        }

        $placements = [
            [
                'customer' => $activeCustomers->get(0),
                'principal' => 10000000,
                'tenor' => 3,
                'method' => InterestPaymentMethod::Maturity,
                'rollover' => RolloverType::None,
            ],
            [
                'customer' => $activeCustomers->get(1),
                'principal' => 50000000,
                'tenor' => 6,
                'method' => InterestPaymentMethod::Monthly,
                'rollover' => RolloverType::PrincipalOnly,
            ],
            [
                'customer' => $activeCustomers->get(0),
                'principal' => 100000000,
                'tenor' => 12,
                'method' => InterestPaymentMethod::Maturity,
                'rollover' => RolloverType::PrincipalAndInterest,
            ],
        ];

        if ($activeCustomers->count() >= 3) {
            $placements[] = [
                'customer' => $activeCustomers->get(2),
                'principal' => 25000000,
                'tenor' => 1,
                'method' => InterestPaymentMethod::Upfront,
                'rollover' => RolloverType::None,
            ];
        }

        foreach ($placements as $placement) {
            if (! $placement['customer']) {
                continue;
            }

            $placeDeposit->execute(
                new PlaceDepositData(
                    product: $product,
                    customerId: $placement['customer']->id,
                    branchId: $admin->branch_id ?? 1,
                    principalAmount: $placement['principal'],
                    tenorMonths: $placement['tenor'],
                    interestPaymentMethod: $placement['method'],
                    rolloverType: $placement['rollover'],
                    savingsAccountId: null,
                    performer: $admin,
                    placementDate: Carbon::now(),
                ),
            );
        }
    }
}
