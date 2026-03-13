<?php

namespace App\DTOs\Tax;

use Illuminate\Support\Collection;

readonly class PphReportResult
{
    /**
     * @param  Collection<int, array{
     *     customer_id: int,
     *     customer_name: string,
     *     customer_type: string,
     *     npwp: string|null,
     *     product_type: string,
     *     gross_interest: float,
     *     tax_amount: float,
     *     net_interest: float,
     * }>  $customerBreakdown
     */
    public function __construct(
        public float $totalGrossInterest,
        public float $totalTax,
        public float $totalNetInterest,
        public int $customerCount,
        public Collection $customerBreakdown,
    ) {}
}
