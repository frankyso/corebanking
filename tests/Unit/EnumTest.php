<?php

use App\Enums\AccountGroup;
use App\Enums\ApprovalStatus;
use App\Enums\CollateralType;
use App\Enums\Collectibility;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\DepositStatus;
use App\Enums\EodStatus;
use App\Enums\Gender;
use App\Enums\InterestCalcMethod;
use App\Enums\InterestPaymentMethod;
use App\Enums\InterestType;
use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Enums\LoanType;
use App\Enums\MaritalStatus;
use App\Enums\NormalBalance;
use App\Enums\RiskRating;
use App\Enums\RolloverType;
use App\Enums\SavingsAccountStatus;
use App\Enums\SavingsTransactionType;
use App\Enums\TellerSessionStatus;
use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;

// ============================================================================
// AccountGroup
// ============================================================================

describe('AccountGroup', function (): void {
    it('has exactly 5 cases', function (): void {
        expect(AccountGroup::cases())->toHaveCount(5);
    });

    it('has correct backing values', function (AccountGroup $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Asset' => [AccountGroup::Asset, 'asset'],
        'Liability' => [AccountGroup::Liability, 'liability'],
        'Equity' => [AccountGroup::Equity, 'equity'],
        'Revenue' => [AccountGroup::Revenue, 'revenue'],
        'Expense' => [AccountGroup::Expense, 'expense'],
    ]);

    it('returns correct labels', function (AccountGroup $case, string $label): void {
        expect($case->label())->toBe($label);
    })->with([
        'Asset' => [AccountGroup::Asset, 'Aset'],
        'Liability' => [AccountGroup::Liability, 'Kewajiban'],
        'Equity' => [AccountGroup::Equity, 'Ekuitas'],
        'Revenue' => [AccountGroup::Revenue, 'Pendapatan'],
        'Expense' => [AccountGroup::Expense, 'Beban'],
    ]);

    it('returns correct code prefixes', function (AccountGroup $case, string $prefix): void {
        expect($case->codePrefix())->toBe($prefix);
    })->with([
        'Asset' => [AccountGroup::Asset, '1'],
        'Liability' => [AccountGroup::Liability, '2'],
        'Equity' => [AccountGroup::Equity, '3'],
        'Revenue' => [AccountGroup::Revenue, '4'],
        'Expense' => [AccountGroup::Expense, '5'],
    ]);

    it('returns non-empty strings for label and codePrefix on every case', function (AccountGroup $case): void {
        expect($case->label())->toBeString()->not->toBeEmpty()
            ->and($case->codePrefix())->toBeString()->not->toBeEmpty();
    })->with(AccountGroup::cases());
});

// ============================================================================
// NormalBalance
// ============================================================================

describe('NormalBalance', function (): void {
    it('has exactly 2 cases', function (): void {
        expect(NormalBalance::cases())->toHaveCount(2);
    });

    it('has correct backing values', function (NormalBalance $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Debit' => [NormalBalance::Debit, 'debit'],
        'Credit' => [NormalBalance::Credit, 'credit'],
    ]);

    it('returns correct labels', function (NormalBalance $case, string $label): void {
        expect($case->label())->toBe($label);
    })->with([
        'Debit' => [NormalBalance::Debit, 'Debit'],
        'Credit' => [NormalBalance::Credit, 'Kredit'],
    ]);
});

// ============================================================================
// ApprovalStatus
// ============================================================================

describe('ApprovalStatus', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(ApprovalStatus::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (ApprovalStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Pending' => [ApprovalStatus::Pending, 'pending'],
        'Approved' => [ApprovalStatus::Approved, 'approved'],
        'Rejected' => [ApprovalStatus::Rejected, 'rejected'],
    ]);

    it('returns correct labels', function (ApprovalStatus $case, string $label): void {
        expect($case->label())->toBe($label);
    })->with([
        'Pending' => [ApprovalStatus::Pending, 'Menunggu Persetujuan'],
        'Approved' => [ApprovalStatus::Approved, 'Disetujui'],
        'Rejected' => [ApprovalStatus::Rejected, 'Ditolak'],
    ]);

    it('returns correct colors', function (ApprovalStatus $case, string $color): void {
        expect($case->color())->toBe($color);
    })->with([
        'Pending' => [ApprovalStatus::Pending, 'warning'],
        'Approved' => [ApprovalStatus::Approved, 'success'],
        'Rejected' => [ApprovalStatus::Rejected, 'danger'],
    ]);

    it('returns non-empty strings for label and color on every case', function (ApprovalStatus $case): void {
        expect($case->label())->toBeString()->not->toBeEmpty()
            ->and($case->color())->toBeString()->not->toBeEmpty();
    })->with(ApprovalStatus::cases());
});

// ============================================================================
// CustomerType
// ============================================================================

describe('CustomerType', function (): void {
    it('has exactly 2 cases', function (): void {
        expect(CustomerType::cases())->toHaveCount(2);
    });

    it('has correct backing values', function (CustomerType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Individual' => [CustomerType::Individual, 'individual'],
        'Corporate' => [CustomerType::Corporate, 'corporate'],
    ]);

    it('returns correct labels', function (CustomerType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Individual' => [CustomerType::Individual, 'Perorangan'],
        'Corporate' => [CustomerType::Corporate, 'Badan Usaha'],
    ]);
});

// ============================================================================
// CustomerStatus
// ============================================================================

describe('CustomerStatus', function (): void {
    it('has exactly 5 cases', function (): void {
        expect(CustomerStatus::cases())->toHaveCount(5);
    });

    it('has correct backing values', function (CustomerStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'PendingApproval' => [CustomerStatus::PendingApproval, 'pending_approval'],
        'Active' => [CustomerStatus::Active, 'active'],
        'Inactive' => [CustomerStatus::Inactive, 'inactive'],
        'Blocked' => [CustomerStatus::Blocked, 'blocked'],
        'Closed' => [CustomerStatus::Closed, 'closed'],
    ]);

    it('returns correct labels', function (CustomerStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'PendingApproval' => [CustomerStatus::PendingApproval, 'Menunggu Persetujuan'],
        'Active' => [CustomerStatus::Active, 'Aktif'],
        'Inactive' => [CustomerStatus::Inactive, 'Tidak Aktif'],
        'Blocked' => [CustomerStatus::Blocked, 'Diblokir'],
        'Closed' => [CustomerStatus::Closed, 'Ditutup'],
    ]);

    it('returns correct colors', function (CustomerStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'PendingApproval' => [CustomerStatus::PendingApproval, 'warning'],
        'Active' => [CustomerStatus::Active, 'success'],
        'Inactive' => [CustomerStatus::Inactive, 'gray'],
        'Blocked' => [CustomerStatus::Blocked, 'danger'],
        'Closed' => [CustomerStatus::Closed, 'gray'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (CustomerStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(CustomerStatus::cases());
});

// ============================================================================
// Gender
// ============================================================================

describe('Gender', function (): void {
    it('has exactly 2 cases', function (): void {
        expect(Gender::cases())->toHaveCount(2);
    });

    it('has correct backing values', function (Gender $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Male' => [Gender::Male, 'M'],
        'Female' => [Gender::Female, 'F'],
    ]);

    it('returns correct labels', function (Gender $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Male' => [Gender::Male, 'Laki-laki'],
        'Female' => [Gender::Female, 'Perempuan'],
    ]);
});

// ============================================================================
// MaritalStatus
// ============================================================================

describe('MaritalStatus', function (): void {
    it('has exactly 4 cases', function (): void {
        expect(MaritalStatus::cases())->toHaveCount(4);
    });

    it('has correct backing values', function (MaritalStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Single' => [MaritalStatus::Single, 'single'],
        'Married' => [MaritalStatus::Married, 'married'],
        'Divorced' => [MaritalStatus::Divorced, 'divorced'],
        'Widowed' => [MaritalStatus::Widowed, 'widowed'],
    ]);

    it('returns correct labels', function (MaritalStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Single' => [MaritalStatus::Single, 'Belum Menikah'],
        'Married' => [MaritalStatus::Married, 'Menikah'],
        'Divorced' => [MaritalStatus::Divorced, 'Cerai Hidup'],
        'Widowed' => [MaritalStatus::Widowed, 'Cerai Mati'],
    ]);
});

// ============================================================================
// RiskRating
// ============================================================================

describe('RiskRating', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(RiskRating::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (RiskRating $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Low' => [RiskRating::Low, 'low'],
        'Medium' => [RiskRating::Medium, 'medium'],
        'High' => [RiskRating::High, 'high'],
    ]);

    it('returns correct labels', function (RiskRating $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Low' => [RiskRating::Low, 'Rendah'],
        'Medium' => [RiskRating::Medium, 'Menengah'],
        'High' => [RiskRating::High, 'Tinggi'],
    ]);

    it('returns correct colors', function (RiskRating $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Low' => [RiskRating::Low, 'success'],
        'Medium' => [RiskRating::Medium, 'warning'],
        'High' => [RiskRating::High, 'danger'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (RiskRating $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(RiskRating::cases());
});

// ============================================================================
// InterestCalcMethod
// ============================================================================

describe('InterestCalcMethod', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(InterestCalcMethod::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (InterestCalcMethod $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'DailyBalance' => [InterestCalcMethod::DailyBalance, 'daily_balance'],
        'AverageBalance' => [InterestCalcMethod::AverageBalance, 'average_balance'],
        'LowestBalance' => [InterestCalcMethod::LowestBalance, 'lowest_balance'],
    ]);

    it('returns correct labels', function (InterestCalcMethod $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'DailyBalance' => [InterestCalcMethod::DailyBalance, 'Saldo Harian'],
        'AverageBalance' => [InterestCalcMethod::AverageBalance, 'Saldo Rata-rata'],
        'LowestBalance' => [InterestCalcMethod::LowestBalance, 'Saldo Terendah'],
    ]);
});

// ============================================================================
// SavingsAccountStatus
// ============================================================================

describe('SavingsAccountStatus', function (): void {
    it('has exactly 4 cases', function (): void {
        expect(SavingsAccountStatus::cases())->toHaveCount(4);
    });

    it('has correct backing values', function (SavingsAccountStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Active' => [SavingsAccountStatus::Active, 'active'],
        'Dormant' => [SavingsAccountStatus::Dormant, 'dormant'],
        'Frozen' => [SavingsAccountStatus::Frozen, 'frozen'],
        'Closed' => [SavingsAccountStatus::Closed, 'closed'],
    ]);

    it('returns correct labels', function (SavingsAccountStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Active' => [SavingsAccountStatus::Active, 'Aktif'],
        'Dormant' => [SavingsAccountStatus::Dormant, 'Dorman'],
        'Frozen' => [SavingsAccountStatus::Frozen, 'Dibekukan'],
        'Closed' => [SavingsAccountStatus::Closed, 'Ditutup'],
    ]);

    it('returns correct colors', function (SavingsAccountStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Active' => [SavingsAccountStatus::Active, 'success'],
        'Dormant' => [SavingsAccountStatus::Dormant, 'warning'],
        'Frozen' => [SavingsAccountStatus::Frozen, 'danger'],
        'Closed' => [SavingsAccountStatus::Closed, 'gray'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (SavingsAccountStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(SavingsAccountStatus::cases());
});

// ============================================================================
// SavingsTransactionType
// ============================================================================

describe('SavingsTransactionType', function (): void {
    it('has exactly 10 cases', function (): void {
        expect(SavingsTransactionType::cases())->toHaveCount(10);
    });

    it('has correct backing values', function (SavingsTransactionType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Deposit' => [SavingsTransactionType::Deposit, 'deposit'],
        'Withdrawal' => [SavingsTransactionType::Withdrawal, 'withdrawal'],
        'InterestCredit' => [SavingsTransactionType::InterestCredit, 'interest_credit'],
        'AdminFee' => [SavingsTransactionType::AdminFee, 'admin_fee'],
        'Tax' => [SavingsTransactionType::Tax, 'tax'],
        'Transfer' => [SavingsTransactionType::Transfer, 'transfer'],
        'Opening' => [SavingsTransactionType::Opening, 'opening'],
        'Closing' => [SavingsTransactionType::Closing, 'closing'],
        'Hold' => [SavingsTransactionType::Hold, 'hold'],
        'Unhold' => [SavingsTransactionType::Unhold, 'unhold'],
    ]);

    it('returns correct labels', function (SavingsTransactionType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Deposit' => [SavingsTransactionType::Deposit, 'Setoran'],
        'Withdrawal' => [SavingsTransactionType::Withdrawal, 'Penarikan'],
        'InterestCredit' => [SavingsTransactionType::InterestCredit, 'Bunga'],
        'AdminFee' => [SavingsTransactionType::AdminFee, 'Biaya Admin'],
        'Tax' => [SavingsTransactionType::Tax, 'Pajak'],
        'Transfer' => [SavingsTransactionType::Transfer, 'Transfer'],
        'Opening' => [SavingsTransactionType::Opening, 'Pembukaan'],
        'Closing' => [SavingsTransactionType::Closing, 'Penutupan'],
        'Hold' => [SavingsTransactionType::Hold, 'Pemblokiran'],
        'Unhold' => [SavingsTransactionType::Unhold, 'Pembukaan Blokir'],
    ]);

    it('returns correct colors', function (SavingsTransactionType $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Deposit' => [SavingsTransactionType::Deposit, 'success'],
        'Withdrawal' => [SavingsTransactionType::Withdrawal, 'danger'],
        'InterestCredit' => [SavingsTransactionType::InterestCredit, 'success'],
        'AdminFee' => [SavingsTransactionType::AdminFee, 'danger'],
        'Tax' => [SavingsTransactionType::Tax, 'danger'],
        'Transfer' => [SavingsTransactionType::Transfer, 'info'],
        'Opening' => [SavingsTransactionType::Opening, 'success'],
        'Closing' => [SavingsTransactionType::Closing, 'danger'],
        'Hold' => [SavingsTransactionType::Hold, 'danger'],
        'Unhold' => [SavingsTransactionType::Unhold, 'success'],
    ]);

    it('identifies credit types correctly', function (SavingsTransactionType $case): void {
        expect($case->isCredit())->toBeTrue()
            ->and($case->isDebit())->toBeFalse();
    })->with([
        'Deposit' => [SavingsTransactionType::Deposit],
        'InterestCredit' => [SavingsTransactionType::InterestCredit],
        'Opening' => [SavingsTransactionType::Opening],
        'Unhold' => [SavingsTransactionType::Unhold],
    ]);

    it('identifies debit types correctly', function (SavingsTransactionType $case): void {
        expect($case->isDebit())->toBeTrue()
            ->and($case->isCredit())->toBeFalse();
    })->with([
        'Withdrawal' => [SavingsTransactionType::Withdrawal],
        'AdminFee' => [SavingsTransactionType::AdminFee],
        'Tax' => [SavingsTransactionType::Tax],
        'Closing' => [SavingsTransactionType::Closing],
        'Hold' => [SavingsTransactionType::Hold],
    ]);

    it('identifies Transfer as neither credit nor debit', function (): void {
        expect(SavingsTransactionType::Transfer->isCredit())->toBeFalse()
            ->and(SavingsTransactionType::Transfer->isDebit())->toBeFalse();
    });

    it('returns non-empty strings for getLabel and getColor on every case', function (SavingsTransactionType $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(SavingsTransactionType::cases());
});

// ============================================================================
// DepositStatus
// ============================================================================

describe('DepositStatus', function (): void {
    it('has exactly 6 cases', function (): void {
        expect(DepositStatus::cases())->toHaveCount(6);
    });

    it('has correct backing values', function (DepositStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Active' => [DepositStatus::Active, 'active'],
        'Matured' => [DepositStatus::Matured, 'matured'],
        'Withdrawn' => [DepositStatus::Withdrawn, 'withdrawn'],
        'Rolled' => [DepositStatus::Rolled, 'rolled'],
        'Pledged' => [DepositStatus::Pledged, 'pledged'],
        'Closed' => [DepositStatus::Closed, 'closed'],
    ]);

    it('returns correct labels', function (DepositStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Active' => [DepositStatus::Active, 'Aktif'],
        'Matured' => [DepositStatus::Matured, 'Jatuh Tempo'],
        'Withdrawn' => [DepositStatus::Withdrawn, 'Dicairkan'],
        'Rolled' => [DepositStatus::Rolled, 'Diperpanjang'],
        'Pledged' => [DepositStatus::Pledged, 'Dijaminkan'],
        'Closed' => [DepositStatus::Closed, 'Ditutup'],
    ]);

    it('returns correct colors', function (DepositStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Active' => [DepositStatus::Active, 'success'],
        'Matured' => [DepositStatus::Matured, 'warning'],
        'Withdrawn' => [DepositStatus::Withdrawn, 'gray'],
        'Rolled' => [DepositStatus::Rolled, 'info'],
        'Pledged' => [DepositStatus::Pledged, 'danger'],
        'Closed' => [DepositStatus::Closed, 'gray'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (DepositStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(DepositStatus::cases());
});

// ============================================================================
// InterestPaymentMethod
// ============================================================================

describe('InterestPaymentMethod', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(InterestPaymentMethod::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (InterestPaymentMethod $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Maturity' => [InterestPaymentMethod::Maturity, 'maturity'],
        'Monthly' => [InterestPaymentMethod::Monthly, 'monthly'],
        'Upfront' => [InterestPaymentMethod::Upfront, 'upfront'],
    ]);

    it('returns correct labels', function (InterestPaymentMethod $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Maturity' => [InterestPaymentMethod::Maturity, 'Saat Jatuh Tempo'],
        'Monthly' => [InterestPaymentMethod::Monthly, 'Bulanan'],
        'Upfront' => [InterestPaymentMethod::Upfront, 'Di Muka'],
    ]);
});

// ============================================================================
// RolloverType
// ============================================================================

describe('RolloverType', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(RolloverType::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (RolloverType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'None' => [RolloverType::None, 'none'],
        'PrincipalOnly' => [RolloverType::PrincipalOnly, 'principal_only'],
        'PrincipalAndInterest' => [RolloverType::PrincipalAndInterest, 'principal_and_interest'],
    ]);

    it('returns correct labels', function (RolloverType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'None' => [RolloverType::None, 'Tidak Diperpanjang'],
        'PrincipalOnly' => [RolloverType::PrincipalOnly, 'Pokok Saja'],
        'PrincipalAndInterest' => [RolloverType::PrincipalAndInterest, 'Pokok + Bunga'],
    ]);
});

// ============================================================================
// JournalStatus
// ============================================================================

describe('JournalStatus', function (): void {
    it('has exactly 4 cases', function (): void {
        expect(JournalStatus::cases())->toHaveCount(4);
    });

    it('has correct backing values', function (JournalStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Draft' => [JournalStatus::Draft, 'draft'],
        'PendingApproval' => [JournalStatus::PendingApproval, 'pending_approval'],
        'Posted' => [JournalStatus::Posted, 'posted'],
        'Reversed' => [JournalStatus::Reversed, 'reversed'],
    ]);

    it('returns correct labels', function (JournalStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Draft' => [JournalStatus::Draft, 'Draft'],
        'PendingApproval' => [JournalStatus::PendingApproval, 'Menunggu Persetujuan'],
        'Posted' => [JournalStatus::Posted, 'Terposting'],
        'Reversed' => [JournalStatus::Reversed, 'Dibatalkan'],
    ]);

    it('returns correct colors', function (JournalStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Draft' => [JournalStatus::Draft, 'gray'],
        'PendingApproval' => [JournalStatus::PendingApproval, 'warning'],
        'Posted' => [JournalStatus::Posted, 'success'],
        'Reversed' => [JournalStatus::Reversed, 'danger'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (JournalStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(JournalStatus::cases());
});

// ============================================================================
// JournalSource
// ============================================================================

describe('JournalSource', function (): void {
    it('has exactly 6 cases', function (): void {
        expect(JournalSource::cases())->toHaveCount(6);
    });

    it('has correct backing values', function (JournalSource $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Manual' => [JournalSource::Manual, 'manual'],
        'System' => [JournalSource::System, 'system'],
        'Teller' => [JournalSource::Teller, 'teller'],
        'Interest' => [JournalSource::Interest, 'interest'],
        'Fee' => [JournalSource::Fee, 'fee'],
        'Eod' => [JournalSource::Eod, 'eod'],
    ]);

    it('returns correct labels', function (JournalSource $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Manual' => [JournalSource::Manual, 'Manual'],
        'System' => [JournalSource::System, 'Sistem'],
        'Teller' => [JournalSource::Teller, 'Teller'],
        'Interest' => [JournalSource::Interest, 'Bunga'],
        'Fee' => [JournalSource::Fee, 'Biaya'],
        'Eod' => [JournalSource::Eod, 'EOD'],
    ]);

    it('returns correct colors', function (JournalSource $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Manual' => [JournalSource::Manual, 'primary'],
        'System' => [JournalSource::System, 'gray'],
        'Teller' => [JournalSource::Teller, 'info'],
        'Interest' => [JournalSource::Interest, 'success'],
        'Fee' => [JournalSource::Fee, 'warning'],
        'Eod' => [JournalSource::Eod, 'danger'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (JournalSource $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(JournalSource::cases());
});

// ============================================================================
// LoanType
// ============================================================================

describe('LoanType', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(LoanType::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (LoanType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Kmk' => [LoanType::Kmk, 'kmk'],
        'Ki' => [LoanType::Ki, 'ki'],
        'Kk' => [LoanType::Kk, 'kk'],
    ]);

    it('returns correct labels', function (LoanType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Kmk' => [LoanType::Kmk, 'Kredit Modal Kerja'],
        'Ki' => [LoanType::Ki, 'Kredit Investasi'],
        'Kk' => [LoanType::Kk, 'Kredit Konsumsi'],
    ]);
});

// ============================================================================
// InterestType
// ============================================================================

describe('InterestType', function (): void {
    it('has exactly 3 cases', function (): void {
        expect(InterestType::cases())->toHaveCount(3);
    });

    it('has correct backing values', function (InterestType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Flat' => [InterestType::Flat, 'flat'],
        'Effective' => [InterestType::Effective, 'effective'],
        'Annuity' => [InterestType::Annuity, 'annuity'],
    ]);

    it('returns correct labels', function (InterestType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Flat' => [InterestType::Flat, 'Flat'],
        'Effective' => [InterestType::Effective, 'Efektif'],
        'Annuity' => [InterestType::Annuity, 'Anuitas'],
    ]);
});

// ============================================================================
// LoanStatus
// ============================================================================

describe('LoanStatus', function (): void {
    it('has exactly 6 cases', function (): void {
        expect(LoanStatus::cases())->toHaveCount(6);
    });

    it('has correct backing values', function (LoanStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Active' => [LoanStatus::Active, 'active'],
        'Current' => [LoanStatus::Current, 'current'],
        'Overdue' => [LoanStatus::Overdue, 'overdue'],
        'Restructured' => [LoanStatus::Restructured, 'restructured'],
        'WrittenOff' => [LoanStatus::WrittenOff, 'written_off'],
        'Closed' => [LoanStatus::Closed, 'closed'],
    ]);

    it('returns correct labels', function (LoanStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Active' => [LoanStatus::Active, 'Aktif'],
        'Current' => [LoanStatus::Current, 'Lancar'],
        'Overdue' => [LoanStatus::Overdue, 'Menunggak'],
        'Restructured' => [LoanStatus::Restructured, 'Restrukturisasi'],
        'WrittenOff' => [LoanStatus::WrittenOff, 'Hapus Buku'],
        'Closed' => [LoanStatus::Closed, 'Lunas'],
    ]);

    it('returns correct colors', function (LoanStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Active' => [LoanStatus::Active, 'success'],
        'Current' => [LoanStatus::Current, 'success'],
        'Overdue' => [LoanStatus::Overdue, 'danger'],
        'Restructured' => [LoanStatus::Restructured, 'warning'],
        'WrittenOff' => [LoanStatus::WrittenOff, 'gray'],
        'Closed' => [LoanStatus::Closed, 'info'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (LoanStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(LoanStatus::cases());
});

// ============================================================================
// Collectibility
// ============================================================================

describe('Collectibility', function (): void {
    it('has exactly 5 cases', function (): void {
        expect(Collectibility::cases())->toHaveCount(5);
    });

    it('has correct int backing values', function (Collectibility $case, int $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Current' => [Collectibility::Current, 1],
        'SpecialMention' => [Collectibility::SpecialMention, 2],
        'Substandard' => [Collectibility::Substandard, 3],
        'Doubtful' => [Collectibility::Doubtful, 4],
        'Loss' => [Collectibility::Loss, 5],
    ]);

    it('returns correct labels', function (Collectibility $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Current' => [Collectibility::Current, '1 - Lancar'],
        'SpecialMention' => [Collectibility::SpecialMention, '2 - Dalam Perhatian Khusus'],
        'Substandard' => [Collectibility::Substandard, '3 - Kurang Lancar'],
        'Doubtful' => [Collectibility::Doubtful, '4 - Diragukan'],
        'Loss' => [Collectibility::Loss, '5 - Macet'],
    ]);

    it('returns correct colors', function (Collectibility $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Current' => [Collectibility::Current, 'success'],
        'SpecialMention' => [Collectibility::SpecialMention, 'warning'],
        'Substandard' => [Collectibility::Substandard, 'warning'],
        'Doubtful' => [Collectibility::Doubtful, 'danger'],
        'Loss' => [Collectibility::Loss, 'gray'],
    ]);

    it('returns correct CKPN rates', function (Collectibility $case, float $rate): void {
        expect($case->ckpnRate())->toBe($rate);
    })->with([
        'Current 1%' => [Collectibility::Current, 0.01],
        'SpecialMention 5%' => [Collectibility::SpecialMention, 0.05],
        'Substandard 15%' => [Collectibility::Substandard, 0.15],
        'Doubtful 50%' => [Collectibility::Doubtful, 0.50],
        'Loss 100%' => [Collectibility::Loss, 1.00],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (Collectibility $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(Collectibility::cases());

    it('returns CKPN rates between 0 and 1 for every case', function (Collectibility $case): void {
        expect($case->ckpnRate())->toBeFloat()
            ->toBeGreaterThanOrEqual(0.0)
            ->toBeLessThanOrEqual(1.0);
    })->with(Collectibility::cases());
});

// ============================================================================
// CollateralType
// ============================================================================

describe('CollateralType', function (): void {
    it('has exactly 7 cases', function (): void {
        expect(CollateralType::cases())->toHaveCount(7);
    });

    it('has correct backing values', function (CollateralType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Land' => [CollateralType::Land, 'land'],
        'Building' => [CollateralType::Building, 'building'],
        'Vehicle' => [CollateralType::Vehicle, 'vehicle'],
        'Deposit' => [CollateralType::Deposit, 'deposit'],
        'Inventory' => [CollateralType::Inventory, 'inventory'],
        'Machinery' => [CollateralType::Machinery, 'machinery'],
        'Other' => [CollateralType::Other, 'other'],
    ]);

    it('returns correct labels', function (CollateralType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Land' => [CollateralType::Land, 'Tanah'],
        'Building' => [CollateralType::Building, 'Bangunan'],
        'Vehicle' => [CollateralType::Vehicle, 'Kendaraan'],
        'Deposit' => [CollateralType::Deposit, 'Deposito'],
        'Inventory' => [CollateralType::Inventory, 'Persediaan'],
        'Machinery' => [CollateralType::Machinery, 'Mesin/Peralatan'],
        'Other' => [CollateralType::Other, 'Lainnya'],
    ]);
});

// ============================================================================
// LoanApplicationStatus
// ============================================================================

describe('LoanApplicationStatus', function (): void {
    it('has exactly 7 cases', function (): void {
        expect(LoanApplicationStatus::cases())->toHaveCount(7);
    });

    it('has correct backing values', function (LoanApplicationStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Draft' => [LoanApplicationStatus::Draft, 'draft'],
        'Submitted' => [LoanApplicationStatus::Submitted, 'submitted'],
        'UnderReview' => [LoanApplicationStatus::UnderReview, 'under_review'],
        'Approved' => [LoanApplicationStatus::Approved, 'approved'],
        'Rejected' => [LoanApplicationStatus::Rejected, 'rejected'],
        'Disbursed' => [LoanApplicationStatus::Disbursed, 'disbursed'],
        'Cancelled' => [LoanApplicationStatus::Cancelled, 'cancelled'],
    ]);

    it('returns correct labels', function (LoanApplicationStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Draft' => [LoanApplicationStatus::Draft, 'Draft'],
        'Submitted' => [LoanApplicationStatus::Submitted, 'Diajukan'],
        'UnderReview' => [LoanApplicationStatus::UnderReview, 'Dalam Review'],
        'Approved' => [LoanApplicationStatus::Approved, 'Disetujui'],
        'Rejected' => [LoanApplicationStatus::Rejected, 'Ditolak'],
        'Disbursed' => [LoanApplicationStatus::Disbursed, 'Dicairkan'],
        'Cancelled' => [LoanApplicationStatus::Cancelled, 'Dibatalkan'],
    ]);

    it('returns correct colors', function (LoanApplicationStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Draft' => [LoanApplicationStatus::Draft, 'gray'],
        'Submitted' => [LoanApplicationStatus::Submitted, 'info'],
        'UnderReview' => [LoanApplicationStatus::UnderReview, 'warning'],
        'Approved' => [LoanApplicationStatus::Approved, 'success'],
        'Rejected' => [LoanApplicationStatus::Rejected, 'danger'],
        'Disbursed' => [LoanApplicationStatus::Disbursed, 'primary'],
        'Cancelled' => [LoanApplicationStatus::Cancelled, 'gray'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (LoanApplicationStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(LoanApplicationStatus::cases());
});

// ============================================================================
// VaultTransactionType
// ============================================================================

describe('VaultTransactionType', function (): void {
    it('has exactly 6 cases', function (): void {
        expect(VaultTransactionType::cases())->toHaveCount(6);
    });

    it('has correct backing values', function (VaultTransactionType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'InitialCash' => [VaultTransactionType::InitialCash, 'initial_cash'],
        'CashIn' => [VaultTransactionType::CashIn, 'cash_in'],
        'CashOut' => [VaultTransactionType::CashOut, 'cash_out'],
        'TellerRequest' => [VaultTransactionType::TellerRequest, 'teller_request'],
        'TellerReturn' => [VaultTransactionType::TellerReturn, 'teller_return'],
        'Adjustment' => [VaultTransactionType::Adjustment, 'adjustment'],
    ]);

    it('returns correct labels', function (VaultTransactionType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'InitialCash' => [VaultTransactionType::InitialCash, 'Saldo Awal'],
        'CashIn' => [VaultTransactionType::CashIn, 'Kas Masuk'],
        'CashOut' => [VaultTransactionType::CashOut, 'Kas Keluar'],
        'TellerRequest' => [VaultTransactionType::TellerRequest, 'Permintaan Teller'],
        'TellerReturn' => [VaultTransactionType::TellerReturn, 'Pengembalian Teller'],
        'Adjustment' => [VaultTransactionType::Adjustment, 'Penyesuaian'],
    ]);

    it('returns correct colors', function (VaultTransactionType $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'InitialCash' => [VaultTransactionType::InitialCash, 'gray'],
        'CashIn' => [VaultTransactionType::CashIn, 'success'],
        'CashOut' => [VaultTransactionType::CashOut, 'danger'],
        'TellerRequest' => [VaultTransactionType::TellerRequest, 'danger'],
        'TellerReturn' => [VaultTransactionType::TellerReturn, 'success'],
        'Adjustment' => [VaultTransactionType::Adjustment, 'warning'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (VaultTransactionType $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(VaultTransactionType::cases());
});

// ============================================================================
// TellerTransactionType
// ============================================================================

describe('TellerTransactionType', function (): void {
    it('has exactly 7 cases', function (): void {
        expect(TellerTransactionType::cases())->toHaveCount(7);
    });

    it('has correct backing values', function (TellerTransactionType $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'SavingsDeposit' => [TellerTransactionType::SavingsDeposit, 'savings_deposit'],
        'SavingsWithdrawal' => [TellerTransactionType::SavingsWithdrawal, 'savings_withdrawal'],
        'LoanPayment' => [TellerTransactionType::LoanPayment, 'loan_payment'],
        'CashRequest' => [TellerTransactionType::CashRequest, 'cash_request'],
        'CashReturn' => [TellerTransactionType::CashReturn, 'cash_return'],
        'Transfer' => [TellerTransactionType::Transfer, 'transfer'],
        'Other' => [TellerTransactionType::Other, 'other'],
    ]);

    it('returns correct labels', function (TellerTransactionType $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'SavingsDeposit' => [TellerTransactionType::SavingsDeposit, 'Setor Tabungan'],
        'SavingsWithdrawal' => [TellerTransactionType::SavingsWithdrawal, 'Tarik Tabungan'],
        'LoanPayment' => [TellerTransactionType::LoanPayment, 'Bayar Angsuran'],
        'CashRequest' => [TellerTransactionType::CashRequest, 'Permintaan Kas'],
        'CashReturn' => [TellerTransactionType::CashReturn, 'Pengembalian Kas'],
        'Transfer' => [TellerTransactionType::Transfer, 'Transfer'],
        'Other' => [TellerTransactionType::Other, 'Lainnya'],
    ]);

    it('returns correct colors', function (TellerTransactionType $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'SavingsDeposit' => [TellerTransactionType::SavingsDeposit, 'success'],
        'SavingsWithdrawal' => [TellerTransactionType::SavingsWithdrawal, 'danger'],
        'LoanPayment' => [TellerTransactionType::LoanPayment, 'success'],
        'CashRequest' => [TellerTransactionType::CashRequest, 'danger'],
        'CashReturn' => [TellerTransactionType::CashReturn, 'success'],
        'Transfer' => [TellerTransactionType::Transfer, 'info'],
        'Other' => [TellerTransactionType::Other, 'gray'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (TellerTransactionType $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(TellerTransactionType::cases());
});

// ============================================================================
// TellerSessionStatus
// ============================================================================

describe('TellerSessionStatus', function (): void {
    it('has exactly 2 cases', function (): void {
        expect(TellerSessionStatus::cases())->toHaveCount(2);
    });

    it('has correct backing values', function (TellerSessionStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Open' => [TellerSessionStatus::Open, 'open'],
        'Closed' => [TellerSessionStatus::Closed, 'closed'],
    ]);

    it('returns correct labels', function (TellerSessionStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Open' => [TellerSessionStatus::Open, 'Aktif'],
        'Closed' => [TellerSessionStatus::Closed, 'Ditutup'],
    ]);

    it('returns correct colors', function (TellerSessionStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Open' => [TellerSessionStatus::Open, 'success'],
        'Closed' => [TellerSessionStatus::Closed, 'gray'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (TellerSessionStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(TellerSessionStatus::cases());
});

// ============================================================================
// EodStatus
// ============================================================================

describe('EodStatus', function (): void {
    it('has exactly 4 cases', function (): void {
        expect(EodStatus::cases())->toHaveCount(4);
    });

    it('has correct backing values', function (EodStatus $case, string $value): void {
        expect($case->value)->toBe($value);
    })->with([
        'Pending' => [EodStatus::Pending, 'pending'],
        'Running' => [EodStatus::Running, 'running'],
        'Completed' => [EodStatus::Completed, 'completed'],
        'Failed' => [EodStatus::Failed, 'failed'],
    ]);

    it('returns correct labels', function (EodStatus $case, string $label): void {
        expect($case->getLabel())->toBe($label);
    })->with([
        'Pending' => [EodStatus::Pending, 'Menunggu'],
        'Running' => [EodStatus::Running, 'Berjalan'],
        'Completed' => [EodStatus::Completed, 'Selesai'],
        'Failed' => [EodStatus::Failed, 'Gagal'],
    ]);

    it('returns correct colors', function (EodStatus $case, string $color): void {
        expect($case->getColor())->toBe($color);
    })->with([
        'Pending' => [EodStatus::Pending, 'gray'],
        'Running' => [EodStatus::Running, 'warning'],
        'Completed' => [EodStatus::Completed, 'success'],
        'Failed' => [EodStatus::Failed, 'danger'],
    ]);

    it('returns non-empty strings for getLabel and getColor on every case', function (EodStatus $case): void {
        expect($case->getLabel())->toBeString()->not->toBeEmpty()
            ->and($case->getColor())->toBeString()->not->toBeEmpty();
    })->with(EodStatus::cases());
});
