<?php

namespace App\DTOs\Eod;

use App\Models\User;
use Carbon\Carbon;

readonly class EodProcessData
{
    public function __construct(
        public Carbon $processDate,
        public User $performer,
    ) {}
}
