<?php

namespace App\Actions\MobileBanking;

use App\Actions\Loan\MakeLoanPayment;
use App\Actions\Savings\WithdrawFromSavings;
use App\DTOs\Loan\MakeLoanPaymentData;
use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\MobileUser;
use App\Models\SavingsAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PayLoanFromSavings
{
    public function __construct(
        private WithdrawFromSavings $withdrawFromSavings,
        private MakeLoanPayment $makeLoanPayment,
    ) {}

    public function execute(
        SavingsAccount $savingsAccount,
        LoanAccount $loanAccount,
        float $amount,
        MobileUser $performer,
    ): LoanPayment {
        $systemUser = User::where('employee_id', 'MOBILE_SYSTEM')->firstOrFail();

        return DB::transaction(function () use ($savingsAccount, $loanAccount, $amount, $systemUser): LoanPayment {
            $this->withdrawFromSavings->execute(
                account: $savingsAccount,
                amount: $amount,
                performer: $systemUser,
                description: 'Pembayaran angsuran kredit via Mobile Banking',
            );

            return $this->makeLoanPayment->execute(new MakeLoanPaymentData(
                account: $loanAccount,
                amount: $amount,
                performer: $systemUser,
                description: 'Pembayaran via Mobile Banking dari rekening '.$savingsAccount->account_number,
            ));
        });
    }
}
