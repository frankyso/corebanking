<?php

use App\Actions\Accounting\CreateJournalEntry as CreateJournalEntryAction;
use App\Actions\Accounting\PostJournalEntry;
use App\Actions\Customer\RejectCustomer;
use App\Actions\Deposit\PlaceDeposit;
use App\Actions\Deposit\ProcessDepositMaturity;
use App\Actions\Loan\CreateLoanApplication as CreateLoanApplicationAction;
use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\HoldSavingsBalance;
use App\Actions\Savings\OpenSavingsAccount;
use App\Actions\Savings\UnholdSavingsBalance;
use App\DTOs\Deposit\PlaceDepositData;
use App\DTOs\Savings\OpenSavingsAccountData;
use App\Enums\ApprovalStatus;
use App\Enums\DepositStatus;
use App\Enums\InterestPaymentMethod;
use App\Enums\JournalSource;
use App\Enums\NormalBalance;
use App\Enums\RolloverType;
use App\Enums\SavingsAccountStatus;
use App\Exceptions\Accounting\InvalidJournalStatusException;
use App\Exceptions\Customer\CustomerApprovalException;
use App\Exceptions\Deposit\InvalidDepositStatusException;
use App\Exceptions\Loan\InvalidLoanAmountException;
use App\Exceptions\Savings\InvalidSavingsAccountStatusException;
use App\Exceptions\Savings\SavingsBalanceLimitException;
use App\Filament\Resources\DepositAccountResource\Pages\CreateDepositAccount;
use App\Filament\Resources\JournalEntryResource\Pages\CreateJournalEntry;
use App\Filament\Resources\LoanApplicationResource\Pages\CreateLoanApplication;
use App\Filament\Resources\SavingsAccountResource\Pages\CreateSavingsAccount;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\DepositProductRate;
use App\Models\JournalEntry;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    $this->branch = Branch::factory()->create();
    $this->user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);
    $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    $this->user->assignRole($role);
    $this->actingAs($this->user);
});

// ─── CreateSavingsAccount ──────────────────────────────────────────────────

describe('CreateSavingsAccount', function (): void {

    it('can render the create page', function (): void {
        Livewire::test(CreateSavingsAccount::class)
            ->assertOk();
    });

    it('creates savings account through action', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = SavingsProduct::factory()->create();

        $mock = Mockery::mock(OpenSavingsAccount::class);
        $mock->shouldReceive('execute')->once()->andReturn(
            SavingsAccount::factory()->create([
                'customer_id' => $customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])
        );
        app()->instance(OpenSavingsAccount::class, $mock);

        Livewire::test(CreateSavingsAccount::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'initial_deposit' => 100000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    });

    it('handles DomainException during creation', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = SavingsProduct::factory()->create();

        $mock = Mockery::mock(OpenSavingsAccount::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new SavingsBalanceLimitException('Setoran awal minimal Rp 50.000')
        );
        app()->instance(OpenSavingsAccount::class, $mock);

        Livewire::test(CreateSavingsAccount::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'savings_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'initial_deposit' => 1000,
            ])
            ->call('create')
            ->assertNotified();
    });
});

// ─── CreateDepositAccount ──────────────────────────────────────────────────

describe('CreateDepositAccount', function (): void {

    it('can render the create page', function (): void {
        Livewire::test(CreateDepositAccount::class)
            ->assertOk();
    });

    it('creates deposit through action', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = DepositProduct::factory()->create();

        $mock = Mockery::mock(PlaceDeposit::class);
        $mock->shouldReceive('execute')->once()->andReturn(
            DepositAccount::factory()->create([
                'customer_id' => $customer->id,
                'deposit_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])
        );
        app()->instance(PlaceDeposit::class, $mock);

        Livewire::test(CreateDepositAccount::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'deposit_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'principal_amount' => 10000000,
                'tenor_months' => 12,
                'interest_payment_method' => InterestPaymentMethod::Monthly->value,
                'rollover_type' => RolloverType::None->value,
                'placement_date' => now()->toDateString(),
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    });

    it('handles DomainException during creation', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = DepositProduct::factory()->create();

        $mock = Mockery::mock(PlaceDeposit::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new InvalidDepositStatusException('Nominal tidak memenuhi syarat')
        );
        app()->instance(PlaceDeposit::class, $mock);

        Livewire::test(CreateDepositAccount::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'deposit_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'principal_amount' => 100,
                'tenor_months' => 12,
                'interest_payment_method' => InterestPaymentMethod::Monthly->value,
                'rollover_type' => RolloverType::None->value,
                'placement_date' => now()->toDateString(),
            ])
            ->call('create')
            ->assertNotified();
    });
});

// ─── CreateJournalEntry ────────────────────────────────────────────────────

describe('CreateJournalEntry', function (): void {

    it('can render the create page', function (): void {
        Livewire::test(CreateJournalEntry::class)
            ->assertOk();
    });

    it('creates journal entry through action', function (): void {
        $debitAccount = ChartOfAccount::factory()->asset()->create();
        $creditAccount = ChartOfAccount::factory()->liability()->create();

        $mock = Mockery::mock(CreateJournalEntryAction::class);
        $mock->shouldReceive('execute')->once()->andReturn(
            JournalEntry::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])
        );
        app()->instance(CreateJournalEntryAction::class, $mock);

        Livewire::test(CreateJournalEntry::class)
            ->fillForm([
                'journal_date' => now()->toDateString(),
                'description' => 'Jurnal test',
                'source' => JournalSource::Manual->value,
                'branch_id' => $this->branch->id,
                'lines' => [
                    [
                        'account_id' => $debitAccount->id,
                        'description' => 'Debit line',
                        'debit' => 100000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'description' => 'Credit line',
                        'debit' => 0,
                        'credit' => 100000,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    });

    it('handles DomainException during creation', function (): void {
        $debitAccount = ChartOfAccount::factory()->asset()->create();
        $creditAccount = ChartOfAccount::factory()->liability()->create();

        $mock = Mockery::mock(CreateJournalEntryAction::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new InvalidJournalStatusException('Total debit dan kredit tidak seimbang')
        );
        app()->instance(CreateJournalEntryAction::class, $mock);

        Livewire::test(CreateJournalEntry::class)
            ->fillForm([
                'journal_date' => now()->toDateString(),
                'description' => 'Jurnal test gagal',
                'source' => JournalSource::Manual->value,
                'branch_id' => $this->branch->id,
                'lines' => [
                    [
                        'account_id' => $debitAccount->id,
                        'description' => 'Debit',
                        'debit' => 100000,
                        'credit' => 0,
                    ],
                    [
                        'account_id' => $creditAccount->id,
                        'description' => 'Credit',
                        'debit' => 0,
                        'credit' => 50000,
                    ],
                ],
            ])
            ->call('create')
            ->assertNotified();
    });
});

// ─── CreateLoanApplication ─────────────────────────────────────────────────

describe('CreateLoanApplication', function (): void {

    it('can render the create page', function (): void {
        Livewire::test(CreateLoanApplication::class)
            ->assertOk();
    });

    it('creates loan application through action', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = LoanProduct::factory()->create();

        $mock = Mockery::mock(CreateLoanApplicationAction::class);
        $mock->shouldReceive('execute')->once()->andReturn(
            LoanApplication::factory()->create([
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
            ])
        );
        app()->instance(CreateLoanApplicationAction::class, $mock);

        Livewire::test(CreateLoanApplication::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'requested_amount' => 50000000,
                'requested_tenor_months' => 24,
                'purpose' => 'Modal usaha',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
    });

    it('handles DomainException during creation', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = LoanProduct::factory()->create();

        $mock = Mockery::mock(CreateLoanApplicationAction::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new InvalidLoanAmountException('Jumlah pinjaman melebihi batas')
        );
        app()->instance(CreateLoanApplicationAction::class, $mock);

        Livewire::test(CreateLoanApplication::class)
            ->fillForm([
                'customer_id' => $customer->id,
                'loan_product_id' => $product->id,
                'branch_id' => $this->branch->id,
                'requested_amount' => 999999999,
                'requested_tenor_months' => 24,
                'purpose' => 'Modal usaha',
            ])
            ->call('create')
            ->assertNotified();
    });
});

// ─── Action Edge Cases ─────────────────────────────────────────────────────

describe('DepositToSavings max balance', function (): void {

    it('throws when deposit would exceed max balance', function (): void {
        $product = SavingsProduct::factory()->create([
            'max_balance' => 10000000,
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
        ]);

        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $product,
            customerId: Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ])->id,
            branchId: $this->branch->id,
            initialDeposit: 9500000,
            performer: $this->user,
        ));

        app(DepositToSavings::class)->execute(
            account: $account,
            amount: 600000,
            performer: $this->user,
        );
    })->throws(SavingsBalanceLimitException::class, 'Saldo melebihi batas maksimal');
});

describe('HoldSavingsBalance inactive account', function (): void {

    it('throws when account is not active or dormant', function (): void {
        $product = SavingsProduct::factory()->create([
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
        ]);

        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $product,
            customerId: Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ])->id,
            branchId: $this->branch->id,
            initialDeposit: 200000,
            performer: $this->user,
        ));

        $account->update(['status' => SavingsAccountStatus::Frozen]);

        app(HoldSavingsBalance::class)->execute($account, 50000, $this->user);
    })->throws(InvalidSavingsAccountStatusException::class, 'Rekening tidak aktif');
});

describe('UnholdSavingsBalance inactive account', function (): void {

    it('throws when account is not active or dormant', function (): void {
        $product = SavingsProduct::factory()->create([
            'min_opening_balance' => 50000,
            'min_balance' => 25000,
        ]);

        $account = app(OpenSavingsAccount::class)->execute(new OpenSavingsAccountData(
            product: $product,
            customerId: Customer::factory()->create([
                'branch_id' => $this->branch->id,
                'created_by' => $this->user->id,
                'approved_by' => $this->user->id,
            ])->id,
            branchId: $this->branch->id,
            initialDeposit: 200000,
            performer: $this->user,
        ));

        app(HoldSavingsBalance::class)->execute($account, 50000, $this->user);
        $account->refresh();

        $account->update(['status' => SavingsAccountStatus::Frozen]);

        app(UnholdSavingsBalance::class)->execute($account, 30000, $this->user);
    })->throws(InvalidSavingsAccountStatusException::class, 'Rekening tidak aktif');
});

describe('RejectCustomer not pending', function (): void {

    it('throws when customer is already approved', function (): void {
        $creator = User::factory()->create(['branch_id' => $this->branch->id]);
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $creator->id,
            'approved_by' => $this->user->id,
            'approval_status' => ApprovalStatus::Approved,
        ]);

        app(RejectCustomer::class)->execute($customer, $this->user, 'Alasan penolakan');
    })->throws(CustomerApprovalException::class, 'Nasabah tidak dalam status menunggu persetujuan');
});

describe('PostJournalEntry not balanced', function (): void {

    it('throws when journal debit and credit are not balanced', function (): void {
        $cashAccount = ChartOfAccount::factory()->asset()->create([
            'normal_balance' => NormalBalance::Debit,
        ]);
        $liabilityAccount = ChartOfAccount::factory()->liability()->create([
            'normal_balance' => NormalBalance::Credit,
        ]);

        $journal = JournalEntry::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => User::factory()->create(['branch_id' => $this->branch->id])->id,
            'total_debit' => 100000,
            'total_credit' => 50000,
        ]);

        $approver = User::factory()->create(['branch_id' => $this->branch->id]);

        app(PostJournalEntry::class)->execute($journal, $approver);
    })->throws(InvalidJournalStatusException::class, 'Total debit dan kredit tidak seimbang');
});

describe('ProcessDepositMaturity zero tax', function (): void {

    it('skips tax transaction when tax rate is zero', function (): void {
        $product = DepositProduct::factory()->create([
            'tax_rate' => 0,
            'min_amount' => 1000000,
            'max_amount' => 2000000000,
            'penalty_rate' => 0.5,
        ]);

        DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 12,
            'min_amount' => 1000000,
            'max_amount' => null,
            'interest_rate' => 6.0,
            'is_active' => true,
        ]);

        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $account = app(PlaceDeposit::class)->execute(new PlaceDepositData(
            product: $product,
            customerId: $customer->id,
            branchId: $this->branch->id,
            principalAmount: 10000000,
            tenorMonths: 12,
            interestPaymentMethod: InterestPaymentMethod::Maturity,
            rolloverType: RolloverType::None,
            savingsAccountId: null,
            performer: $this->user,
            placementDate: Carbon::now()->subMonths(12),
        ));

        $matured = app(ProcessDepositMaturity::class)->execute($account, $this->user);

        expect($matured->status)->toBe(DepositStatus::Matured)
            ->and((float) $matured->total_interest_paid)->toBeGreaterThan(0)
            ->and((float) $matured->total_tax_paid)->toBe(0.00);

        $taxTransactions = $account->transactions()
            ->where('transaction_type', 'tax')
            ->count();

        expect($taxTransactions)->toBe(0);
    });
});
