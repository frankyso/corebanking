<?php

use App\Enums\EodStatus;
use App\Enums\Gender;
use App\Enums\InterestCalcMethod;
use App\Enums\InterestType;
use App\Enums\LoanType;
use App\Enums\MaritalStatus;
use App\Enums\SavingsTransactionType;
use App\Enums\TellerTransactionType;
use App\Enums\VaultTransactionType;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerDocument;
use App\Models\CustomerPhone;
use App\Models\DepositAccount;
use App\Models\DepositInterestAccrual;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\DepositTransaction;
use App\Models\EodProcess;
use App\Models\EodProcessStep;
use App\Models\GlBalance;
use App\Models\GlDailyBalance;
use App\Models\Holiday;
use App\Models\IndividualDetail;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\LoanAccount;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsInterestAccrual;
use App\Models\SavingsProduct;
use App\Models\SavingsTransaction;
use App\Models\Sequence;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Carbon\Carbon;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
});

// ============================================================================
// Holiday
// ============================================================================
describe('Holiday', function (): void {
    it('casts date to Carbon', function (): void {
        $holiday = Holiday::factory()->create(['date' => '2026-01-01']);
        expect($holiday->date)->toBeInstanceOf(Carbon::class);
    });

    it('isHoliday returns true for weekends', function (): void {
        $saturday = Carbon::parse('2026-03-14'); // Saturday
        expect(Holiday::isHoliday($saturday))->toBeTrue();
    });

    it('isHoliday returns true for a holiday in the database', function (): void {
        $date = Carbon::parse('2026-03-12'); // Thursday
        Holiday::factory()->create(['date' => $date->toDateString()]);
        expect(Holiday::isHoliday($date))->toBeTrue();
    });

    it('isHoliday returns false for a regular business day', function (): void {
        $wednesday = Carbon::parse('2026-03-11'); // Wednesday
        expect(Holiday::isHoliday($wednesday))->toBeFalse();
    });

    it('getNextBusinessDay skips weekends', function (): void {
        $friday = Carbon::parse('2026-03-13'); // Friday
        $next = Holiday::getNextBusinessDay($friday);
        expect($next->isWeekday())->toBeTrue()
            ->and($next->format('Y-m-d'))->toBe('2026-03-16'); // Monday
    });

    it('getNextBusinessDay skips holidays', function (): void {
        $wednesday = Carbon::parse('2026-03-11');
        Holiday::factory()->create(['date' => '2026-03-12']); // Thursday is holiday
        $next = Holiday::getNextBusinessDay($wednesday);
        expect($next->format('Y-m-d'))->toBe('2026-03-13'); // Friday
    });
});

// ============================================================================
// HasMicrosecondTimestamps Trait
// ============================================================================
describe('HasMicrosecondTimestamps', function (): void {
    it('uses microsecond date format', function (): void {
        $model = new Holiday;
        expect($model->getDateFormat())->toBe('Y-m-d H:i:s.u');
    });

    it('stores date-cast columns as Y-m-d format', function (): void {
        $holiday = Holiday::factory()->create(['date' => '2026-06-15']);
        expect($holiday->getAttributes()['date'])->toBe('2026-06-15');
    });
});

// ============================================================================
// VaultTransaction
// ============================================================================
describe('VaultTransaction', function (): void {
    it('casts transaction_type to VaultTransactionType enum', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id, 'custodian_id' => $this->user->id]);
        $txn = VaultTransaction::create([
            'reference_number' => 'VT-GAP-001',
            'vault_id' => $vault->id,
            'transaction_type' => 'cash_in',
            'amount' => 1000000,
            'balance_before' => 5000000,
            'balance_after' => 6000000,
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->transaction_type)->toBeInstanceOf(VaultTransactionType::class);
    });

    it('casts amount fields to decimal', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id, 'custodian_id' => $this->user->id]);
        $txn = VaultTransaction::create([
            'reference_number' => 'VT-GAP-002',
            'vault_id' => $vault->id,
            'transaction_type' => 'cash_in',
            'amount' => 1000000.50,
            'balance_before' => 5000000.25,
            'balance_after' => 6000000.75,
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->amount)->toBeString()
            ->and($txn->balance_before)->toBeString()
            ->and($txn->balance_after)->toBeString();
    });

    it('belongs to vault', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id, 'custodian_id' => $this->user->id]);
        $txn = VaultTransaction::create([
            'reference_number' => 'VT-GAP-003',
            'vault_id' => $vault->id,
            'transaction_type' => 'cash_in',
            'amount' => 1000000,
            'balance_before' => 5000000,
            'balance_after' => 6000000,
            'performed_by' => $this->user->id,
        ]);
        expect($txn->vault)->toBeInstanceOf(Vault::class)
            ->and($txn->vault->id)->toBe($vault->id);
    });

    it('belongs to performer', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id, 'custodian_id' => $this->user->id]);
        $txn = VaultTransaction::create([
            'reference_number' => 'VT-GAP-004',
            'vault_id' => $vault->id,
            'transaction_type' => 'cash_in',
            'amount' => 1000000,
            'balance_before' => 5000000,
            'balance_after' => 6000000,
            'performed_by' => $this->user->id,
        ]);
        expect($txn->performer)->toBeInstanceOf(User::class)
            ->and($txn->performer->id)->toBe($this->user->id);
    });

    it('belongs to approver', function (): void {
        $approver = User::factory()->create();
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id, 'custodian_id' => $this->user->id]);
        $txn = VaultTransaction::create([
            'reference_number' => 'VT-GAP-005',
            'vault_id' => $vault->id,
            'transaction_type' => 'cash_in',
            'amount' => 1000000,
            'balance_before' => 5000000,
            'balance_after' => 6000000,
            'performed_by' => $this->user->id,
            'approved_by' => $approver->id,
        ]);
        expect($txn->approver)->toBeInstanceOf(User::class)
            ->and($txn->approver->id)->toBe($approver->id);
    });
});

// ============================================================================
// TellerTransaction
// ============================================================================
describe('TellerTransaction', function (): void {
    it('casts transaction_type to enum', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-001',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 10500000,
            'direction' => 'in',
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->transaction_type)->toBeInstanceOf(TellerTransactionType::class);
    });

    it('casts boolean fields', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-002',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 10500000,
            'direction' => 'in',
            'is_reversed' => false,
            'needs_authorization' => true,
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->is_reversed)->toBeFalse()->toBeBool()
            ->and($txn->needs_authorization)->toBeTrue()->toBeBool();
    });

    it('casts authorized_at to datetime', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-003',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_withdrawal',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 9500000,
            'direction' => 'out',
            'authorized_at' => now(),
            'authorized_by' => $this->user->id,
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->authorized_at)->toBeInstanceOf(Carbon::class);
    });

    it('isCashIn returns true for in direction', function (): void {
        $txn = new TellerTransaction(['direction' => 'in']);
        expect($txn->isCashIn())->toBeTrue()
            ->and($txn->isCashOut())->toBeFalse();
    });

    it('isCashOut returns true for out direction', function (): void {
        $txn = new TellerTransaction(['direction' => 'out']);
        expect($txn->isCashOut())->toBeTrue()
            ->and($txn->isCashIn())->toBeFalse();
    });

    it('belongs to tellerSession', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-004',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 10500000,
            'direction' => 'in',
            'performed_by' => $this->user->id,
        ]);
        expect($txn->tellerSession)->toBeInstanceOf(TellerSession::class)
            ->and($txn->tellerSession->id)->toBe($session->id);
    });

    it('belongs to customer', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-005',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 10500000,
            'direction' => 'in',
            'customer_id' => $customer->id,
            'performed_by' => $this->user->id,
        ]);
        expect($txn->customer)->toBeInstanceOf(Customer::class)
            ->and($txn->customer->id)->toBe($customer->id);
    });

    it('belongs to performer', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-006',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 10500000,
            'direction' => 'in',
            'performed_by' => $this->user->id,
        ]);
        expect($txn->performer)->toBeInstanceOf(User::class)
            ->and($txn->performer->id)->toBe($this->user->id);
    });

    it('belongs to authorizer', function (): void {
        $authorizer = User::factory()->create();
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT-GAP-007',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_withdrawal',
            'amount' => 5000000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 5000000,
            'direction' => 'out',
            'needs_authorization' => true,
            'authorized_by' => $authorizer->id,
            'authorized_at' => now(),
            'performed_by' => $this->user->id,
        ]);
        expect($txn->authorizer)->toBeInstanceOf(User::class)
            ->and($txn->authorizer->id)->toBe($authorizer->id);
    });

    it('belongs to reversedBy', function (): void {
        $session = TellerSession::factory()->create();
        $original = TellerTransaction::create([
            'reference_number' => 'TT-GAP-008',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 10500000,
            'direction' => 'in',
            'performed_by' => $this->user->id,
        ]);
        $reversal = TellerTransaction::create([
            'reference_number' => 'TT-GAP-009',
            'teller_session_id' => $session->id,
            'transaction_type' => 'savings_deposit',
            'amount' => 500000,
            'teller_balance_before' => 10500000,
            'teller_balance_after' => 10000000,
            'direction' => 'out',
            'reversed_by_id' => $original->id,
            'performed_by' => $this->user->id,
        ]);
        expect($reversal->reversedBy)->toBeInstanceOf(TellerTransaction::class)
            ->and($reversal->reversedBy->id)->toBe($original->id);
    });
});

// ============================================================================
// Branch
// ============================================================================
describe('Branch', function (): void {
    it('active scope filters active branches', function (): void {
        $active = Branch::factory()->create(['is_active' => true]);
        $inactive = Branch::factory()->create(['is_active' => false]);

        $results = Branch::query()->active()->pluck('id');
        expect($results)->toContain($active->id)
            ->and($results)->not->toContain($inactive->id);
    });

    it('casts is_head_office to boolean', function (): void {
        $branch = Branch::factory()->create(['is_head_office' => true]);
        expect($branch->is_head_office)->toBeTrue()->toBeBool();
    });

    it('casts is_active to boolean', function (): void {
        $branch = Branch::factory()->create(['is_active' => false]);
        expect($branch->is_active)->toBeFalse()->toBeBool();
    });

    it('belongs to head user', function (): void {
        $head = User::factory()->create();
        $branch = Branch::factory()->create(['head_id' => $head->id]);
        expect($branch->head)->toBeInstanceOf(User::class)
            ->and($branch->head->id)->toBe($head->id);
    });

    it('has many users', function (): void {
        User::factory()->count(3)->create(['branch_id' => $this->branch->id]);

        // +1 from beforeEach user
        expect($this->branch->users->count())->toBeGreaterThanOrEqual(4);
    });
});

// ============================================================================
// SavingsProduct
// ============================================================================
describe('SavingsProduct', function (): void {
    it('casts interest_calc_method to enum', function (): void {
        $product = SavingsProduct::factory()->create(['interest_calc_method' => 'daily_balance']);
        $product->refresh();
        expect($product->interest_calc_method)->toBeInstanceOf(InterestCalcMethod::class);
    });

    it('casts numeric fields to decimal', function (): void {
        $product = SavingsProduct::factory()->create([
            'interest_rate' => 3.50000,
            'min_opening_balance' => 50000.00,
            'admin_fee_monthly' => 5000.00,
        ]);
        $product->refresh();
        expect($product->interest_rate)->toBeString()
            ->and($product->min_opening_balance)->toBeString()
            ->and($product->admin_fee_monthly)->toBeString();
    });

    it('has many accounts', function (): void {
        $product = SavingsProduct::factory()->create();
        SavingsAccount::factory()->count(2)->create(['savings_product_id' => $product->id]);

        expect($product->accounts)->toHaveCount(2);
    });

    it('belongs to GL savings account', function (): void {
        $glAccount = ChartOfAccount::factory()->liability()->create();
        $product = SavingsProduct::factory()->create(['gl_savings_id' => $glAccount->id]);
        expect($product->glSavings)->toBeInstanceOf(ChartOfAccount::class)
            ->and($product->glSavings->id)->toBe($glAccount->id);
    });

    it('belongs to GL interest expense account', function (): void {
        $glAccount = ChartOfAccount::factory()->expense()->create();
        $product = SavingsProduct::factory()->create(['gl_interest_expense_id' => $glAccount->id]);
        expect($product->glInterestExpense)->toBeInstanceOf(ChartOfAccount::class)
            ->and($product->glInterestExpense->id)->toBe($glAccount->id);
    });

    it('belongs to GL interest payable account', function (): void {
        $glAccount = ChartOfAccount::factory()->liability()->create();
        $product = SavingsProduct::factory()->create(['gl_interest_payable_id' => $glAccount->id]);
        expect($product->glInterestPayable)->toBeInstanceOf(ChartOfAccount::class);
    });

    it('belongs to GL admin fee income account', function (): void {
        $glAccount = ChartOfAccount::factory()->revenue()->create();
        $product = SavingsProduct::factory()->create(['gl_admin_fee_income_id' => $glAccount->id]);
        expect($product->glAdminFeeIncome)->toBeInstanceOf(ChartOfAccount::class);
    });

    it('belongs to GL tax payable account', function (): void {
        $glAccount = ChartOfAccount::factory()->liability()->create();
        $product = SavingsProduct::factory()->create(['gl_tax_payable_id' => $glAccount->id]);
        expect($product->glTaxPayable)->toBeInstanceOf(ChartOfAccount::class);
    });

    it('active scope works', function (): void {
        $active = SavingsProduct::factory()->create(['is_active' => true]);
        $inactive = SavingsProduct::factory()->create(['is_active' => false]);

        $results = SavingsProduct::query()->active()->pluck('id');
        expect($results)->toContain($active->id)
            ->and($results)->not->toContain($inactive->id);
    });
});

// ============================================================================
// LoanProduct
// ============================================================================
describe('LoanProduct', function (): void {
    it('casts loan_type to LoanType enum', function (): void {
        $product = LoanProduct::factory()->create(['loan_type' => 'kmk']);
        $product->refresh();
        expect($product->loan_type)->toBeInstanceOf(LoanType::class);
    });

    it('casts interest_type to InterestType enum', function (): void {
        $product = LoanProduct::factory()->create(['interest_type' => 'annuity']);
        $product->refresh();
        expect($product->interest_type)->toBeInstanceOf(InterestType::class);
    });

    it('casts decimal fields to string', function (): void {
        $product = LoanProduct::factory()->create([
            'min_amount' => 1000000.00,
            'interest_rate' => 12.50000,
            'penalty_rate' => 0.50000,
        ]);
        $product->refresh();
        expect($product->min_amount)->toBeString()
            ->and($product->interest_rate)->toBeString()
            ->and($product->penalty_rate)->toBeString();
    });

    it('has many accounts', function (): void {
        $product = LoanProduct::factory()->create();
        LoanAccount::factory()->count(2)->create([
            'loan_product_id' => $product->id,
            'customer_id' => Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        expect($product->accounts)->toHaveCount(2);
    });

    it('belongs to GL loan account', function (): void {
        $glAccount = ChartOfAccount::factory()->asset()->create();
        $product = LoanProduct::factory()->create(['gl_loan_id' => $glAccount->id]);
        expect($product->glLoan)->toBeInstanceOf(ChartOfAccount::class)
            ->and($product->glLoan->id)->toBe($glAccount->id);
    });

    it('belongs to GL interest income account', function (): void {
        $glAccount = ChartOfAccount::factory()->revenue()->create();
        $product = LoanProduct::factory()->create(['gl_interest_income_id' => $glAccount->id]);
        expect($product->glInterestIncome)->toBeInstanceOf(ChartOfAccount::class)
            ->and($product->glInterestIncome->id)->toBe($glAccount->id);
    });

    it('active scope works', function (): void {
        $active = LoanProduct::factory()->create(['is_active' => true]);
        $inactive = LoanProduct::factory()->create(['is_active' => false]);

        $results = LoanProduct::query()->active()->pluck('id');
        expect($results)->toContain($active->id)
            ->and($results)->not->toContain($inactive->id);
    });
});

// ============================================================================
// GlDailyBalance
// ============================================================================
describe('GlDailyBalance', function (): void {
    it('casts balance_date to Carbon', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $daily = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 1000000,
            'debit_total' => 500000,
            'credit_total' => 200000,
            'closing_balance' => 1300000,
        ]);
        $daily->refresh();
        expect($daily->balance_date)->toBeInstanceOf(Carbon::class);
    });

    it('casts decimal fields', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $daily = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 1000000.50,
            'debit_total' => 500000.25,
            'credit_total' => 200000.75,
            'closing_balance' => 1300000.00,
        ]);
        $daily->refresh();
        expect($daily->opening_balance)->toBeString()
            ->and($daily->debit_total)->toBeString()
            ->and($daily->credit_total)->toBeString()
            ->and($daily->closing_balance)->toBeString();
    });

    it('forDate scope filters by date', function (): void {
        $coa = ChartOfAccount::factory()->create();
        GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 1000000,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 1000000,
        ]);
        GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-11',
            'opening_balance' => 2000000,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 2000000,
        ]);

        $results = GlDailyBalance::query()->forDate('2026-03-10')->get();
        expect($results)->toHaveCount(1)
            ->and($results->first()->opening_balance)->toBe('1000000.00');
    });

    it('belongs to chartOfAccount', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $daily = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 0,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 0,
        ]);
        expect($daily->chartOfAccount)->toBeInstanceOf(ChartOfAccount::class)
            ->and($daily->chartOfAccount->id)->toBe($coa->id);
    });

    it('belongs to branch', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $daily = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'balance_date' => '2026-03-10',
            'opening_balance' => 0,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 0,
        ]);
        expect($daily->branch)->toBeInstanceOf(Branch::class)
            ->and($daily->branch->id)->toBe($this->branch->id);
    });
});

// ============================================================================
// CustomerAddress
// ============================================================================
describe('CustomerAddress', function (): void {
    it('casts is_primary to boolean', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'home',
            'address' => 'Jl. Test No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'is_primary' => true,
        ]);
        $address->refresh();
        expect($address->is_primary)->toBeTrue()->toBeBool();
    });

    it('belongs to customer', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'home',
            'address' => 'Jl. Test No. 2',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'is_primary' => false,
        ]);
        expect($address->customer)->toBeInstanceOf(Customer::class)
            ->and($address->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// CustomerDocument
// ============================================================================
describe('CustomerDocument', function (): void {
    it('casts expiry_date to Carbon', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $doc = CustomerDocument::create([
            'customer_id' => $customer->id,
            'type' => 'ktp',
            'document_number' => '1234567890',
            'expiry_date' => '2030-12-31',
            'is_verified' => false,
        ]);
        $doc->refresh();
        expect($doc->expiry_date)->toBeInstanceOf(Carbon::class);
    });

    it('casts is_verified to boolean', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $doc = CustomerDocument::create([
            'customer_id' => $customer->id,
            'type' => 'ktp',
            'document_number' => '0987654321',
            'is_verified' => true,
        ]);
        $doc->refresh();
        expect($doc->is_verified)->toBeTrue()->toBeBool();
    });

    it('belongs to customer', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $doc = CustomerDocument::create([
            'customer_id' => $customer->id,
            'type' => 'npwp',
            'document_number' => '111222333',
            'is_verified' => false,
        ]);
        expect($doc->customer)->toBeInstanceOf(Customer::class)
            ->and($doc->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// CustomerPhone
// ============================================================================
describe('CustomerPhone', function (): void {
    it('casts is_primary to boolean', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $phone = CustomerPhone::create([
            'customer_id' => $customer->id,
            'type' => 'mobile',
            'number' => '081234567890',
            'is_primary' => true,
        ]);
        $phone->refresh();
        expect($phone->is_primary)->toBeTrue()->toBeBool();
    });

    it('belongs to customer', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $phone = CustomerPhone::create([
            'customer_id' => $customer->id,
            'type' => 'office',
            'number' => '02112345678',
            'is_primary' => false,
        ]);
        expect($phone->customer)->toBeInstanceOf(Customer::class)
            ->and($phone->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// DepositInterestAccrual
// ============================================================================
describe('DepositInterestAccrual', function (): void {
    it('casts date and boolean fields', function (): void {
        $account = DepositAccount::factory()->create();
        $accrual = DepositInterestAccrual::create([
            'deposit_account_id' => $account->id,
            'accrual_date' => '2026-03-10',
            'principal' => 10000000,
            'interest_rate' => 5.50000,
            'accrued_amount' => 15068.49,
            'tax_amount' => 3013.70,
            'is_posted' => false,
        ]);
        $accrual->refresh();
        expect($accrual->accrual_date)->toBeInstanceOf(Carbon::class)
            ->and($accrual->is_posted)->toBeFalse()->toBeBool()
            ->and($accrual->principal)->toBeString();
    });

    it('belongs to depositAccount', function (): void {
        $account = DepositAccount::factory()->create();
        $accrual = DepositInterestAccrual::create([
            'deposit_account_id' => $account->id,
            'accrual_date' => '2026-03-10',
            'principal' => 10000000,
            'interest_rate' => 5.50,
            'accrued_amount' => 15068.49,
            'tax_amount' => 3013.70,
            'is_posted' => false,
        ]);
        expect($accrual->depositAccount)->toBeInstanceOf(DepositAccount::class)
            ->and($accrual->depositAccount->id)->toBe($account->id);
    });
});

// ============================================================================
// DepositProductRate
// ============================================================================
describe('DepositProductRate', function (): void {
    it('casts decimal and boolean fields', function (): void {
        $product = DepositProduct::factory()->create();
        $rate = DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 3,
            'min_amount' => 1000000.00,
            'max_amount' => 50000000.00,
            'interest_rate' => 4.50000,
            'is_active' => true,
        ]);
        $rate->refresh();
        expect($rate->min_amount)->toBeString()
            ->and($rate->interest_rate)->toBeString()
            ->and($rate->is_active)->toBeTrue()->toBeBool();
    });

    it('belongs to depositProduct', function (): void {
        $product = DepositProduct::factory()->create();
        $rate = DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 6,
            'min_amount' => 1000000,
            'interest_rate' => 5.00,
            'is_active' => true,
        ]);
        expect($rate->depositProduct)->toBeInstanceOf(DepositProduct::class)
            ->and($rate->depositProduct->id)->toBe($product->id);
    });
});

// ============================================================================
// DepositTransaction
// ============================================================================
describe('DepositTransaction', function (): void {
    it('casts transaction_date to date and amount to decimal', function (): void {
        $account = DepositAccount::factory()->create();
        $txn = DepositTransaction::create([
            'reference_number' => 'DT-GAP-001',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'placement',
            'amount' => 10000000.50,
            'transaction_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->transaction_date)->toBeInstanceOf(Carbon::class)
            ->and($txn->amount)->toBeString();
    });

    it('belongs to depositAccount', function (): void {
        $account = DepositAccount::factory()->create();
        $txn = DepositTransaction::create([
            'reference_number' => 'DT-GAP-002',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'placement',
            'amount' => 10000000,
            'transaction_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        expect($txn->depositAccount)->toBeInstanceOf(DepositAccount::class)
            ->and($txn->depositAccount->id)->toBe($account->id);
    });

    it('belongs to performer', function (): void {
        $account = DepositAccount::factory()->create();
        $txn = DepositTransaction::create([
            'reference_number' => 'DT-GAP-003',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'placement',
            'amount' => 10000000,
            'transaction_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        expect($txn->performer)->toBeInstanceOf(User::class)
            ->and($txn->performer->id)->toBe($this->user->id);
    });
});

// ============================================================================
// EodProcess - casts coverage
// ============================================================================
describe('EodProcess casts', function (): void {
    it('casts process_date to Carbon and status to enum', function (): void {
        $eod = EodProcess::create([
            'process_date' => '2026-03-10',
            'status' => 'pending',
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        $eod->refresh();
        expect($eod->process_date)->toBeInstanceOf(Carbon::class)
            ->and($eod->status)->toBeInstanceOf(EodStatus::class);
    });

    it('casts started_at and completed_at to datetime', function (): void {
        $eod = EodProcess::create([
            'process_date' => '2026-03-10',
            'status' => 'completed',
            'total_steps' => 5,
            'completed_steps' => 5,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now(),
            'started_by' => $this->user->id,
        ]);
        $eod->refresh();
        expect($eod->started_at)->toBeInstanceOf(Carbon::class)
            ->and($eod->completed_at)->toBeInstanceOf(Carbon::class);
    });

    it('startedBy belongs to user', function (): void {
        $eod = EodProcess::create([
            'process_date' => '2026-03-10',
            'status' => 'pending',
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        expect($eod->startedBy)->toBeInstanceOf(User::class)
            ->and($eod->startedBy->id)->toBe($this->user->id);
    });
});

// ============================================================================
// EodProcessStep - casts coverage
// ============================================================================
describe('EodProcessStep casts', function (): void {
    it('casts status to EodStatus enum', function (): void {
        $eod = EodProcess::create([
            'process_date' => '2026-03-10',
            'status' => 'running',
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Test Step',
            'status' => 'pending',
        ]);
        $step->refresh();
        expect($step->status)->toBeInstanceOf(EodStatus::class);
    });

    it('eodProcess belongs to EodProcess', function (): void {
        $eod = EodProcess::create([
            'process_date' => '2026-03-10',
            'status' => 'running',
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $this->user->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Test Step',
            'status' => 'pending',
        ]);
        expect($step->eodProcess)->toBeInstanceOf(EodProcess::class)
            ->and($step->eodProcess->id)->toBe($eod->id);
    });
});

// ============================================================================
// GlBalance
// ============================================================================
describe('GlBalance', function (): void {
    it('casts decimal fields', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 1000000.50,
            'debit_total' => 500000.25,
            'credit_total' => 200000.75,
            'closing_balance' => 1300000.00,
        ]);
        $balance->refresh();
        expect($balance->opening_balance)->toBeString()
            ->and($balance->debit_total)->toBeString();
    });

    it('forPeriod scope filters by year and month', function (): void {
        $coa = ChartOfAccount::factory()->create();
        GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 1000000,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 1000000,
        ]);
        GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 4,
            'opening_balance' => 2000000,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 2000000,
        ]);

        $results = GlBalance::query()->forPeriod(2026, 3)->get();
        expect($results)->toHaveCount(1)
            ->and($results->first()->opening_balance)->toBe('1000000.00');
    });

    it('belongs to chartOfAccount', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 0,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 0,
        ]);
        expect($balance->chartOfAccount)->toBeInstanceOf(ChartOfAccount::class)
            ->and($balance->chartOfAccount->id)->toBe($coa->id);
    });

    it('belongs to branch', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $this->branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 0,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 0,
        ]);
        expect($balance->branch)->toBeInstanceOf(Branch::class)
            ->and($balance->branch->id)->toBe($this->branch->id);
    });
});

// ============================================================================
// IndividualDetail
// ============================================================================
describe('IndividualDetail', function (): void {
    it('casts gender to Gender enum', function (): void {
        $detail = IndividualDetail::factory()->create(['gender' => 'M']);
        $detail->refresh();
        expect($detail->gender)->toBeInstanceOf(Gender::class);
    });

    it('casts marital_status to MaritalStatus enum', function (): void {
        $detail = IndividualDetail::factory()->create(['marital_status' => 'married']);
        $detail->refresh();
        expect($detail->marital_status)->toBeInstanceOf(MaritalStatus::class);
    });

    it('casts birth_date to Carbon', function (): void {
        $detail = IndividualDetail::factory()->create(['birth_date' => '1990-05-15']);
        $detail->refresh();
        expect($detail->birth_date)->toBeInstanceOf(Carbon::class);
    });

    it('casts monthly_income to decimal', function (): void {
        $detail = IndividualDetail::factory()->create(['monthly_income' => 15000000.50]);
        $detail->refresh();
        expect($detail->monthly_income)->toBeString();
    });

    it('belongs to customer', function (): void {
        $customer = Customer::factory()->individual()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $detail = IndividualDetail::factory()->create(['customer_id' => $customer->id]);
        expect($detail->customer)->toBeInstanceOf(Customer::class)
            ->and($detail->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// JournalEntryLine
// ============================================================================
describe('JournalEntryLine', function (): void {
    it('casts debit and credit to decimal', function (): void {
        $journal = JournalEntry::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $coa = ChartOfAccount::factory()->create();
        $line = JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Test line',
            'debit' => 1000000.50,
            'credit' => 0,
        ]);
        $line->refresh();
        expect($line->debit)->toBeString()
            ->and($line->credit)->toBeString();
    });

    it('belongs to journalEntry', function (): void {
        $journal = JournalEntry::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $coa = ChartOfAccount::factory()->create();
        $line = JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'debit' => 1000000,
            'credit' => 0,
        ]);
        expect($line->journalEntry)->toBeInstanceOf(JournalEntry::class)
            ->and($line->journalEntry->id)->toBe($journal->id);
    });

    it('belongs to chartOfAccount', function (): void {
        $journal = JournalEntry::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $coa = ChartOfAccount::factory()->create();
        $line = JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'debit' => 0,
            'credit' => 1000000,
        ]);
        expect($line->chartOfAccount)->toBeInstanceOf(ChartOfAccount::class)
            ->and($line->chartOfAccount->id)->toBe($coa->id);
    });
});

// ============================================================================
// LoanPayment
// ============================================================================
describe('LoanPayment', function (): void {
    it('casts decimal and date fields', function (): void {
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $payment = LoanPayment::create([
            'reference_number' => 'LP-GAP-001',
            'loan_account_id' => $loanAccount->id,
            'payment_type' => 'installment',
            'amount' => 1500000.00,
            'principal_portion' => 1000000.00,
            'interest_portion' => 500000.00,
            'penalty_portion' => 0,
            'payment_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        $payment->refresh();
        expect($payment->amount)->toBeString()
            ->and($payment->principal_portion)->toBeString()
            ->and($payment->payment_date)->toBeInstanceOf(Carbon::class);
    });

    it('belongs to loanAccount', function (): void {
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $payment = LoanPayment::create([
            'reference_number' => 'LP-GAP-002',
            'loan_account_id' => $loanAccount->id,
            'payment_type' => 'installment',
            'amount' => 1500000,
            'principal_portion' => 1000000,
            'interest_portion' => 500000,
            'penalty_portion' => 0,
            'payment_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        expect($payment->loanAccount)->toBeInstanceOf(LoanAccount::class)
            ->and($payment->loanAccount->id)->toBe($loanAccount->id);
    });

    it('belongs to performer', function (): void {
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $payment = LoanPayment::create([
            'reference_number' => 'LP-GAP-003',
            'loan_account_id' => $loanAccount->id,
            'payment_type' => 'installment',
            'amount' => 1500000,
            'principal_portion' => 1000000,
            'interest_portion' => 500000,
            'penalty_portion' => 0,
            'payment_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        expect($payment->performer)->toBeInstanceOf(User::class)
            ->and($payment->performer->id)->toBe($this->user->id);
    });

    it('belongs to journalEntry', function (): void {
        $journal = JournalEntry::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
        $payment = LoanPayment::create([
            'reference_number' => 'LP-GAP-004',
            'loan_account_id' => $loanAccount->id,
            'payment_type' => 'installment',
            'amount' => 1500000,
            'principal_portion' => 1000000,
            'interest_portion' => 500000,
            'penalty_portion' => 0,
            'payment_date' => '2026-03-10',
            'performed_by' => $this->user->id,
            'journal_entry_id' => $journal->id,
        ]);
        expect($payment->journalEntry)->toBeInstanceOf(JournalEntry::class)
            ->and($payment->journalEntry->id)->toBe($journal->id);
    });
});

// ============================================================================
// LoanSchedule
// ============================================================================
describe('LoanSchedule', function (): void {
    beforeEach(function (): void {
        $this->loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);
    });

    it('casts date fields to Carbon', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $this->loanAccount->id,
            'due_date' => '2026-06-15',
        ]);
        $schedule->refresh();
        expect($schedule->due_date)->toBeInstanceOf(Carbon::class);
    });

    it('casts is_paid to boolean', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $this->loanAccount->id,
            'is_paid' => false,
        ]);
        $schedule->refresh();
        expect($schedule->is_paid)->toBeFalse()->toBeBool();
    });

    it('getRemainingPrincipal calculates correctly', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $this->loanAccount->id,
            'principal_amount' => 1000000,
            'principal_paid' => 400000,
        ]);
        expect($schedule->getRemainingPrincipal())->toBe(600000.00);
    });

    it('getRemainingInterest calculates correctly', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $this->loanAccount->id,
            'interest_amount' => 100000,
            'interest_paid' => 30000,
        ]);
        expect($schedule->getRemainingInterest())->toBe(70000.00);
    });

    it('isOverdue returns true for past unpaid schedule', function (): void {
        $schedule = LoanSchedule::factory()->overdue()->create([
            'loan_account_id' => $this->loanAccount->id,
        ]);
        expect($schedule->isOverdue())->toBeTrue();
    });

    it('isOverdue returns false for paid schedule', function (): void {
        $schedule = LoanSchedule::factory()->paid()->create([
            'loan_account_id' => $this->loanAccount->id,
            'due_date' => now()->subMonth(),
        ]);
        expect($schedule->isOverdue())->toBeFalse();
    });

    it('isOverdue returns false for future unpaid schedule', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'loan_account_id' => $this->loanAccount->id,
            'due_date' => now()->addMonth(),
            'is_paid' => false,
        ]);
        expect($schedule->isOverdue())->toBeFalse();
    });

    it('belongs to loanAccount', function (): void {
        $schedule = LoanSchedule::factory()->create(['loan_account_id' => $this->loanAccount->id]);
        expect($schedule->loanAccount)->toBeInstanceOf(LoanAccount::class)
            ->and($schedule->loanAccount->id)->toBe($this->loanAccount->id);
    });
});

// ============================================================================
// SavingsInterestAccrual
// ============================================================================
describe('SavingsInterestAccrual', function (): void {
    it('casts date and boolean fields', function (): void {
        $account = SavingsAccount::factory()->create();
        $accrual = SavingsInterestAccrual::create([
            'savings_account_id' => $account->id,
            'accrual_date' => '2026-03-10',
            'balance' => 10000000,
            'interest_rate' => 3.50000,
            'accrued_amount' => 958.90,
            'tax_amount' => 191.78,
            'is_posted' => true,
            'posted_at' => '2026-03-31',
        ]);
        $accrual->refresh();
        expect($accrual->accrual_date)->toBeInstanceOf(Carbon::class)
            ->and($accrual->is_posted)->toBeTrue()->toBeBool()
            ->and($accrual->posted_at)->toBeInstanceOf(Carbon::class)
            ->and($accrual->balance)->toBeString();
    });

    it('belongs to savingsAccount', function (): void {
        $account = SavingsAccount::factory()->create();
        $accrual = SavingsInterestAccrual::create([
            'savings_account_id' => $account->id,
            'accrual_date' => '2026-03-10',
            'balance' => 10000000,
            'interest_rate' => 3.50,
            'accrued_amount' => 958.90,
            'tax_amount' => 191.78,
            'is_posted' => false,
        ]);
        expect($accrual->savingsAccount)->toBeInstanceOf(SavingsAccount::class)
            ->and($accrual->savingsAccount->id)->toBe($account->id);
    });
});

// ============================================================================
// SavingsTransaction
// ============================================================================
describe('SavingsTransaction', function (): void {
    it('casts transaction_type to enum', function (): void {
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'ST-GAP-001',
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => '2026-03-10',
            'value_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->transaction_type)->toBeInstanceOf(SavingsTransactionType::class);
    });

    it('casts date fields to Carbon', function (): void {
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'ST-GAP-002',
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => '2026-03-10',
            'value_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->transaction_date)->toBeInstanceOf(Carbon::class)
            ->and($txn->value_date)->toBeInstanceOf(Carbon::class);
    });

    it('casts is_reversed to boolean and reversed_at to datetime', function (): void {
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'ST-GAP-003',
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => '2026-03-10',
            'value_date' => '2026-03-10',
            'is_reversed' => true,
            'reversed_at' => now(),
            'reversed_by' => $this->user->id,
            'reversal_reason' => 'Kesalahan input',
            'performed_by' => $this->user->id,
        ]);
        $txn->refresh();
        expect($txn->is_reversed)->toBeTrue()->toBeBool()
            ->and($txn->reversed_at)->toBeInstanceOf(Carbon::class);
    });

    it('belongs to savingsAccount', function (): void {
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'ST-GAP-004',
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => '2026-03-10',
            'value_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        expect($txn->savingsAccount)->toBeInstanceOf(SavingsAccount::class)
            ->and($txn->savingsAccount->id)->toBe($account->id);
    });

    it('belongs to performer', function (): void {
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'ST-GAP-005',
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => '2026-03-10',
            'value_date' => '2026-03-10',
            'performed_by' => $this->user->id,
        ]);
        expect($txn->performer)->toBeInstanceOf(User::class)
            ->and($txn->performer->id)->toBe($this->user->id);
    });

    it('belongs to reverser', function (): void {
        $reverser = User::factory()->create();
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'ST-GAP-006',
            'savings_account_id' => $account->id,
            'transaction_type' => 'deposit',
            'amount' => 500000,
            'balance_before' => 1000000,
            'balance_after' => 1500000,
            'transaction_date' => '2026-03-10',
            'value_date' => '2026-03-10',
            'is_reversed' => true,
            'reversed_by' => $reverser->id,
            'reversed_at' => now(),
            'performed_by' => $this->user->id,
        ]);
        expect($txn->reverser)->toBeInstanceOf(User::class)
            ->and($txn->reverser->id)->toBe($reverser->id);
    });
});

// ============================================================================
// Sequence
// ============================================================================
describe('Sequence', function (): void {
    it('casts last_number and padding to integer', function (): void {
        $seq = Sequence::create([
            'type' => 'test_sequence',
            'prefix' => 'TST',
            'last_number' => 100,
            'padding' => 6,
        ]);
        $seq->refresh();
        expect($seq->last_number)->toBeInt()
            ->and($seq->padding)->toBeInt();
    });
});
