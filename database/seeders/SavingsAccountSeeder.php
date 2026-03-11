<?php

namespace Database\Seeders;

use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\OpenSavingsAccount;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Models\User;
use Illuminate\Database\Seeder;

class SavingsAccountSeeder extends Seeder
{
    public function run(): void
    {
        $openAction = app(OpenSavingsAccount::class);
        $depositAction = app(DepositToSavings::class);
        $user = User::first();
        $product = SavingsProduct::where('code', 'T01')->first();

        $customers = Customer::query()->where('status', 'active')->get();

        $deposits = [5_000_000, 10_000_000, 25_000_000, 100_000_000];

        foreach ($customers as $index => $customer) {
            $account = $openAction->execute(new OpenSavingsAccountData(
                product: $product,
                customerId: $customer->id,
                branchId: $customer->branch_id,
                initialDeposit: $deposits[$index] ?? 1_000_000,
                performer: $user,
            ));

            if ($index < 2) {
                $depositAction->execute(
                    account: $account,
                    amount: 2_000_000,
                    performer: $user,
                    description: 'Setoran tambahan',
                );
            }
        }
    }
}
