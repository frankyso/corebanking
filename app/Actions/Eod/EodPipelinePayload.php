<?php

namespace App\Actions\Eod;

use App\Models\EodProcess;
use App\Models\User;
use Carbon\Carbon;

class EodPipelinePayload
{
    public function __construct(
        public readonly Carbon $processDate,
        public readonly User $performer,
        public EodProcess $process,
    ) {}
}
