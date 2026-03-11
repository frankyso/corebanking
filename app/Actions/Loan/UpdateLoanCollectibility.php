<?php

namespace App\Actions\Loan;

use App\Enums\Collectibility;
use App\Models\LoanAccount;

class UpdateLoanCollectibility
{
    public function execute(LoanAccount $account): void
    {
        $dpd = $account->dpd;

        $collectibility = match (true) {
            $dpd <= 0 => Collectibility::Current,
            $dpd <= 90 => Collectibility::SpecialMention,
            $dpd <= 120 => Collectibility::Substandard,
            $dpd <= 180 => Collectibility::Doubtful,
            default => Collectibility::Loss,
        };

        $ckpn = bcmul((string) $account->outstanding_principal, (string) $collectibility->ckpnRate(), 2);

        $account->update([
            'collectibility' => $collectibility,
            'ckpn_amount' => $ckpn,
        ]);
    }
}
