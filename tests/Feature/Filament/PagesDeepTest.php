<?php

use App\Enums\Collectibility;
use App\Enums\EodStatus;
use App\Enums\LoanStatus;
use App\Filament\Pages\BalanceSheetPage;
use App\Filament\Pages\EodProcessPage;
use App\Filament\Pages\IncomeStatementPage;
use App\Filament\Pages\LoanPortfolioReport;
use App\Filament\Pages\TellerDashboard;
use App\Filament\Pages\TrialBalancePage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EodProcess;
use App\Models\LoanAccount;
use App\Models\LoanProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
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

// ─── TellerDashboard ─────────────────────────────────────────────────────

describe('TellerDashboard', function (): void {
    it('activeSession returns null when no teller session exists', function (): void {
        $component = Livewire::test(TellerDashboard::class);

        expect($component->instance()->activeSession)->toBeNull();
    });

    it('recentTransactions returns empty collection when no active session', function (): void {
        $component = Livewire::test(TellerDashboard::class);

        $transactions = $component->instance()->recentTransactions;

        expect($transactions)->toBeInstanceOf(Collection::class)
            ->and($transactions)->toBeEmpty();
    });

    it('previousSessions returns a collection', function (): void {
        $component = Livewire::test(TellerDashboard::class);

        $sessions = $component->instance()->previousSessions;

        expect($sessions)->toBeInstanceOf(BaseCollection::class);
    });
});

// ─── EodProcessPage ──────────────────────────────────────────────────────

describe('EodProcessPage', function (): void {
    it('renders successfully', function (): void {
        Livewire::test(EodProcessPage::class)
            ->assertOk();
    });

    it('initializes processDate with today', function (): void {
        Livewire::test(EodProcessPage::class)
            ->assertSet('processDate', now()->toDateString());
    });

    it('currentProcess returns null when no EOD has been run for today', function (): void {
        $component = Livewire::test(EodProcessPage::class);

        expect($component->instance()->currentProcess)->toBeNull();
    });

    it('currentProcess returns the EodProcess when one exists for the date', function (): void {
        $eod = EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Completed,
            'total_steps' => 5,
            'completed_steps' => 5,
            'started_at' => now(),
            'completed_at' => now(),
            'started_by' => $this->user->id,
        ]);

        $component = Livewire::test(EodProcessPage::class);

        expect($component->instance()->currentProcess)->not->toBeNull()
            ->and($component->instance()->currentProcess->id)->toBe($eod->id);
    });

    it('recentProcesses returns a collection', function (): void {
        $component = Livewire::test(EodProcessPage::class);

        expect($component->instance()->recentProcesses)->toBeInstanceOf(Collection::class);
    });

    it('stepNames returns an array', function (): void {
        $component = Livewire::test(EodProcessPage::class);

        expect($component->instance()->stepNames)->toBeArray();
    });

    it('runEod button is visible when no process exists for date', function (): void {
        Livewire::test(EodProcessPage::class)
            ->assertActionVisible('runEod');
    });

    it('runEod button is visible when previous process failed', function (): void {
        EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Failed,
            'total_steps' => 5,
            'completed_steps' => 2,
            'started_at' => now(),
            'error_message' => 'Something went wrong',
            'started_by' => $this->user->id,
        ]);

        Livewire::test(EodProcessPage::class)
            ->assertActionVisible('runEod');
    });

    it('runEod button is hidden when process completed successfully', function (): void {
        EodProcess::create([
            'process_date' => now()->toDateString(),
            'status' => EodStatus::Completed,
            'total_steps' => 5,
            'completed_steps' => 5,
            'started_at' => now(),
            'completed_at' => now(),
            'started_by' => $this->user->id,
        ]);

        Livewire::test(EodProcessPage::class)
            ->assertActionHidden('runEod');
    });
});

// ─── TrialBalancePage ────────────────────────────────────────────────────

describe('TrialBalancePage', function (): void {
    it('trialBalance computed returns a collection', function (): void {
        $component = Livewire::test(TrialBalancePage::class);

        $trialBalance = $component->instance()->trialBalance;

        expect($trialBalance)->toBeInstanceOf(BaseCollection::class);
    });

    it('trialBalance resets when year is updated', function (): void {
        $component = Livewire::test(TrialBalancePage::class);

        $component->set('year', 2024);

        expect($component->instance()->year)->toBe(2024);
    });

    it('trialBalance resets when month is updated', function (): void {
        $component = Livewire::test(TrialBalancePage::class);

        $component->set('month', 6);

        expect($component->instance()->month)->toBe(6);
    });
});

// ─── BalanceSheetPage ────────────────────────────────────────────────────

describe('BalanceSheetPage', function (): void {
    it('balanceSheet computed returns correct structure', function (): void {
        $component = Livewire::test(BalanceSheetPage::class);

        $balanceSheet = $component->instance()->balanceSheet;

        expect($balanceSheet)->toBeArray()
            ->and($balanceSheet)->toHaveKeys([
                'date',
                'assets',
                'liabilities',
                'equity',
                'total_assets',
                'total_liabilities',
                'total_equity',
            ])
            ->and($balanceSheet['assets'])->toBeArray()
            ->and($balanceSheet['liabilities'])->toBeArray()
            ->and($balanceSheet['equity'])->toBeArray();
    });

    it('balanceSheet resets when reportDate is updated', function (): void {
        $component = Livewire::test(BalanceSheetPage::class);

        $component->set('reportDate', '2024-01-01');

        expect($component->instance()->reportDate)->toBe('2024-01-01');
    });
});

// ─── IncomeStatementPage ─────────────────────────────────────────────────

describe('IncomeStatementPage', function (): void {
    it('incomeStatement computed returns correct structure', function (): void {
        $component = Livewire::test(IncomeStatementPage::class);

        $incomeStatement = $component->instance()->incomeStatement;

        expect($incomeStatement)->toBeArray()
            ->and($incomeStatement)->toHaveKeys([
                'start_date',
                'end_date',
                'revenues',
                'expenses',
                'total_revenue',
                'total_expense',
                'net_income',
            ])
            ->and($incomeStatement['revenues'])->toBeArray()
            ->and($incomeStatement['expenses'])->toBeArray();
    });

    it('incomeStatement resets when startDate is updated', function (): void {
        $component = Livewire::test(IncomeStatementPage::class);

        $component->set('startDate', '2024-01-01');

        expect($component->instance()->startDate)->toBe('2024-01-01');
    });

    it('incomeStatement resets when endDate is updated', function (): void {
        $component = Livewire::test(IncomeStatementPage::class);

        $component->set('endDate', '2024-06-30');

        expect($component->instance()->endDate)->toBe('2024-06-30');
    });
});

// ─── LoanPortfolioReport ─────────────────────────────────────────────────

describe('LoanPortfolioReport', function (): void {
    it('portfolioByCollectibility returns a collection', function (): void {
        $component = Livewire::test(LoanPortfolioReport::class);

        expect($component->instance()->portfolioByCollectibility)->toBeInstanceOf(BaseCollection::class);
    });

    it('portfolioByCollectibility maps data with correct structure when loans exist', function (): void {
        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $product = LoanProduct::factory()->create();

        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'collectibility' => Collectibility::Current,
            'outstanding_principal' => 50000000,
            'ckpn_amount' => 500000,
        ]);

        $component = Livewire::test(LoanPortfolioReport::class);
        $portfolio = $component->instance()->portfolioByCollectibility;

        expect($portfolio)->not->toBeEmpty();

        $first = $portfolio->first();
        expect($first)->toHaveKeys([
            'collectibility',
            'color',
            'count',
            'total_outstanding',
            'total_ckpn',
            'ckpn_rate',
        ]);
    });

    it('summary returns correct data types', function (): void {
        $component = Livewire::test(LoanPortfolioReport::class);

        $summary = $component->instance()->summary;

        expect($summary)->toBeArray()
            ->and($summary)->toHaveKeys([
                'total_accounts',
                'total_outstanding',
                'total_plafon',
                'total_ckpn',
                'npl_count',
                'npl_amount',
            ])
            ->and($summary['total_accounts'])->toBeInt()
            ->and($summary['total_outstanding'])->toBeFloat()
            ->and($summary['total_plafon'])->toBeFloat()
            ->and($summary['total_ckpn'])->toBeFloat()
            ->and($summary['npl_count'])->toBeInt()
            ->and($summary['npl_amount'])->toBeFloat();
    });

    it('nplRatio returns zero when no loans exist', function (): void {
        $component = Livewire::test(LoanPortfolioReport::class);

        expect($component->instance()->nplRatio)->toBe(0.0);
    });

    it('nplRatio calculates correctly with NPL loans', function (): void {
        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);
        $product = LoanProduct::factory()->create();

        // Current loan
        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Active,
            'collectibility' => Collectibility::Current,
            'outstanding_principal' => 50000000,
        ]);

        // NPL loan (collectibility >= 3)
        LoanAccount::factory()->create([
            'customer_id' => $customer->id,
            'loan_product_id' => $product->id,
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
            'status' => LoanStatus::Overdue,
            'collectibility' => Collectibility::Substandard,
            'outstanding_principal' => 50000000,
        ]);

        $component = Livewire::test(LoanPortfolioReport::class);

        expect($component->instance()->nplRatio)->toBeGreaterThan(0.0);
    });

    it('portfolioByProduct returns a collection', function (): void {
        $component = Livewire::test(LoanPortfolioReport::class);

        expect($component->instance()->portfolioByProduct)->toBeInstanceOf(BaseCollection::class);
    });
});
