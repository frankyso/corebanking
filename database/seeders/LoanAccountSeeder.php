<?php

namespace Database\Seeders;

use App\Enums\CollateralType;
use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;
use App\Services\LoanService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LoanAccountSeeder extends Seeder
{
    public function run(): void
    {
        $loanService = app(LoanService::class);
        $admin = User::where('email', 'admin@corebanking.test')->first();
        $kmkProduct = LoanProduct::where('code', 'K01')->first();
        $kiProduct = LoanProduct::where('code', 'K02')->first();
        $kkProduct = LoanProduct::where('code', 'K03')->first();

        if (! $admin || ! $kmkProduct) {
            return;
        }

        $activeCustomers = Customer::where('status', CustomerStatus::Active)->limit(3)->get();

        if ($activeCustomers->isEmpty()) {
            return;
        }

        $approver = User::where('email', '!=', 'admin@corebanking.test')
            ->where('is_active', true)
            ->first() ?? $admin;

        $loans = [
            [
                'customer' => $activeCustomers->get(0),
                'product' => $kmkProduct,
                'amount' => 50000000,
                'tenor' => 12,
                'purpose' => 'Modal kerja usaha perdagangan',
                'collateral' => [
                    'collateral_type' => CollateralType::Vehicle,
                    'description' => 'Toyota Avanza 2022',
                    'document_number' => 'BPKB-1234567',
                    'appraised_value' => 150000000,
                    'liquidation_value' => 120000000,
                    'ownership_name' => 'Budi Santoso',
                ],
            ],
            [
                'customer' => $activeCustomers->get(1),
                'product' => $kiProduct ?? $kmkProduct,
                'amount' => 100000000,
                'tenor' => 24,
                'purpose' => 'Pembelian mesin produksi',
                'collateral' => [
                    'collateral_type' => CollateralType::Land,
                    'description' => 'Tanah SHM 200m2 di Jl. Raya Utama',
                    'document_number' => 'SHM-00123',
                    'appraised_value' => 500000000,
                    'liquidation_value' => 400000000,
                    'location' => 'Jl. Raya Utama No. 10',
                    'ownership_name' => 'Siti Aminah',
                ],
            ],
        ];

        if ($activeCustomers->count() >= 3 && $kkProduct) {
            $loans[] = [
                'customer' => $activeCustomers->get(2),
                'product' => $kkProduct,
                'amount' => 25000000,
                'tenor' => 12,
                'purpose' => 'Renovasi rumah tinggal',
                'collateral' => [
                    'collateral_type' => CollateralType::Building,
                    'description' => 'Rumah tinggal LT 100m2 LB 80m2',
                    'document_number' => 'SHM-00456',
                    'appraised_value' => 300000000,
                    'liquidation_value' => 240000000,
                    'location' => 'Perum Griya Asri Blok C-5',
                    'ownership_name' => 'Ahmad Hidayat',
                ],
            ];
        }

        foreach ($loans as $loan) {
            if (! $loan['customer']) {
                continue;
            }

            $application = $loanService->createApplication(
                product: $loan['product'],
                customerId: $loan['customer']->id,
                branchId: $admin->branch_id ?? 1,
                requestedAmount: $loan['amount'],
                requestedTenor: $loan['tenor'],
                purpose: $loan['purpose'],
                creator: $admin,
            );

            $application->collaterals()->create($loan['collateral']);

            if ($approver->id !== $admin->id) {
                $loanService->approveApplication(
                    application: $application,
                    approver: $approver,
                );
            } else {
                $application->update([
                    'status' => 'approved',
                    'approved_amount' => $loan['amount'],
                    'approved_tenor_months' => $loan['tenor'],
                    'approved_by' => $approver->id,
                    'approved_at' => now(),
                ]);
            }

            $application->refresh();

            $loanService->disburse(
                application: $application,
                performer: $admin,
                disbursementDate: Carbon::now()->subMonths(2),
            );
        }
    }
}
