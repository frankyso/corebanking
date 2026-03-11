<?php

namespace App\Actions\Loan;

use App\DTOs\Loan\CreateLoanApplicationData;
use App\Enums\LoanApplicationStatus;
use App\Exceptions\Loan\InvalidLoanAmountException;
use App\Exceptions\Loan\InvalidLoanTenorException;
use App\Models\LoanApplication;
use Illuminate\Support\Facades\DB;

class CreateLoanApplication
{
    public function execute(CreateLoanApplicationData $dto): LoanApplication
    {
        if ($dto->requestedAmount < (float) $dto->product->min_amount) {
            throw InvalidLoanAmountException::belowMinimum($dto->product, $dto->requestedAmount);
        }

        if ($dto->product->max_amount && $dto->requestedAmount > (float) $dto->product->max_amount) {
            throw InvalidLoanAmountException::aboveMaximum($dto->product, $dto->requestedAmount);
        }

        if ($dto->requestedTenor < $dto->product->min_tenor_months || $dto->requestedTenor > $dto->product->max_tenor_months) {
            throw InvalidLoanTenorException::outOfRange($dto->product, $dto->requestedTenor);
        }

        return DB::transaction(function () use ($dto): LoanApplication {
            return LoanApplication::create([
                'application_number' => $this->generateApplicationNumber(),
                'customer_id' => $dto->customerId,
                'loan_product_id' => $dto->product->id,
                'branch_id' => $dto->branchId,
                'status' => LoanApplicationStatus::Submitted,
                'requested_amount' => $dto->requestedAmount,
                'requested_tenor_months' => $dto->requestedTenor,
                'interest_rate' => $dto->product->interest_rate,
                'purpose' => $dto->purpose,
                'loan_officer_id' => $dto->loanOfficerId,
                'created_by' => $dto->creator->id,
            ]);
        });
    }

    private function generateApplicationNumber(): string
    {
        return 'APP'.now()->format('Ymd').str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }
}
