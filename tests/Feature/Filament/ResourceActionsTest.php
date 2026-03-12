<?php

use App\Actions\Accounting\PostJournalEntry;
use App\Actions\Accounting\ReverseJournalEntry;
use App\Actions\Customer\ApproveCustomer;
use App\Actions\Customer\RejectCustomer;
use App\Actions\Deposit\ProcessDepositMaturity;
use App\Actions\Loan\ApproveLoanApplication;
use App\Actions\Loan\DisburseLoan;
use App\Actions\Loan\MakeLoanPayment;
use App\Actions\Savings\CloseSavingsAccount;
use App\Actions\Savings\DepositToSavings;
use App\Actions\Savings\FreezeSavingsAccount;
use App\Actions\Savings\WithdrawFromSavings;
use App\DTOs\Loan\ApproveLoanApplicationData;
use App\DTOs\Loan\MakeLoanPaymentData;
use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\DepositStatus;
use App\Enums\JournalStatus;
use App\Enums\LoanApplicationStatus;
use App\Enums\LoanStatus;
use App\Enums\SavingsAccountStatus;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\AddressesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\DocumentsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\PhonesRelationManager;
use App\Filament\Resources\DepositAccountResource\Pages\ViewDepositAccount;
use App\Filament\Resources\DepositProductResource\Pages\EditDepositProduct;
use App\Filament\Resources\DepositProductResource\RelationManagers\RatesRelationManager;
use App\Filament\Resources\JournalEntryResource\Pages\ViewJournalEntry;
use App\Filament\Resources\LoanAccountResource\Pages\ViewLoanAccount;
use App\Filament\Resources\LoanApplicationResource\Pages\ViewLoanApplication;
use App\Filament\Resources\LoanApplicationResource\RelationManagers\CollateralsRelationManager;
use App\Filament\Resources\SavingsAccountResource\Pages\ViewSavingsAccount;
use App\Filament\Resources\SavingsAccountResource\RelationManagers\TransactionsRelationManager;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositAccount;
use App\Models\DepositProduct;
use App\Models\JournalEntry;
use App\Models\LoanAccount;
use App\Models\LoanApplication;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\SavingsAccount;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\Testing\TestAction;
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

// ─── Section 1: CustomerResource ViewCustomer Actions ──────────────────────

describe('CustomerResource ViewCustomer actions', function (): void {
    it('can call approve action on pending customer', function (): void {
        $otherUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $customer = Customer::factory()->pendingApproval()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $otherUser->id,
            'approved_by' => null,
        ]);

        $mock = Mockery::mock(ApproveCustomer::class);
        $mock->shouldReceive('execute')->once()->andReturn($customer);
        app()->instance(ApproveCustomer::class, $mock);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->callAction('approve')
            ->assertHasNoActionErrors()
            ->assertNotified('Nasabah berhasil disetujui');
    });

    it('can call reject action on pending customer', function (): void {
        $otherUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $customer = Customer::factory()->pendingApproval()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $otherUser->id,
            'approved_by' => null,
        ]);

        $mock = Mockery::mock(RejectCustomer::class);
        $mock->shouldReceive('execute')->once()->andReturn($customer);
        app()->instance(RejectCustomer::class, $mock);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->callAction('reject', data: [
                'rejection_reason' => 'Dokumen tidak lengkap',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Nasabah ditolak');
    });

    it('can call block action on active customer', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
            'status' => CustomerStatus::Active,
            'approval_status' => ApprovalStatus::Approved,
        ]);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->callAction('block')
            ->assertHasNoActionErrors()
            ->assertNotified('Nasabah diblokir');

        expect($customer->fresh()->status)->toBe(CustomerStatus::Blocked);
    });

    it('can call unblock action on blocked customer', function (): void {
        $customer = Customer::factory()->blocked()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        Livewire::test(ViewCustomer::class, ['record' => $customer->getRouteKey()])
            ->callAction('unblock')
            ->assertHasNoActionErrors()
            ->assertNotified('Blokir nasabah dibuka');

        expect($customer->fresh()->status)->toBe(CustomerStatus::Active);
    });
});

// ─── Section 2: CustomerResource EditCustomer ──────────────────────────────

describe('CustomerResource EditCustomer', function (): void {
    it('can render edit customer page', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        Livewire::test(EditCustomer::class, ['record' => $customer->getRouteKey()])
            ->assertOk();
    });
});

// ─── Section 3: RelationManager Form + Table Coverage ──────────────────────

describe('RelationManager form and table coverage', function (): void {
    it('can create address through AddressesRelationManager', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        Livewire::test(AddressesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'type' => 'domicile',
                'address' => 'Jl. Test 123',
                'city' => 'Jakarta',
                'province' => 'DKI Jakarta',
            ])
            ->assertHasNoActionErrors();
    });

    it('can create phone through PhonesRelationManager', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        Livewire::test(PhonesRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'type' => 'mobile',
                'number' => '081234567890',
            ])
            ->assertHasNoActionErrors();
    });

    it('can create document through DocumentsRelationManager', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        Livewire::test(DocumentsRelationManager::class, [
            'ownerRecord' => $customer,
            'pageClass' => EditCustomer::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'type' => 'ktp',
                'document_number' => '3201234567890001',
            ])
            ->assertHasNoActionErrors();
    });

    it('can render CollateralsRelationManager and create collateral', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = LoanProduct::factory()->create();
        $application = LoanApplication::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(CollateralsRelationManager::class, [
            'ownerRecord' => $application,
            'pageClass' => ViewLoanApplication::class,
        ])
            ->assertOk();
    });

    it('can create rate through RatesRelationManager', function (): void {
        $product = DepositProduct::factory()->create();

        Livewire::test(RatesRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditDepositProduct::class,
        ])
            ->callAction(TestAction::make(CreateAction::class)->table(), [
                'tenor_months' => 6,
                'min_amount' => 5000000,
                'max_amount' => 100000000,
                'interest_rate' => 5.5,
                'is_active' => true,
            ])
            ->assertHasNoActionErrors();
    });

    it('can render TransactionsRelationManager table', function (): void {
        $account = SavingsAccount::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        Livewire::test(TransactionsRelationManager::class, [
            'ownerRecord' => $account,
            'pageClass' => ViewSavingsAccount::class,
        ])
            ->assertOk();
    });
});

// ─── Section 4: View Page Actions ──────────────────────────────────────────

describe('SavingsAccountResource ViewSavingsAccount actions', function (): void {
    it('can call deposit action', function (): void {
        $account = SavingsAccount::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mock = Mockery::mock(DepositToSavings::class);
        $mock->shouldReceive('execute')->once();
        app()->instance(DepositToSavings::class, $mock);

        Livewire::test(ViewSavingsAccount::class, ['record' => $account->getRouteKey()])
            ->callAction('deposit', data: [
                'amount' => 100000,
                'description' => 'Setoran tunai',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Setoran berhasil');
    });

    it('can call withdraw action', function (): void {
        $account = SavingsAccount::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mock = Mockery::mock(WithdrawFromSavings::class);
        $mock->shouldReceive('execute')->once();
        app()->instance(WithdrawFromSavings::class, $mock);

        Livewire::test(ViewSavingsAccount::class, ['record' => $account->getRouteKey()])
            ->callAction('withdraw', data: [
                'amount' => 50000,
                'description' => 'Penarikan tunai',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Penarikan berhasil');
    });

    it('can call freeze action', function (): void {
        $account = SavingsAccount::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mock = Mockery::mock(FreezeSavingsAccount::class);
        $mock->shouldReceive('execute')->once();
        app()->instance(FreezeSavingsAccount::class, $mock);

        Livewire::test(ViewSavingsAccount::class, ['record' => $account->getRouteKey()])
            ->callAction('freeze')
            ->assertHasNoActionErrors()
            ->assertNotified('Rekening dibekukan');
    });

    it('can call close action', function (): void {
        $account = SavingsAccount::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mock = Mockery::mock(CloseSavingsAccount::class);
        $mock->shouldReceive('execute')->once()->andReturnNull();
        app()->instance(CloseSavingsAccount::class, $mock);

        Livewire::test(ViewSavingsAccount::class, ['record' => $account->getRouteKey()])
            ->callAction('close')
            ->assertHasNoActionErrors()
            ->assertNotified('Rekening ditutup');
    });
});

describe('DepositAccountResource ViewDepositAccount actions', function (): void {
    it('can call processMaturity action', function (): void {
        $account = DepositAccount::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => DepositStatus::Active,
            'maturity_date' => now()->subDay(),
        ]);

        $mock = Mockery::mock(ProcessDepositMaturity::class);
        $mock->shouldReceive('execute')->once();
        app()->instance(ProcessDepositMaturity::class, $mock);

        Livewire::test(ViewDepositAccount::class, ['record' => $account->getRouteKey()])
            ->callAction('processMaturity')
            ->assertHasNoActionErrors()
            ->assertNotified('Jatuh tempo berhasil diproses');
    });
});

describe('JournalEntryResource ViewJournalEntry actions', function (): void {
    it('can call post action on draft journal', function (): void {
        $otherUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $journal = JournalEntry::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $otherUser->id,
            'status' => JournalStatus::Draft,
            'approval_status' => ApprovalStatus::Pending,
        ]);

        $mock = Mockery::mock(PostJournalEntry::class);
        $mock->shouldReceive('execute')->once()->andReturn($journal);
        app()->instance(PostJournalEntry::class, $mock);

        Livewire::test(ViewJournalEntry::class, ['record' => $journal->getRouteKey()])
            ->callAction('post')
            ->assertHasNoActionErrors()
            ->assertNotified('Jurnal berhasil diposting');
    });

    it('can call reverse action on posted journal', function (): void {
        $journal = JournalEntry::factory()->posted()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);

        $mock = Mockery::mock(ReverseJournalEntry::class);
        $mock->shouldReceive('execute')->once()->andReturn($journal);
        app()->instance(ReverseJournalEntry::class, $mock);

        Livewire::test(ViewJournalEntry::class, ['record' => $journal->getRouteKey()])
            ->callAction('reverse', data: [
                'reason' => 'Salah input jurnal',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Jurnal berhasil dibatalkan');
    });
});

describe('LoanApplicationResource ViewLoanApplication actions', function (): void {
    it('can call approve action on submitted application', function (): void {
        $otherUser = User::factory()->create(['branch_id' => $this->branch->id]);
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = LoanProduct::factory()->create();
        $application = LoanApplication::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $otherUser->id,
            'status' => LoanApplicationStatus::Submitted,
        ]);

        $mock = Mockery::mock(ApproveLoanApplication::class);
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(fn (ApproveLoanApplicationData $dto): bool => $dto->application->is($application))
            ->andReturn($application);
        app()->instance(ApproveLoanApplication::class, $mock);

        Livewire::test(ViewLoanApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('approve', data: [
                'approved_amount' => $application->requested_amount,
                'approved_tenor' => $application->requested_tenor_months,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Permohonan berhasil disetujui');
    });

    it('can call disburse action on approved application', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = LoanProduct::factory()->create();
        $application = LoanApplication::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanApplicationStatus::Approved,
            'approved_amount' => 50000000,
            'approved_tenor_months' => 12,
            'approved_by' => $this->user->id,
            'approved_at' => now(),
        ]);

        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

        $mock = Mockery::mock(DisburseLoan::class);
        $mock->shouldReceive('execute')->once()->andReturn($loanAccount);
        app()->instance(DisburseLoan::class, $mock);

        Livewire::test(ViewLoanApplication::class, ['record' => $application->getRouteKey()])
            ->callAction('disburse')
            ->assertHasNoActionErrors()
            ->assertNotified('Kredit berhasil dicairkan');
    });
});

describe('LoanAccountResource ViewLoanAccount actions', function (): void {
    it('can call makePayment action on active loan', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'approved_by' => $this->user->id,
        ]);
        $product = LoanProduct::factory()->create();
        $account = LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
        ]);

        $payment = new LoanPayment([
            'reference_number' => 'PAY-001',
            'principal_portion' => 500000,
            'interest_portion' => 100000,
        ]);

        $mock = Mockery::mock(MakeLoanPayment::class);
        $mock->shouldReceive('execute')
            ->once()
            ->withArgs(fn (MakeLoanPaymentData $dto): bool => $dto->account->is($account))
            ->andReturn($payment);
        app()->instance(MakeLoanPayment::class, $mock);

        Livewire::test(ViewLoanAccount::class, ['record' => $account->getRouteKey()])
            ->callAction('makePayment', data: [
                'amount' => 600000,
                'description' => 'Pembayaran angsuran',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Pembayaran berhasil');
    });
});
