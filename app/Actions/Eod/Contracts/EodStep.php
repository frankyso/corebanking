<?php

namespace App\Actions\Eod\Contracts;

use App\Actions\Eod\EodPipelinePayload;

interface EodStep
{
    public function handle(EodPipelinePayload $payload, \Closure $next): EodPipelinePayload;
}
