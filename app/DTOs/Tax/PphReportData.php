<?php

namespace App\DTOs\Tax;

readonly class PphReportData
{
    public function __construct(
        public int $year,
        public int $month = 0,
        public ?string $productType = null,
        public ?int $branchId = null,
    ) {}
}
