<?php

namespace App\Actions\Tax;

use App\DTOs\Tax\PphReportData;
use App\DTOs\Tax\PphReportResult;
use App\Enums\CustomerType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GeneratePphReport
{
    /**
     * @var array<string, string>
     */
    private const array PRODUCT_LABELS = [
        'deposit' => 'Deposito',
        'savings' => 'Tabungan',
    ];

    public function execute(PphReportData $data): PphReportResult
    {
        /** @var Collection<int, array{customer_id: int, customer_name: string, customer_type: string, npwp: string|null, product_type: string, gross_interest: float, tax_amount: float}> $depositRows */
        $depositRows = $data->productType !== 'savings'
            ? $this->getDepositAccruals($data)
            : collect();

        /** @var Collection<int, array{customer_id: int, customer_name: string, customer_type: string, npwp: string|null, product_type: string, gross_interest: float, tax_amount: float}> $savingsRows */
        $savingsRows = $data->productType !== 'deposit'
            ? $this->getSavingsAccruals($data)
            : collect();

        $merged = $depositRows->concat($savingsRows);

        /** @var Collection<int, array{customer_id: int, customer_name: string, customer_type: string, npwp: string|null, product_type: string, gross_interest: float, tax_amount: float, net_interest: float}> $customerBreakdown */
        $customerBreakdown = $merged
            ->groupBy('customer_id')
            ->map(function (Collection $rows): array {
                /** @var array{customer_id: int, customer_name: string, customer_type: string, npwp: string|null, product_type: string, gross_interest: float, tax_amount: float} $first */
                $first = $rows->first();
                /** @var float $grossSum */
                $grossSum = $rows->sum('gross_interest');
                /** @var float $taxSum */
                $taxSum = $rows->sum('tax_amount');

                return [
                    'customer_id' => $first['customer_id'],
                    'customer_name' => $first['customer_name'],
                    'customer_type' => $first['customer_type'],
                    'npwp' => $first['npwp'],
                    'product_type' => $rows->pluck('product_type')->unique()->implode(', '),
                    'gross_interest' => $grossSum,
                    'tax_amount' => $taxSum,
                    'net_interest' => round($grossSum - $taxSum, 2),
                ];
            })
            ->sortByDesc('gross_interest')
            ->values();

        /** @var float $totalGrossRaw */
        $totalGrossRaw = $customerBreakdown->sum('gross_interest');
        /** @var float $totalTaxRaw */
        $totalTaxRaw = $customerBreakdown->sum('tax_amount');
        $totalGross = round($totalGrossRaw, 2);
        $totalTax = round($totalTaxRaw, 2);

        return new PphReportResult(
            totalGrossInterest: $totalGross,
            totalTax: $totalTax,
            totalNetInterest: round($totalGross - $totalTax, 2),
            customerCount: $customerBreakdown->count(),
            customerBreakdown: $customerBreakdown,
        );
    }

    /**
     * @return Collection<int, array{customer_id: int, customer_name: string, customer_type: string, npwp: string|null, product_type: string, gross_interest: float, tax_amount: float}>
     */
    private function getDepositAccruals(PphReportData $data): Collection
    {
        $query = DB::table('deposit_interest_accruals')
            ->join('deposit_accounts', 'deposit_interest_accruals.deposit_account_id', '=', 'deposit_accounts.id')
            ->join('customers', 'deposit_accounts.customer_id', '=', 'customers.id')
            ->leftJoin('individual_details', 'customers.id', '=', 'individual_details.customer_id')
            ->leftJoin('corporate_details', 'customers.id', '=', 'corporate_details.customer_id')
            ->whereYear('deposit_interest_accruals.accrual_date', $data->year)
            ->where('deposit_interest_accruals.tax_amount', '>', 0);

        if ($data->month > 0) {
            $query->whereMonth('deposit_interest_accruals.accrual_date', $data->month);
        }

        if ($data->branchId !== null) {
            $query->where('deposit_accounts.branch_id', $data->branchId);
        }

        return $query
            ->select([
                'customers.id as customer_id',
                DB::raw('COALESCE(individual_details.full_name, corporate_details.company_name, customers.cif_number) as customer_name'),
                DB::raw("CASE WHEN customers.customer_type = '".CustomerType::Individual->value."' THEN 'Perorangan' ELSE 'Badan Usaha' END as customer_type"),
                DB::raw('COALESCE(individual_details.npwp, corporate_details.npwp_company) as npwp'),
                DB::raw('SUM(deposit_interest_accruals.accrued_amount) as gross_interest'),
                DB::raw('SUM(deposit_interest_accruals.tax_amount) as tax_amount'),
            ])
            ->groupBy('customers.id', 'customer_name', 'customer_type', 'npwp')
            ->get()
            ->map(function (object $row): array {
                return [
                    'customer_id' => (int) $row->customer_id, // @phpstan-ignore cast.int
                    'customer_name' => (string) $row->customer_name, // @phpstan-ignore cast.string
                    'customer_type' => (string) $row->customer_type, // @phpstan-ignore cast.string
                    'npwp' => isset($row->npwp) ? (string) $row->npwp : null, // @phpstan-ignore cast.string
                    'product_type' => self::PRODUCT_LABELS['deposit'],
                    'gross_interest' => round((float) $row->gross_interest, 2), // @phpstan-ignore cast.double
                    'tax_amount' => round((float) $row->tax_amount, 2), // @phpstan-ignore cast.double
                ];
            });
    }

    /**
     * @return Collection<int, array{customer_id: int, customer_name: string, customer_type: string, npwp: string|null, product_type: string, gross_interest: float, tax_amount: float}>
     */
    private function getSavingsAccruals(PphReportData $data): Collection
    {
        $query = DB::table('savings_interest_accruals')
            ->join('savings_accounts', 'savings_interest_accruals.savings_account_id', '=', 'savings_accounts.id')
            ->join('customers', 'savings_accounts.customer_id', '=', 'customers.id')
            ->leftJoin('individual_details', 'customers.id', '=', 'individual_details.customer_id')
            ->leftJoin('corporate_details', 'customers.id', '=', 'corporate_details.customer_id')
            ->whereYear('savings_interest_accruals.accrual_date', $data->year)
            ->where('savings_interest_accruals.tax_amount', '>', 0);

        if ($data->month > 0) {
            $query->whereMonth('savings_interest_accruals.accrual_date', $data->month);
        }

        if ($data->branchId !== null) {
            $query->where('savings_accounts.branch_id', $data->branchId);
        }

        return $query
            ->select([
                'customers.id as customer_id',
                DB::raw('COALESCE(individual_details.full_name, corporate_details.company_name, customers.cif_number) as customer_name'),
                DB::raw("CASE WHEN customers.customer_type = '".CustomerType::Individual->value."' THEN 'Perorangan' ELSE 'Badan Usaha' END as customer_type"),
                DB::raw('COALESCE(individual_details.npwp, corporate_details.npwp_company) as npwp'),
                DB::raw('SUM(savings_interest_accruals.accrued_amount) as gross_interest'),
                DB::raw('SUM(savings_interest_accruals.tax_amount) as tax_amount'),
            ])
            ->groupBy('customers.id', 'customer_name', 'customer_type', 'npwp')
            ->get()
            ->map(function (object $row): array {
                return [
                    'customer_id' => (int) $row->customer_id, // @phpstan-ignore cast.int
                    'customer_name' => (string) $row->customer_name, // @phpstan-ignore cast.string
                    'customer_type' => (string) $row->customer_type, // @phpstan-ignore cast.string
                    'npwp' => isset($row->npwp) ? (string) $row->npwp : null, // @phpstan-ignore cast.string
                    'product_type' => self::PRODUCT_LABELS['savings'],
                    'gross_interest' => round((float) $row->gross_interest, 2), // @phpstan-ignore cast.double
                    'tax_amount' => round((float) $row->tax_amount, 2), // @phpstan-ignore cast.double
                ];
            });
    }
}
