<?php

use App\Actions\Eod\RunEodProcess;
use App\Actions\Teller\CloseTellerSession;
use App\Actions\Teller\OpenTellerSession;
use App\Actions\Teller\ProcessTellerDeposit;
use App\Actions\Teller\ProcessTellerLoanPayment;
use App\Actions\Teller\ProcessTellerWithdrawal;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Enums\SavingsAccountStatus;
use App\Enums\TellerSessionStatus;
use App\Exceptions\Eod\EodAlreadyRunException;
use App\Exceptions\Teller\InsufficientTellerCashException;
use App\Exceptions\Teller\TellerSessionAlreadyOpenException;
use App\Exceptions\Teller\TellerSessionClosedException;
use App\Filament\Pages\EodProcessPage;
use App\Filament\Pages\TellerDashboard;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\SavingsAccount;
use App\Models\SavingsProduct;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
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

// ─── TellerDashboard Actions ────────────────────────────────────────────────

describe('TellerDashboard Actions', function (): void {

    // ── canAccess ────────────────────────────────────────────────────────────

    it('denies access to users without teller.open-session permission', function (): void {
        $unprivileged = User::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->actingAs($unprivileged);

        Livewire::test(TellerDashboard::class)
            ->assertForbidden();
    });

    // ── openSession ─────────────────────────────────────────────────────────

    it('shows openSession action when no active session exists', function (): void {
        Livewire::test(TellerDashboard::class)
            ->assertActionVisible('openSession');
    });

    it('hides openSession action when active session exists', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        Livewire::test(TellerDashboard::class)
            ->assertActionHidden('openSession');
    });

    it('openSession action calls OpenTellerSession and shows success notification', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id]);

        $mock = Mockery::mock(OpenTellerSession::class);
        $mock->shouldReceive('execute')->once()->andReturnUsing(function () use ($vault): TellerSession {
            return TellerSession::factory()->create([
                'user_id' => $this->user->id,
                'branch_id' => $this->branch->id,
                'vault_id' => $vault->id,
            ]);
        });
        app()->instance(OpenTellerSession::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('openSession', data: [
                'vault_id' => $vault->id,
                'opening_balance' => 5000000,
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Sesi teller berhasil dibuka');
    });

    it('openSession action catches DomainException and shows danger notification', function (): void {
        $vault = Vault::factory()->create(['branch_id' => $this->branch->id]);

        $mock = Mockery::mock(OpenTellerSession::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            TellerSessionAlreadyOpenException::alreadyOpen($this->user)
        );
        app()->instance(OpenTellerSession::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('openSession', data: [
                'vault_id' => $vault->id,
                'opening_balance' => 5000000,
            ])
            ->assertNotified('Gagal');
    });

    // ── closeSession ────────────────────────────────────────────────────────

    it('shows closeSession action when active session exists', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        Livewire::test(TellerDashboard::class)
            ->assertActionVisible('closeSession');
    });

    it('hides closeSession action when no active session exists', function (): void {
        Livewire::test(TellerDashboard::class)
            ->assertActionHidden('closeSession');
    });

    it('closeSession action calls CloseTellerSession and shows success notification', function (): void {
        $session = TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $mock = Mockery::mock(CloseTellerSession::class);
        $mock->shouldReceive('execute')->once()->andReturn($session);
        app()->instance(CloseTellerSession::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('closeSession', data: [
                'closing_notes' => 'Tutup sesi hari ini',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Sesi teller berhasil ditutup');
    });

    it('closeSession action catches DomainException and shows danger notification', function (): void {
        $session = TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $mock = Mockery::mock(CloseTellerSession::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            TellerSessionClosedException::alreadyClosed($session)
        );
        app()->instance(CloseTellerSession::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('closeSession', data: [
                'closing_notes' => '',
            ])
            ->assertNotified('Gagal');
    });

    // ── deposit ─────────────────────────────────────────────────────────────

    it('deposit action calls ProcessTellerDeposit and shows success notification', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $account = SavingsAccount::factory()->create([
            'customer_id' => $customer->id,
            'savings_product_id' => SavingsProduct::factory(),
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mockTransaction = Mockery::mock(TellerTransaction::class);
        $mock = Mockery::mock(ProcessTellerDeposit::class);
        $mock->shouldReceive('execute')->once()->andReturn($mockTransaction);
        app()->instance(ProcessTellerDeposit::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('deposit', data: [
                'savings_account_id' => $account->id,
                'amount' => 1000000,
                'description' => 'Setoran tunai',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Setoran berhasil');
    });

    it('deposit action catches DomainException and shows danger notification', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $account = SavingsAccount::factory()->create([
            'customer_id' => $customer->id,
            'savings_product_id' => SavingsProduct::factory(),
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mock = Mockery::mock(ProcessTellerDeposit::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new TellerSessionClosedException('Sesi teller tidak aktif')
        );
        app()->instance(ProcessTellerDeposit::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('deposit', data: [
                'savings_account_id' => $account->id,
                'amount' => 1000000,
                'description' => 'Setoran tunai',
            ])
            ->assertNotified('Gagal');
    });

    // ── withdraw ─────────────────────────────────────────────────────────────

    it('withdraw action calls ProcessTellerWithdrawal and shows success notification', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
            'current_balance' => 50000000,
        ]);

        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $account = SavingsAccount::factory()->create([
            'customer_id' => $customer->id,
            'savings_product_id' => SavingsProduct::factory(),
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
            'balance' => 10000000,
            'available_balance' => 10000000,
        ]);

        $mockTransaction = Mockery::mock(TellerTransaction::class);
        $mock = Mockery::mock(ProcessTellerWithdrawal::class);
        $mock->shouldReceive('execute')->once()->andReturn($mockTransaction);
        app()->instance(ProcessTellerWithdrawal::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('withdraw', data: [
                'savings_account_id' => $account->id,
                'amount' => 500000,
                'description' => 'Penarikan tunai',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Penarikan berhasil');
    });

    it('withdraw action catches DomainException and shows danger notification', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
            'current_balance' => 50000000,
        ]);

        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $account = SavingsAccount::factory()->create([
            'customer_id' => $customer->id,
            'savings_product_id' => SavingsProduct::factory(),
            'branch_id' => $this->branch->id,
            'status' => SavingsAccountStatus::Active,
        ]);

        $mock = Mockery::mock(ProcessTellerWithdrawal::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new InsufficientTellerCashException('Saldo kas teller tidak cukup')
        );
        app()->instance(ProcessTellerWithdrawal::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('withdraw', data: [
                'savings_account_id' => $account->id,
                'amount' => 500000,
                'description' => 'Penarikan tunai',
            ])
            ->assertNotified('Gagal');
    });

    // ── loanPayment ─────────────────────────────────────────────────────────

    it('loanPayment action calls ProcessTellerLoanPayment and shows success notification', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => LoanProduct::factory(),
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
        ]);

        $mockTransaction = Mockery::mock(TellerTransaction::class);
        $mock = Mockery::mock(ProcessTellerLoanPayment::class);
        $mock->shouldReceive('execute')->once()->andReturn($mockTransaction);
        app()->instance(ProcessTellerLoanPayment::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('loanPayment', data: [
                'loan_account_id' => $loanAccount->id,
                'amount' => 1500000,
                'description' => 'Bayar angsuran bulan ini',
            ])
            ->assertHasNoActionErrors()
            ->assertNotified('Pembayaran angsuran berhasil');
    });

    it('loanPayment action catches DomainException and shows danger notification', function (): void {
        TellerSession::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
            'status' => TellerSessionStatus::Open,
        ]);

        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => LoanProduct::factory(),
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
        ]);

        $mock = Mockery::mock(ProcessTellerLoanPayment::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new TellerSessionClosedException('Sesi teller tidak aktif')
        );
        app()->instance(ProcessTellerLoanPayment::class, $mock);

        Livewire::test(TellerDashboard::class)
            ->callAction('loanPayment', data: [
                'loan_account_id' => $loanAccount->id,
                'amount' => 1500000,
                'description' => 'Bayar angsuran',
            ])
            ->assertNotified('Gagal');
    });
});

// ─── EodProcessPage Actions ─────────────────────────────────────────────────

describe('EodProcessPage Actions', function (): void {

    // ── canAccess ────────────────────────────────────────────────────────────

    it('denies access to users without eod.execute permission', function (): void {
        $unprivileged = User::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->actingAs($unprivileged);

        Livewire::test(EodProcessPage::class)
            ->assertForbidden();
    });

    // ── runEod success ──────────────────────────────────────────────────────

    it('runEod action shows success notification when process completes', function (): void {
        $mock = Mockery::mock(RunEodProcess::class);
        $mock->shouldReceive('execute')->once()->andReturnUsing(function (): EodProcess {
            return EodProcess::create([
                'process_date' => now()->toDateString(),
                'status' => EodStatus::Completed,
                'total_steps' => 11,
                'completed_steps' => 11,
                'started_at' => now(),
                'completed_at' => now(),
                'started_by' => $this->user->id,
            ]);
        });
        $mock->shouldReceive('getStepNames')->andReturn(RunEodProcess::STEP_NAMES);
        app()->instance(RunEodProcess::class, $mock);

        Livewire::test(EodProcessPage::class)
            ->callAction('runEod')
            ->assertHasNoActionErrors()
            ->assertNotified('EOD berhasil diselesaikan');
    });

    // ── runEod partial failure ──────────────────────────────────────────────

    it('runEod action shows danger notification when process fails', function (): void {
        $mock = Mockery::mock(RunEodProcess::class);
        $mock->shouldReceive('execute')->once()->andReturnUsing(function (): EodProcess {
            return EodProcess::create([
                'process_date' => now()->toDateString(),
                'status' => EodStatus::Failed,
                'total_steps' => 11,
                'completed_steps' => 3,
                'started_at' => now(),
                'error_message' => 'Step gagal: Interest posting error',
                'started_by' => $this->user->id,
            ]);
        });
        $mock->shouldReceive('getStepNames')->andReturn(RunEodProcess::STEP_NAMES);
        app()->instance(RunEodProcess::class, $mock);

        Livewire::test(EodProcessPage::class)
            ->callAction('runEod')
            ->assertNotified('EOD gagal');
    });

    // ── runEod visible when previous process failed ─────────────────────────

    it('runEod action is visible when previous process failed', function (): void {
        EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Failed,
            'total_steps' => 11,
            'completed_steps' => 3,
            'started_at' => now(),
            'error_message' => 'Something failed',
            'started_by' => $this->user->id,
        ]);

        $mock = Mockery::mock(RunEodProcess::class);
        $mock->shouldReceive('getStepNames')->andReturn(RunEodProcess::STEP_NAMES);
        app()->instance(RunEodProcess::class, $mock);

        Livewire::test(EodProcessPage::class)
            ->assertActionVisible('runEod');
    });

    // ── runEod DomainException ──────────────────────────────────────────────

    it('runEod action catches DomainException and shows danger notification', function (): void {
        $mock = Mockery::mock(RunEodProcess::class);
        $mock->shouldReceive('execute')->once()->andThrow(
            new EodAlreadyRunException('EOD sudah dijalankan hari ini')
        );
        $mock->shouldReceive('getStepNames')->andReturn(RunEodProcess::STEP_NAMES);
        app()->instance(RunEodProcess::class, $mock);

        Livewire::test(EodProcessPage::class)
            ->callAction('runEod')
            ->assertNotified('Gagal');
    });

    // ── updatedProcessDate ──────────────────────────────────────────────────

    it('updatedProcessDate clears computed caches', function (): void {
        EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Completed,
            'total_steps' => 11,
            'completed_steps' => 11,
            'started_at' => now(),
            'completed_at' => now(),
            'started_by' => $this->user->id,
        ]);

        $component = Livewire::test(EodProcessPage::class);

        // Verify currentProcess exists for today
        expect($component->instance()->currentProcess)->not->toBeNull();

        // Change processDate to a date with no EOD process
        $component->set('processDate', now()->subDays(10)->toDateString());

        // After updating processDate, the computed cache should be cleared
        expect($component->instance()->currentProcess)->toBeNull();
    });
});
