<?php

pest()->group('legacy');

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
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\CorporateDetail;
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
use App\Models\LoanApplication;
use App\Models\LoanCollateral;
use App\Models\LoanPayment;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\SavingsAccount;
use App\Models\SavingsInterestAccrual;
use App\Models\SavingsProduct;
use App\Models\SavingsTransaction;
use App\Models\Sequence;
use App\Models\SystemParameter;
use App\Models\TellerSession;
use App\Models\TellerTransaction;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultTransaction;
use Carbon\Carbon;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

// ============================================================================
// User
// ============================================================================
describe('User', function (): void {
    it('can be created with factory', function (): void {
        $user = User::factory()->create();

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBeString()
            ->and($user->email)->toBeString();
    });

    it('casts is_active to boolean', function (): void {
        $user = User::factory()->create(['is_active' => true]);

        expect($user->is_active)->toBeTrue()->toBeBool();
    });

    it('casts email_verified_at to datetime', function (): void {
        $user = User::factory()->create();

        expect($user->email_verified_at)->toBeInstanceOf(Carbon::class);
    });

    it('has branch relationship', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);

        expect($user->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($user->branch->id)->toBe($branch->id);
    });

    it('canAccessPanel returns true when active', function (): void {
        $user = User::factory()->create(['is_active' => true]);

        expect($user->canAccessPanel(app(Panel::class)))->toBeTrue();
    });

    it('canAccessPanel returns false when inactive', function (): void {
        $user = User::factory()->create(['is_active' => false]);

        expect($user->canAccessPanel(app(Panel::class)))->toBeFalse();
    });
});

// ============================================================================
// Branch
// ============================================================================
describe('Branch', function (): void {
    it('can be created with factory', function (): void {
        $branch = Branch::factory()->create();

        expect($branch)->toBeInstanceOf(Branch::class)
            ->and($branch->code)->toBeString()
            ->and($branch->name)->toBeString();
    });

    it('casts booleans correctly', function (): void {
        $branch = Branch::factory()->create(['is_head_office' => true, 'is_active' => true]);

        expect($branch->is_head_office)->toBeTrue()->toBeBool()
            ->and($branch->is_active)->toBeTrue()->toBeBool();
    });

    it('has head relationship', function (): void {
        $branch = Branch::factory()->create();

        expect($branch->head())->toBeInstanceOf(BelongsTo::class);
    });

    it('has users relationship', function (): void {
        $branch = Branch::factory()->create();

        expect($branch->users())->toBeInstanceOf(HasMany::class);
    });

    it('scope active filters correctly', function (): void {
        Branch::factory()->create(['is_active' => true]);
        Branch::factory()->create(['is_active' => false]);

        $active = Branch::active()->get();

        expect($active->every(fn ($b) => $b->is_active))->toBeTrue();
    });
});

// ============================================================================
// SystemParameter
// ============================================================================
describe('SystemParameter', function (): void {
    it('can be created with factory', function (): void {
        $param = SystemParameter::factory()->create();

        expect($param)->toBeInstanceOf(SystemParameter::class);
    });

    it('casts is_editable to boolean', function (): void {
        $param = SystemParameter::factory()->create(['is_editable' => true]);

        expect($param->is_editable)->toBeTrue()->toBeBool();
    });

    it('getValue returns string value', function (): void {
        SystemParameter::factory()->create([
            'group' => 'general',
            'key' => 'bank_name',
            'value' => 'Test Bank',
            'type' => 'string',
        ]);

        expect(SystemParameter::getValue('general', 'bank_name'))->toBe('Test Bank');
    });

    it('getValue returns integer value', function (): void {
        SystemParameter::factory()->integer()->create([
            'group' => 'general',
            'key' => 'max_retry',
            'value' => '5',
        ]);

        expect(SystemParameter::getValue('general', 'max_retry'))->toBe(5)->toBeInt();
    });

    it('getValue returns float for decimal type', function (): void {
        SystemParameter::factory()->decimal()->create([
            'group' => 'savings',
            'key' => 'default_rate',
            'value' => '3.50',
        ]);

        expect(SystemParameter::getValue('savings', 'default_rate'))->toBe(3.50)->toBeFloat();
    });

    it('getValue returns boolean value', function (): void {
        SystemParameter::factory()->boolean()->create([
            'group' => 'general',
            'key' => 'maintenance_mode',
            'value' => 'true',
        ]);

        expect(SystemParameter::getValue('general', 'maintenance_mode'))->toBeTrue();
    });

    it('getValue returns default when not found', function (): void {
        expect(SystemParameter::getValue('nonexistent', 'key', 'fallback'))->toBe('fallback');
    });
});

// ============================================================================
// Holiday
// ============================================================================
describe('Holiday', function (): void {
    it('can be created with factory', function (): void {
        $holiday = Holiday::factory()->create();

        expect($holiday)->toBeInstanceOf(Holiday::class);
    });

    it('casts date to date', function (): void {
        $holiday = Holiday::factory()->create();

        expect($holiday->date)->toBeInstanceOf(Carbon::class);
    });

    it('isHoliday returns true for weekend', function (): void {
        $saturday = Carbon::parse('2026-03-14'); // Saturday

        expect(Holiday::isHoliday($saturday))->toBeTrue();
    });

    it('isHoliday returns true for registered holiday', function (): void {
        $date = Carbon::parse('2026-03-11'); // Wednesday
        Holiday::factory()->onDate('2026-03-11')->create();

        expect(Holiday::isHoliday($date))->toBeTrue();
    });

    it('isHoliday returns false for regular business day', function (): void {
        $date = Carbon::parse('2026-03-11'); // Wednesday, no holiday registered

        expect(Holiday::isHoliday($date))->toBeFalse();
    });

    it('getNextBusinessDay skips holidays and weekends', function (): void {
        // Friday
        $friday = Carbon::parse('2026-03-13');
        Holiday::factory()->onDate('2026-03-16')->create(); // Monday is holiday

        $next = Holiday::getNextBusinessDay($friday);

        // Should skip Saturday (14), Sunday (15), Monday holiday (16) => Tuesday 17
        expect($next->toDateString())->toBe('2026-03-17');
    });
});

// ============================================================================
// ChartOfAccount
// ============================================================================
describe('ChartOfAccount', function (): void {
    it('can be created with factory', function (): void {
        $coa = ChartOfAccount::factory()->create();

        expect($coa)->toBeInstanceOf(ChartOfAccount::class);
    });

    it('casts enums correctly', function (): void {
        $coa = ChartOfAccount::factory()->asset()->create();

        expect($coa->account_group)->toBe(AccountGroup::Asset)
            ->and($coa->normal_balance)->toBe(NormalBalance::Debit)
            ->and($coa->is_header)->toBeBool()
            ->and($coa->is_active)->toBeBool()
            ->and($coa->level)->toBeInt();
    });

    it('has parent and children relationships', function (): void {
        $parent = ChartOfAccount::factory()->header()->create();
        $child = ChartOfAccount::factory()->childOf($parent)->create();

        expect($parent->parent())->toBeInstanceOf(BelongsTo::class)
            ->and($parent->children())->toBeInstanceOf(HasMany::class)
            ->and($parent->children->first()->id)->toBe($child->id)
            ->and($child->parent->id)->toBe($parent->id);
    });

    it('scope postable filters non-header active accounts', function (): void {
        ChartOfAccount::factory()->create(['is_header' => false, 'is_active' => true]);
        ChartOfAccount::factory()->header()->create();
        ChartOfAccount::factory()->inactive()->create();

        $postable = ChartOfAccount::postable()->get();

        expect($postable->every(fn ($c): bool => ! $c->is_header && $c->is_active))->toBeTrue();
    });

    it('scope byGroup filters by account group', function (): void {
        ChartOfAccount::factory()->asset()->create();
        ChartOfAccount::factory()->liability()->create();

        $assets = ChartOfAccount::byGroup(AccountGroup::Asset)->get();

        expect($assets->every(fn ($c): bool => $c->account_group === AccountGroup::Asset))->toBeTrue();
    });

    it('scope active filters correctly', function (): void {
        ChartOfAccount::factory()->create(['is_active' => true]);
        ChartOfAccount::factory()->inactive()->create();

        $active = ChartOfAccount::active()->get();

        expect($active->every(fn ($c) => $c->is_active))->toBeTrue();
    });

    it('has fullName accessor', function (): void {
        $coa = ChartOfAccount::factory()->create([
            'account_code' => '10001',
            'account_name' => 'Kas',
        ]);

        expect($coa->full_name)->toBe('10001 - Kas');
    });
});

// ============================================================================
// Sequence
// ============================================================================
describe('Sequence', function (): void {
    it('can be created', function (): void {
        $seq = Sequence::create([
            'type' => 'savings_account',
            'prefix' => 'SAV',
            'last_number' => 100,
            'padding' => 8,
        ]);

        expect($seq)->toBeInstanceOf(Sequence::class)
            ->and($seq->last_number)->toBeInt()
            ->and($seq->padding)->toBeInt();
    });
});

// ============================================================================
// Customer
// ============================================================================
describe('Customer', function (): void {
    it('can be created with factory', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
        ]);

        expect($customer)->toBeInstanceOf(Customer::class)
            ->and($customer->cif_number)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $customer = Customer::factory()->individual()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'approved_by' => $user->id,
        ]);

        expect($customer->customer_type)->toBe(CustomerType::Individual)
            ->and($customer->status)->toBeInstanceOf(CustomerStatus::class)
            ->and($customer->risk_rating)->toBeInstanceOf(RiskRating::class)
            ->and($customer->approval_status)->toBeInstanceOf(ApprovalStatus::class)
            ->and($customer->approved_at)->toBeInstanceOf(Carbon::class);
    });

    it('has branch relationship', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        $customer = Customer::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
        ]);

        expect($customer->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($customer->branch->id)->toBe($branch->id);
    });

    it('has individualDetail relationship', function (): void {
        $customer = Customer::factory()->individual()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($customer->individualDetail())->toBeInstanceOf(HasOne::class);
    });

    it('has corporateDetail relationship', function (): void {
        $customer = Customer::factory()->corporate()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($customer->corporateDetail())->toBeInstanceOf(HasOne::class);
    });

    it('has addresses relationship', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($customer->addresses())->toBeInstanceOf(HasMany::class);
    });

    it('has phones relationship', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($customer->phones())->toBeInstanceOf(HasMany::class);
    });

    it('has documents relationship', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($customer->documents())->toBeInstanceOf(HasMany::class);
    });

    it('display_name returns individual full_name', function (): void {
        $customer = Customer::factory()->individual()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        IndividualDetail::factory()->create([
            'customer_id' => $customer->id,
            'full_name' => 'John Doe',
        ]);
        $customer->load('individualDetail');

        expect($customer->display_name)->toBe('John Doe');
    });

    it('display_name returns corporate company_name', function (): void {
        $customer = Customer::factory()->corporate()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        CorporateDetail::factory()->create([
            'customer_id' => $customer->id,
            'company_name' => 'PT Test Corp',
        ]);
        $customer->load('corporateDetail');

        expect($customer->display_name)->toBe('PT Test Corp');
    });

    it('display_name falls back to cif_number', function (): void {
        $customer = Customer::factory()->individual()->create([
            'cif_number' => '00100000001',
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($customer->display_name)->toBe('00100000001');
    });

    it('scope active filters correctly', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        Customer::factory()->create(['status' => CustomerStatus::Active, 'branch_id' => $branch->id, 'created_by' => $user->id]);
        Customer::factory()->blocked()->create(['branch_id' => $branch->id, 'created_by' => $user->id]);

        $active = Customer::active()->get();

        expect($active->every(fn ($c): bool => $c->status === CustomerStatus::Active))->toBeTrue();
    });

    it('scope byType filters correctly', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        Customer::factory()->individual()->create(['branch_id' => $branch->id, 'created_by' => $user->id]);
        Customer::factory()->corporate()->create(['branch_id' => $branch->id, 'created_by' => $user->id]);

        $individuals = Customer::byType(CustomerType::Individual)->get();

        expect($individuals->every(fn ($c): bool => $c->customer_type === CustomerType::Individual))->toBeTrue();
    });

    it('HasApproval approve works', function (): void {
        $creator = User::factory()->create();
        $approver = User::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create([
            'branch_id' => Branch::factory(),
            'created_by' => $creator->id,
        ]);

        $result = $customer->approve($approver);

        expect($result)->toBeTrue()
            ->and($customer->fresh()->approval_status)->toBe(ApprovalStatus::Approved);
    });

    it('HasApproval reject works', function (): void {
        $creator = User::factory()->create();
        $approver = User::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create([
            'branch_id' => Branch::factory(),
            'created_by' => $creator->id,
        ]);

        $result = $customer->reject($approver, 'Incomplete documents');

        expect($result)->toBeTrue()
            ->and($customer->fresh()->approval_status)->toBe(ApprovalStatus::Rejected)
            ->and($customer->fresh()->rejection_reason)->toBe('Incomplete documents');
    });

    it('HasApproval cannot self-approve', function (): void {
        $creator = User::factory()->create();
        $customer = Customer::factory()->pendingApproval()->create([
            'branch_id' => Branch::factory(),
            'created_by' => $creator->id,
        ]);

        expect($customer->canBeApprovedBy($creator))->toBeFalse();
    });

    it('HasApproval scope pendingApproval works', function (): void {
        $branch = Branch::factory()->create();
        $user = User::factory()->create();
        Customer::factory()->pendingApproval()->create(['branch_id' => $branch->id, 'created_by' => $user->id]);
        Customer::factory()->create(['branch_id' => $branch->id, 'created_by' => $user->id]);

        $pending = Customer::pendingApproval()->get();

        expect($pending->every(fn ($c): bool => $c->approval_status === ApprovalStatus::Pending))->toBeTrue();
    });
});

// ============================================================================
// CustomerAddress
// ============================================================================
describe('CustomerAddress', function (): void {
    it('can be created', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'home',
            'address' => 'Jl. Test No. 1',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'postal_code' => '12345',
            'is_primary' => true,
        ]);

        expect($address)->toBeInstanceOf(CustomerAddress::class)
            ->and($address->is_primary)->toBeTrue()->toBeBool();
    });

    it('has customer relationship', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        $address = CustomerAddress::create([
            'customer_id' => $customer->id,
            'type' => 'home',
            'address' => 'Jl. Test',
            'is_primary' => true,
        ]);

        expect($address->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($address->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// CustomerPhone
// ============================================================================
describe('CustomerPhone', function (): void {
    it('can be created', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        $phone = CustomerPhone::create([
            'customer_id' => $customer->id,
            'type' => 'mobile',
            'number' => '081234567890',
            'is_primary' => true,
        ]);

        expect($phone)->toBeInstanceOf(CustomerPhone::class)
            ->and($phone->is_primary)->toBeTrue()->toBeBool();
    });

    it('has customer relationship', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        $phone = CustomerPhone::create([
            'customer_id' => $customer->id,
            'type' => 'mobile',
            'number' => '081234567890',
            'is_primary' => false,
        ]);

        expect($phone->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($phone->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// CustomerDocument
// ============================================================================
describe('CustomerDocument', function (): void {
    it('can be created', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        $doc = CustomerDocument::create([
            'customer_id' => $customer->id,
            'type' => 'ktp',
            'document_number' => '3201010101010001',
            'file_path' => 'documents/ktp.pdf',
            'file_name' => 'ktp.pdf',
            'expiry_date' => '2030-01-01',
            'is_verified' => true,
        ]);

        expect($doc)->toBeInstanceOf(CustomerDocument::class)
            ->and($doc->expiry_date)->toBeInstanceOf(Carbon::class)
            ->and($doc->is_verified)->toBeTrue()->toBeBool();
    });

    it('has customer relationship', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        $doc = CustomerDocument::create([
            'customer_id' => $customer->id,
            'type' => 'ktp',
            'document_number' => '123',
            'is_verified' => false,
        ]);

        expect($doc->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($doc->customer->id)->toBe($customer->id);
    });
});

// ============================================================================
// IndividualDetail
// ============================================================================
describe('IndividualDetail', function (): void {
    it('can be created with factory', function (): void {
        $detail = IndividualDetail::factory()->create();

        expect($detail)->toBeInstanceOf(IndividualDetail::class)
            ->and($detail->full_name)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $detail = IndividualDetail::factory()->create([
            'gender' => Gender::Male,
            'marital_status' => MaritalStatus::Single,
        ]);

        expect($detail->gender)->toBe(Gender::Male)
            ->and($detail->marital_status)->toBe(MaritalStatus::Single)
            ->and($detail->birth_date)->toBeInstanceOf(Carbon::class);
    });

    it('has customer relationship', function (): void {
        $detail = IndividualDetail::factory()->create();

        expect($detail->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($detail->customer)->toBeInstanceOf(Customer::class);
    });
});

// ============================================================================
// CorporateDetail
// ============================================================================
describe('CorporateDetail', function (): void {
    it('can be created with factory', function (): void {
        $detail = CorporateDetail::factory()->create();

        expect($detail)->toBeInstanceOf(CorporateDetail::class)
            ->and($detail->company_name)->toBeString();
    });

    it('casts correctly', function (): void {
        $detail = CorporateDetail::factory()->create([
            'beneficial_owner' => ['name' => 'John'],
            'authorized_persons' => [['name' => 'Jane', 'position' => 'Director']],
        ]);

        expect($detail->deed_date)->toBeInstanceOf(Carbon::class)
            ->and($detail->beneficial_owner)->toBeArray()
            ->and($detail->authorized_persons)->toBeArray();
    });

    it('has customer relationship', function (): void {
        $detail = CorporateDetail::factory()->create();

        expect($detail->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($detail->customer)->toBeInstanceOf(Customer::class);
    });
});

// ============================================================================
// SavingsProduct
// ============================================================================
describe('SavingsProduct', function (): void {
    it('can be created with factory', function (): void {
        $product = SavingsProduct::factory()->create();

        expect($product)->toBeInstanceOf(SavingsProduct::class)
            ->and($product->code)->toBeString();
    });

    it('casts enum and decimals correctly', function (): void {
        $product = SavingsProduct::factory()->create([
            'interest_calc_method' => InterestCalcMethod::DailyBalance,
        ]);

        expect($product->interest_calc_method)->toBe(InterestCalcMethod::DailyBalance)
            ->and($product->is_active)->toBeBool()
            ->and($product->interest_rate)->toBeString(); // decimal:5 returns string
    });

    it('has accounts relationship', function (): void {
        $product = SavingsProduct::factory()->create();

        expect($product->accounts())->toBeInstanceOf(HasMany::class);
    });

    it('has GL relationships', function (): void {
        $gl = ChartOfAccount::factory()->create();
        $product = SavingsProduct::factory()->create(['gl_savings_id' => $gl->id]);

        expect($product->glSavings())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glInterestExpense())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glInterestPayable())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glAdminFeeIncome())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glTaxPayable())->toBeInstanceOf(BelongsTo::class);
    });

    it('scope active filters correctly', function (): void {
        SavingsProduct::factory()->create(['is_active' => true]);
        SavingsProduct::factory()->create(['is_active' => false]);

        $active = SavingsProduct::active()->get();

        expect($active->every(fn ($p) => $p->is_active))->toBeTrue();
    });
});

// ============================================================================
// SavingsAccount
// ============================================================================
describe('SavingsAccount', function (): void {
    it('can be created with factory', function (): void {
        $account = SavingsAccount::factory()->create();

        expect($account)->toBeInstanceOf(SavingsAccount::class)
            ->and($account->account_number)->toBeString();
    });

    it('casts enum and dates correctly', function (): void {
        $account = SavingsAccount::factory()->create([
            'status' => SavingsAccountStatus::Active,
        ]);

        expect($account->status)->toBe(SavingsAccountStatus::Active)
            ->and($account->opened_at)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $account = SavingsAccount::factory()->create();

        expect($account->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($account->savingsProduct())->toBeInstanceOf(BelongsTo::class)
            ->and($account->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($account->creator())->toBeInstanceOf(BelongsTo::class)
            ->and($account->transactions())->toBeInstanceOf(HasMany::class)
            ->and($account->interestAccruals())->toBeInstanceOf(HasMany::class);
    });

    it('scope active filters correctly', function (): void {
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Active]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Dormant]);

        $active = SavingsAccount::active()->get();

        expect($active->every(fn ($a): bool => $a->status === SavingsAccountStatus::Active))->toBeTrue();
    });

    it('scope byStatus filters correctly', function (): void {
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Dormant]);
        SavingsAccount::factory()->create(['status' => SavingsAccountStatus::Active]);

        $dormant = SavingsAccount::byStatus(SavingsAccountStatus::Dormant)->get();

        expect($dormant->every(fn ($a): bool => $a->status === SavingsAccountStatus::Dormant))->toBeTrue();
    });

    it('recalculateAvailableBalance works', function (): void {
        $account = SavingsAccount::factory()->create([
            'balance' => 1000000.00,
            'hold_amount' => 250000.00,
            'available_balance' => 1000000.00,
        ]);

        $account->recalculateAvailableBalance();
        $account->refresh();

        expect($account->available_balance)->toBe('750000.00');
    });
});

// ============================================================================
// SavingsTransaction
// ============================================================================
describe('SavingsTransaction', function (): void {
    it('can be created', function (): void {
        $account = SavingsAccount::factory()->create();

        $txn = SavingsTransaction::create([
            'reference_number' => 'TXN001',
            'savings_account_id' => $account->id,
            'transaction_type' => SavingsTransactionType::Deposit,
            'amount' => 500000.00,
            'balance_before' => 1000000.00,
            'balance_after' => 1500000.00,
            'description' => 'Cash deposit',
            'transaction_date' => now(),
            'value_date' => now(),
            'performed_by' => User::factory()->create()->id,
            'is_reversed' => false,
        ]);

        expect($txn)->toBeInstanceOf(SavingsTransaction::class)
            ->and($txn->transaction_type)->toBe(SavingsTransactionType::Deposit)
            ->and($txn->is_reversed)->toBeFalse()->toBeBool()
            ->and($txn->transaction_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $account = SavingsAccount::factory()->create();
        $txn = SavingsTransaction::create([
            'reference_number' => 'TXN002',
            'savings_account_id' => $account->id,
            'transaction_type' => SavingsTransactionType::Withdrawal,
            'amount' => 100000,
            'balance_before' => 1000000,
            'balance_after' => 900000,
            'transaction_date' => now(),
            'value_date' => now(),
            'performed_by' => User::factory()->create()->id,
            'is_reversed' => false,
        ]);

        expect($txn->savingsAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->performer())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->reverser())->toBeInstanceOf(BelongsTo::class);
    });
});

// ============================================================================
// SavingsInterestAccrual
// ============================================================================
describe('SavingsInterestAccrual', function (): void {
    it('can be created', function (): void {
        $account = SavingsAccount::factory()->create();

        $accrual = SavingsInterestAccrual::create([
            'savings_account_id' => $account->id,
            'accrual_date' => now(),
            'balance' => 10000000,
            'interest_rate' => 3.50000,
            'accrued_amount' => 958.90,
            'tax_amount' => 191.78,
            'is_posted' => false,
        ]);

        expect($accrual)->toBeInstanceOf(SavingsInterestAccrual::class)
            ->and($accrual->accrual_date)->toBeInstanceOf(Carbon::class)
            ->and($accrual->is_posted)->toBeFalse()->toBeBool();
    });

    it('has savingsAccount relationship', function (): void {
        $account = SavingsAccount::factory()->create();
        $accrual = SavingsInterestAccrual::create([
            'savings_account_id' => $account->id,
            'accrual_date' => now(),
            'balance' => 10000000,
            'interest_rate' => 3.50,
            'accrued_amount' => 958.90,
            'tax_amount' => 0,
            'is_posted' => false,
        ]);

        expect($accrual->savingsAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($accrual->savingsAccount->id)->toBe($account->id);
    });
});

// ============================================================================
// DepositProduct
// ============================================================================
describe('DepositProduct', function (): void {
    it('can be created with factory', function (): void {
        $product = DepositProduct::factory()->create();

        expect($product)->toBeInstanceOf(DepositProduct::class)
            ->and($product->code)->toBeString();
    });

    it('casts correctly', function (): void {
        $product = DepositProduct::factory()->create();

        expect($product->is_active)->toBeBool();
    });

    it('has relationships', function (): void {
        $product = DepositProduct::factory()->create();

        expect($product->rates())->toBeInstanceOf(HasMany::class)
            ->and($product->accounts())->toBeInstanceOf(HasMany::class)
            ->and($product->glDeposit())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glInterestExpense())->toBeInstanceOf(BelongsTo::class);
    });

    it('scope active filters correctly', function (): void {
        DepositProduct::factory()->create(['is_active' => true]);
        DepositProduct::factory()->create(['is_active' => false]);

        $active = DepositProduct::active()->get();

        expect($active->every(fn ($p) => $p->is_active))->toBeTrue();
    });

    it('getRateForTenorAndAmount returns matching rate', function (): void {
        $product = DepositProduct::factory()->create();
        DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 6,
            'min_amount' => 1000000,
            'max_amount' => 100000000,
            'interest_rate' => 5.50,
            'is_active' => true,
        ]);

        $rate = $product->getRateForTenorAndAmount(6, 50000000);

        expect($rate)->toBeInstanceOf(DepositProductRate::class)
            ->and($rate->interest_rate)->toBe('5.50000');
    });

    it('getRateForTenorAndAmount returns null when no match', function (): void {
        $product = DepositProduct::factory()->create();

        expect($product->getRateForTenorAndAmount(99, 100))->toBeNull();
    });
});

// ============================================================================
// DepositProductRate
// ============================================================================
describe('DepositProductRate', function (): void {
    it('can be created', function (): void {
        $product = DepositProduct::factory()->create();

        $rate = DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 12,
            'min_amount' => 5000000,
            'max_amount' => 500000000,
            'interest_rate' => 6.25,
            'is_active' => true,
        ]);

        expect($rate)->toBeInstanceOf(DepositProductRate::class)
            ->and($rate->is_active)->toBeTrue()->toBeBool();
    });

    it('has depositProduct relationship', function (): void {
        $product = DepositProduct::factory()->create();
        $rate = DepositProductRate::create([
            'deposit_product_id' => $product->id,
            'tenor_months' => 3,
            'min_amount' => 1000000,
            'interest_rate' => 4.00,
            'is_active' => true,
        ]);

        expect($rate->depositProduct())->toBeInstanceOf(BelongsTo::class)
            ->and($rate->depositProduct->id)->toBe($product->id);
    });
});

// ============================================================================
// DepositAccount
// ============================================================================
describe('DepositAccount', function (): void {
    it('can be created with factory', function (): void {
        $account = DepositAccount::factory()->create();

        expect($account)->toBeInstanceOf(DepositAccount::class)
            ->and($account->account_number)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $account = DepositAccount::factory()->create([
            'status' => DepositStatus::Active,
            'interest_payment_method' => InterestPaymentMethod::Monthly,
            'rollover_type' => RolloverType::PrincipalOnly,
        ]);

        expect($account->status)->toBe(DepositStatus::Active)
            ->and($account->interest_payment_method)->toBe(InterestPaymentMethod::Monthly)
            ->and($account->rollover_type)->toBe(RolloverType::PrincipalOnly)
            ->and($account->is_pledged)->toBeBool()
            ->and($account->placement_date)->toBeInstanceOf(Carbon::class)
            ->and($account->maturity_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $account = DepositAccount::factory()->create();

        expect($account->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($account->depositProduct())->toBeInstanceOf(BelongsTo::class)
            ->and($account->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($account->savingsAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($account->creator())->toBeInstanceOf(BelongsTo::class)
            ->and($account->transactions())->toBeInstanceOf(HasMany::class)
            ->and($account->interestAccruals())->toBeInstanceOf(HasMany::class);
    });

    it('scope active filters correctly', function (): void {
        DepositAccount::factory()->create(['status' => DepositStatus::Active]);
        DepositAccount::factory()->create(['status' => DepositStatus::Closed]);

        $active = DepositAccount::active()->get();

        expect($active->every(fn ($a): bool => $a->status === DepositStatus::Active))->toBeTrue();
    });

    it('scope maturing filters correctly', function (): void {
        DepositAccount::factory()->create([
            'status' => DepositStatus::Active,
            'maturity_date' => now()->subDay(),
        ]);
        DepositAccount::factory()->create([
            'status' => DepositStatus::Active,
            'maturity_date' => now()->addYear(),
        ]);

        $maturing = DepositAccount::maturing(now())->get();

        expect($maturing)->toHaveCount(1);
    });

    it('isMatured returns true when maturity date is past', function (): void {
        $account = DepositAccount::factory()->create([
            'maturity_date' => now()->subDay(),
        ]);

        expect($account->isMatured())->toBeTrue();
    });

    it('isMatured returns false when maturity date is future', function (): void {
        $account = DepositAccount::factory()->create([
            'maturity_date' => now()->addMonth(),
        ]);

        expect($account->isMatured())->toBeFalse();
    });

    it('daysToMaturity returns correct value', function (): void {
        $account = DepositAccount::factory()->create([
            'maturity_date' => now()->addDays(30),
        ]);

        expect($account->daysToMaturity())->toBe(30);
    });

    it('daysToMaturity returns 0 for past dates', function (): void {
        $account = DepositAccount::factory()->create([
            'maturity_date' => now()->subDays(10),
        ]);

        expect($account->daysToMaturity())->toBe(0);
    });
});

// ============================================================================
// DepositTransaction
// ============================================================================
describe('DepositTransaction', function (): void {
    it('can be created', function (): void {
        $account = DepositAccount::factory()->create();

        $txn = DepositTransaction::create([
            'reference_number' => 'DTXN001',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'placement',
            'amount' => 50000000,
            'description' => 'Initial placement',
            'transaction_date' => now(),
            'performed_by' => User::factory()->create()->id,
        ]);

        expect($txn)->toBeInstanceOf(DepositTransaction::class)
            ->and($txn->transaction_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $account = DepositAccount::factory()->create();
        $txn = DepositTransaction::create([
            'reference_number' => 'DTXN002',
            'deposit_account_id' => $account->id,
            'transaction_type' => 'interest',
            'amount' => 500000,
            'transaction_date' => now(),
            'performed_by' => User::factory()->create()->id,
        ]);

        expect($txn->depositAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->performer())->toBeInstanceOf(BelongsTo::class);
    });
});

// ============================================================================
// DepositInterestAccrual
// ============================================================================
describe('DepositInterestAccrual', function (): void {
    it('can be created', function (): void {
        $account = DepositAccount::factory()->create();

        $accrual = DepositInterestAccrual::create([
            'deposit_account_id' => $account->id,
            'accrual_date' => now(),
            'principal' => 50000000,
            'interest_rate' => 5.50,
            'accrued_amount' => 7534.25,
            'tax_amount' => 1506.85,
            'is_posted' => true,
            'posted_at' => now(),
        ]);

        expect($accrual)->toBeInstanceOf(DepositInterestAccrual::class)
            ->and($accrual->accrual_date)->toBeInstanceOf(Carbon::class)
            ->and($accrual->is_posted)->toBeTrue()->toBeBool();
    });

    it('has depositAccount relationship', function (): void {
        $account = DepositAccount::factory()->create();
        $accrual = DepositInterestAccrual::create([
            'deposit_account_id' => $account->id,
            'accrual_date' => now(),
            'principal' => 50000000,
            'interest_rate' => 5.50,
            'accrued_amount' => 7534.25,
            'tax_amount' => 0,
            'is_posted' => false,
        ]);

        expect($accrual->depositAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($accrual->depositAccount->id)->toBe($account->id);
    });
});

// ============================================================================
// JournalEntry
// ============================================================================
describe('JournalEntry', function (): void {
    it('can be created with factory', function (): void {
        $journal = JournalEntry::factory()->create();

        expect($journal)->toBeInstanceOf(JournalEntry::class)
            ->and($journal->journal_number)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $journal = JournalEntry::factory()->create([
            'source' => JournalSource::Manual,
            'status' => JournalStatus::Draft,
        ]);

        expect($journal->source)->toBe(JournalSource::Manual)
            ->and($journal->status)->toBe(JournalStatus::Draft)
            ->and($journal->approval_status)->toBeInstanceOf(ApprovalStatus::class)
            ->and($journal->journal_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $journal = JournalEntry::factory()->create();

        expect($journal->lines())->toBeInstanceOf(HasMany::class)
            ->and($journal->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($journal->reversedBy())->toBeInstanceOf(BelongsTo::class)
            ->and($journal->reversalJournal())->toBeInstanceOf(BelongsTo::class);
    });

    it('scope posted filters correctly', function (): void {
        JournalEntry::factory()->posted()->create();
        JournalEntry::factory()->create(['status' => JournalStatus::Draft]);

        $posted = JournalEntry::posted()->get();

        expect($posted->every(fn ($j): bool => $j->status === JournalStatus::Posted))->toBeTrue();
    });

    it('scope bySource filters correctly', function (): void {
        JournalEntry::factory()->create(['source' => JournalSource::Manual]);
        JournalEntry::factory()->create(['source' => JournalSource::System]);

        $manual = JournalEntry::bySource(JournalSource::Manual)->get();

        expect($manual->every(fn ($j): bool => $j->source === JournalSource::Manual))->toBeTrue();
    });

    it('scope byDateRange filters correctly', function (): void {
        JournalEntry::factory()->create(['journal_date' => '2026-03-01']);
        JournalEntry::factory()->create(['journal_date' => '2026-04-15']);

        $range = JournalEntry::byDateRange('2026-03-01', '2026-03-31')->get();

        expect($range)->toHaveCount(1);
    });

    it('isBalanced returns true when debits equal credits', function (): void {
        $journal = JournalEntry::factory()->create([
            'total_debit' => 1000000,
            'total_credit' => 1000000,
        ]);

        expect($journal->isBalanced())->toBeTrue();
    });

    it('isBalanced returns false when debits do not equal credits', function (): void {
        $journal = JournalEntry::factory()->create([
            'total_debit' => 1000000,
            'total_credit' => 999999,
        ]);

        expect($journal->isBalanced())->toBeFalse();
    });

    it('isDraft returns true for draft status', function (): void {
        $journal = JournalEntry::factory()->create(['status' => JournalStatus::Draft]);

        expect($journal->isDraft())->toBeTrue();
    });

    it('isPosted returns true for posted status', function (): void {
        $journal = JournalEntry::factory()->posted()->create();

        expect($journal->isPosted())->toBeTrue();
    });

    it('isReversed returns true for reversed status', function (): void {
        $journal = JournalEntry::factory()->create(['status' => JournalStatus::Reversed]);

        expect($journal->isReversed())->toBeTrue();
    });

    it('recalculateTotals sums lines', function (): void {
        $journal = JournalEntry::factory()->create([
            'total_debit' => 0,
            'total_credit' => 0,
        ]);
        $coa = ChartOfAccount::factory()->create();
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Debit',
            'debit' => 500000,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Credit',
            'debit' => 0,
            'credit' => 500000,
        ]);

        $journal->recalculateTotals();
        $journal->refresh();

        expect($journal->total_debit)->toBe('500000.00')
            ->and($journal->total_credit)->toBe('500000.00');
    });
});

// ============================================================================
// JournalEntryLine
// ============================================================================
describe('JournalEntryLine', function (): void {
    it('can be created', function (): void {
        $journal = JournalEntry::factory()->create();
        $coa = ChartOfAccount::factory()->create();

        $line = JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'description' => 'Test line',
            'debit' => 1000000,
            'credit' => 0,
        ]);

        expect($line)->toBeInstanceOf(JournalEntryLine::class);
    });

    it('has relationships', function (): void {
        $journal = JournalEntry::factory()->create();
        $coa = ChartOfAccount::factory()->create();
        $line = JournalEntryLine::create([
            'journal_entry_id' => $journal->id,
            'chart_of_account_id' => $coa->id,
            'debit' => 1000000,
            'credit' => 0,
        ]);

        expect($line->journalEntry())->toBeInstanceOf(BelongsTo::class)
            ->and($line->chartOfAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($line->journalEntry->id)->toBe($journal->id)
            ->and($line->chartOfAccount->id)->toBe($coa->id);
    });
});

// ============================================================================
// GlBalance
// ============================================================================
describe('GlBalance', function (): void {
    it('can be created', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $branch = Branch::factory()->create();

        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 10000000,
            'debit_total' => 5000000,
            'credit_total' => 3000000,
            'closing_balance' => 12000000,
        ]);

        expect($balance)->toBeInstanceOf(GlBalance::class);
    });

    it('has relationships', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $branch = Branch::factory()->create();
        $balance = GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 0,
            'debit_total' => 0,
            'credit_total' => 0,
            'closing_balance' => 0,
        ]);

        expect($balance->chartOfAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($balance->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($balance->chartOfAccount->id)->toBe($coa->id);
    });

    it('scope forPeriod filters correctly', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $branch = Branch::factory()->create();
        GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $branch->id,
            'period_year' => 2026,
            'period_month' => 3,
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);
        GlBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $branch->id,
            'period_year' => 2026,
            'period_month' => 4,
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);

        $march = GlBalance::forPeriod(2026, 3)->get();

        expect($march)->toHaveCount(1);
    });
});

// ============================================================================
// GlDailyBalance
// ============================================================================
describe('GlDailyBalance', function (): void {
    it('can be created', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $branch = Branch::factory()->create();

        $balance = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $branch->id,
            'balance_date' => '2026-03-11',
            'opening_balance' => 10000000,
            'debit_total' => 2000000,
            'credit_total' => 1000000,
            'closing_balance' => 11000000,
        ]);

        expect($balance)->toBeInstanceOf(GlDailyBalance::class)
            ->and($balance->balance_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $branch = Branch::factory()->create();
        $balance = GlDailyBalance::create([
            'chart_of_account_id' => $coa->id,
            'branch_id' => $branch->id,
            'balance_date' => '2026-03-11',
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);

        expect($balance->chartOfAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($balance->branch())->toBeInstanceOf(BelongsTo::class);
    });

    it('scope forDate filters correctly', function (): void {
        $coa = ChartOfAccount::factory()->create();
        $branch = Branch::factory()->create();
        GlDailyBalance::create([
            'chart_of_account_id' => $coa->id, 'branch_id' => $branch->id,
            'balance_date' => '2026-03-11',
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);
        GlDailyBalance::create([
            'chart_of_account_id' => $coa->id, 'branch_id' => $branch->id,
            'balance_date' => '2026-03-12',
            'opening_balance' => 0, 'debit_total' => 0, 'credit_total' => 0, 'closing_balance' => 0,
        ]);

        $result = GlDailyBalance::forDate('2026-03-11')->get();

        expect($result)->toHaveCount(1);
    });
});

// ============================================================================
// LoanProduct
// ============================================================================
describe('LoanProduct', function (): void {
    it('can be created with factory', function (): void {
        $product = LoanProduct::factory()->create();

        expect($product)->toBeInstanceOf(LoanProduct::class)
            ->and($product->code)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $product = LoanProduct::factory()->create([
            'loan_type' => LoanType::Kmk,
            'interest_type' => InterestType::Flat,
        ]);

        expect($product->loan_type)->toBe(LoanType::Kmk)
            ->and($product->interest_type)->toBe(InterestType::Flat)
            ->and($product->is_active)->toBeBool();
    });

    it('has relationships', function (): void {
        $product = LoanProduct::factory()->create();

        expect($product->accounts())->toBeInstanceOf(HasMany::class)
            ->and($product->applications())->toBeInstanceOf(HasMany::class)
            ->and($product->glLoan())->toBeInstanceOf(BelongsTo::class)
            ->and($product->glInterestIncome())->toBeInstanceOf(BelongsTo::class);
    });

    it('scope active filters correctly', function (): void {
        LoanProduct::factory()->create(['is_active' => true]);
        LoanProduct::factory()->create(['is_active' => false]);

        $active = LoanProduct::active()->get();

        expect($active->every(fn ($p) => $p->is_active))->toBeTrue();
    });
});

// ============================================================================
// LoanApplication
// ============================================================================
describe('LoanApplication', function (): void {
    it('can be created with factory', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        $branch = Branch::factory()->create();
        $user = User::factory()->create();

        $app = LoanApplication::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'loan_officer_id' => $user->id,
            'created_by' => $user->id,
        ]);

        expect($app)->toBeInstanceOf(LoanApplication::class)
            ->and($app->application_number)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
            'status' => LoanApplicationStatus::Submitted,
        ]);

        expect($app->status)->toBe(LoanApplicationStatus::Submitted);
    });

    it('has relationships', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'loan_officer_id' => User::factory(),
            'created_by' => User::factory(),
        ]);

        expect($app->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($app->loanProduct())->toBeInstanceOf(BelongsTo::class)
            ->and($app->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($app->loanOfficer())->toBeInstanceOf(BelongsTo::class)
            ->and($app->creator())->toBeInstanceOf(BelongsTo::class)
            ->and($app->approver())->toBeInstanceOf(BelongsTo::class)
            ->and($app->collaterals())->toBeInstanceOf(HasMany::class);
    });

    it('scope pending filters correctly', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        LoanApplication::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
            'status' => LoanApplicationStatus::Submitted,
        ]);
        LoanApplication::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
            'status' => LoanApplicationStatus::Approved,
        ]);

        $pending = LoanApplication::pending()->get();

        expect($pending->every(fn ($a): bool => in_array($a->status, [
            LoanApplicationStatus::Submitted,
            LoanApplicationStatus::UnderReview,
        ])))->toBeTrue();
    });
});

// ============================================================================
// LoanSchedule
// ============================================================================
describe('LoanSchedule', function (): void {
    it('can be created with factory', function (): void {
        $schedule = LoanSchedule::factory()->create();

        expect($schedule)->toBeInstanceOf(LoanSchedule::class)
            ->and($schedule->due_date)->toBeInstanceOf(Carbon::class)
            ->and($schedule->is_paid)->toBeBool();
    });

    it('has loanAccount relationship', function (): void {
        $schedule = LoanSchedule::factory()->create();

        expect($schedule->loanAccount())->toBeInstanceOf(BelongsTo::class);
    });

    it('getRemainingPrincipal calculates correctly', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'principal_amount' => 1000000,
            'principal_paid' => 400000,
        ]);

        expect($schedule->getRemainingPrincipal())->toBe(600000.00);
    });

    it('getRemainingInterest calculates correctly', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'interest_amount' => 100000,
            'interest_paid' => 60000,
        ]);

        expect($schedule->getRemainingInterest())->toBe(40000.00);
    });

    it('isOverdue returns true for past unpaid', function (): void {
        $schedule = LoanSchedule::factory()->overdue()->create();

        expect($schedule->isOverdue())->toBeTrue();
    });

    it('isOverdue returns false for paid schedules', function (): void {
        $schedule = LoanSchedule::factory()->paid()->create();

        expect($schedule->isOverdue())->toBeFalse();
    });

    it('isOverdue returns false for future unpaid', function (): void {
        $schedule = LoanSchedule::factory()->create([
            'due_date' => now()->addMonth(),
            'is_paid' => false,
        ]);

        expect($schedule->isOverdue())->toBeFalse();
    });
});

// ============================================================================
// LoanPayment
// ============================================================================
describe('LoanPayment', function (): void {
    it('can be created', function (): void {
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        $payment = LoanPayment::create([
            'reference_number' => 'PAY001',
            'loan_account_id' => $loanAccount->id,
            'payment_type' => 'installment',
            'amount' => 2000000,
            'principal_portion' => 1500000,
            'interest_portion' => 500000,
            'penalty_portion' => 0,
            'payment_date' => now(),
            'description' => 'Monthly installment',
            'performed_by' => User::factory()->create()->id,
        ]);

        expect($payment)->toBeInstanceOf(LoanPayment::class)
            ->and($payment->payment_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $loanAccount = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        $payment = LoanPayment::create([
            'reference_number' => 'PAY002',
            'loan_account_id' => $loanAccount->id,
            'payment_type' => 'installment',
            'amount' => 1000000,
            'principal_portion' => 800000,
            'interest_portion' => 200000,
            'penalty_portion' => 0,
            'payment_date' => now(),
            'performed_by' => User::factory()->create()->id,
        ]);

        expect($payment->loanAccount())->toBeInstanceOf(BelongsTo::class)
            ->and($payment->performer())->toBeInstanceOf(BelongsTo::class)
            ->and($payment->journalEntry())->toBeInstanceOf(BelongsTo::class);
    });
});

// ============================================================================
// LoanCollateral
// ============================================================================
describe('LoanCollateral', function (): void {
    it('can be created', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        $collateral = LoanCollateral::create([
            'loan_application_id' => $app->id,
            'collateral_type' => CollateralType::Land,
            'description' => 'Land certificate',
            'document_number' => 'SHM/123/2026',
            'appraised_value' => 500000000,
            'liquidation_value' => 350000000,
            'location' => 'Jakarta',
            'ownership_name' => 'John Doe',
        ]);

        expect($collateral)->toBeInstanceOf(LoanCollateral::class)
            ->and($collateral->collateral_type)->toBe(CollateralType::Land);
    });

    it('has relationships', function (): void {
        $app = LoanApplication::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        $collateral = LoanCollateral::create([
            'loan_application_id' => $app->id,
            'collateral_type' => CollateralType::Vehicle,
            'description' => 'Car',
            'appraised_value' => 200000000,
            'liquidation_value' => 150000000,
        ]);

        expect($collateral->loanApplication())->toBeInstanceOf(BelongsTo::class)
            ->and($collateral->loanAccount())->toBeInstanceOf(BelongsTo::class);
    });
});

// ============================================================================
// LoanAccount
// ============================================================================
describe('LoanAccount', function (): void {
    it('can be created with factory', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);

        expect($account)->toBeInstanceOf(LoanAccount::class)
            ->and($account->account_number)->toBeString();
    });

    it('casts enums correctly', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
            'status' => LoanStatus::Active,
            'collectibility' => Collectibility::Current,
        ]);

        expect($account->status)->toBe(LoanStatus::Active)
            ->and($account->collectibility)->toBe(Collectibility::Current)
            ->and($account->disbursement_date)->toBeInstanceOf(Carbon::class)
            ->and($account->maturity_date)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'loan_officer_id' => User::factory(),
            'created_by' => User::factory(),
        ]);

        expect($account->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($account->loanProduct())->toBeInstanceOf(BelongsTo::class)
            ->and($account->loanApplication())->toBeInstanceOf(BelongsTo::class)
            ->and($account->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($account->loanOfficer())->toBeInstanceOf(BelongsTo::class)
            ->and($account->creator())->toBeInstanceOf(BelongsTo::class)
            ->and($account->schedules())->toBeInstanceOf(HasMany::class)
            ->and($account->payments())->toBeInstanceOf(HasMany::class)
            ->and($account->collaterals())->toBeInstanceOf(HasMany::class);
    });

    it('scope active filters correctly', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        LoanAccount::factory()->create(['customer_id' => $customer->id, 'branch_id' => Branch::factory(), 'created_by' => User::factory(), 'status' => LoanStatus::Active]);
        LoanAccount::factory()->create(['customer_id' => $customer->id, 'branch_id' => Branch::factory(), 'created_by' => User::factory(), 'status' => LoanStatus::Closed]);

        $active = LoanAccount::active()->get();

        expect($active->every(fn ($a): bool => in_array($a->status, [LoanStatus::Active, LoanStatus::Current, LoanStatus::Overdue])))->toBeTrue();
    });

    it('scope byCollectibility filters correctly', function (): void {
        $customer = Customer::factory()->create([
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        LoanAccount::factory()->create(['customer_id' => $customer->id, 'branch_id' => Branch::factory(), 'created_by' => User::factory(), 'collectibility' => Collectibility::Current]);
        LoanAccount::factory()->create(['customer_id' => $customer->id, 'branch_id' => Branch::factory(), 'created_by' => User::factory(), 'collectibility' => Collectibility::Substandard]);

        $current = LoanAccount::byCollectibility(Collectibility::Current)->get();

        expect($current->every(fn ($a): bool => $a->collectibility === Collectibility::Current))->toBeTrue();
    });

    it('getNextUnpaidSchedule returns earliest unpaid', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        LoanSchedule::factory()->paid()->create(['loan_account_id' => $account->id, 'installment_number' => 1]);
        $unpaid = LoanSchedule::factory()->create(['loan_account_id' => $account->id, 'installment_number' => 2, 'is_paid' => false]);
        LoanSchedule::factory()->create(['loan_account_id' => $account->id, 'installment_number' => 3, 'is_paid' => false]);

        $next = $account->getNextUnpaidSchedule();

        expect($next)->toBeInstanceOf(LoanSchedule::class)
            ->and($next->installment_number)->toBe(2);
    });

    it('getNextUnpaidSchedule returns null when all paid', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        LoanSchedule::factory()->paid()->create(['loan_account_id' => $account->id, 'installment_number' => 1]);

        expect($account->getNextUnpaidSchedule())->toBeNull();
    });

    it('getOverdueSchedules returns past due unpaid schedules', function (): void {
        $account = LoanAccount::factory()->create([
            'customer_id' => Customer::factory()->create([
                'branch_id' => Branch::factory(),
                'created_by' => User::factory(),
            ])->id,
            'branch_id' => Branch::factory(),
            'created_by' => User::factory(),
        ]);
        LoanSchedule::factory()->overdue()->create(['loan_account_id' => $account->id, 'installment_number' => 1]);
        LoanSchedule::factory()->overdue()->create(['loan_account_id' => $account->id, 'installment_number' => 2]);
        LoanSchedule::factory()->create(['loan_account_id' => $account->id, 'installment_number' => 3, 'due_date' => now()->addMonth()]);

        $overdue = $account->getOverdueSchedules();

        expect($overdue)->toHaveCount(2)
            ->and($overdue->first()->installment_number)->toBe(1);
    });
});

// ============================================================================
// Vault
// ============================================================================
describe('Vault', function (): void {
    it('can be created with factory', function (): void {
        $vault = Vault::factory()->create();

        expect($vault)->toBeInstanceOf(Vault::class)
            ->and($vault->code)->toBeString();
    });

    it('casts correctly', function (): void {
        $vault = Vault::factory()->create();

        expect($vault->is_active)->toBeBool();
    });

    it('has relationships', function (): void {
        $vault = Vault::factory()->create();

        expect($vault->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($vault->custodian())->toBeInstanceOf(BelongsTo::class)
            ->and($vault->transactions())->toBeInstanceOf(HasMany::class)
            ->and($vault->tellerSessions())->toBeInstanceOf(HasMany::class);
    });

    it('scope active filters correctly', function (): void {
        Vault::factory()->create(['is_active' => true]);
        Vault::factory()->inactive()->create();

        $active = Vault::active()->get();

        expect($active->every(fn ($v) => $v->is_active))->toBeTrue();
    });
});

// ============================================================================
// VaultTransaction
// ============================================================================
describe('VaultTransaction', function (): void {
    it('can be created', function (): void {
        $vault = Vault::factory()->create();
        $user = User::factory()->create();

        $txn = VaultTransaction::create([
            'reference_number' => 'VT001',
            'vault_id' => $vault->id,
            'transaction_type' => VaultTransactionType::CashIn,
            'amount' => 50000000,
            'balance_before' => 100000000,
            'balance_after' => 150000000,
            'description' => 'Cash delivery',
            'performed_by' => $user->id,
            'approved_by' => User::factory()->create()->id,
        ]);

        expect($txn)->toBeInstanceOf(VaultTransaction::class)
            ->and($txn->transaction_type)->toBe(VaultTransactionType::CashIn);
    });

    it('has relationships', function (): void {
        $vault = Vault::factory()->create();
        $user = User::factory()->create();
        $approver = User::factory()->create();
        $txn = VaultTransaction::create([
            'reference_number' => 'VT002',
            'vault_id' => $vault->id,
            'transaction_type' => VaultTransactionType::CashOut,
            'amount' => 10000000,
            'balance_before' => 100000000,
            'balance_after' => 90000000,
            'performed_by' => $user->id,
            'approved_by' => $approver->id,
        ]);

        expect($txn->vault())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->performer())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->approver())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->vault->id)->toBe($vault->id)
            ->and($txn->performer->id)->toBe($user->id)
            ->and($txn->approver->id)->toBe($approver->id);
    });
});

// ============================================================================
// TellerSession
// ============================================================================
describe('TellerSession', function (): void {
    it('can be created with factory', function (): void {
        $session = TellerSession::factory()->create();

        expect($session)->toBeInstanceOf(TellerSession::class);
    });

    it('casts enum and datetimes correctly', function (): void {
        $session = TellerSession::factory()->create([
            'status' => TellerSessionStatus::Open,
        ]);

        expect($session->status)->toBe(TellerSessionStatus::Open)
            ->and($session->opened_at)->toBeInstanceOf(Carbon::class);
    });

    it('has relationships', function (): void {
        $session = TellerSession::factory()->create();

        expect($session->user())->toBeInstanceOf(BelongsTo::class)
            ->and($session->branch())->toBeInstanceOf(BelongsTo::class)
            ->and($session->vault())->toBeInstanceOf(BelongsTo::class)
            ->and($session->transactions())->toBeInstanceOf(HasMany::class);
    });

    it('isOpen returns true for open sessions', function (): void {
        $session = TellerSession::factory()->create(['status' => TellerSessionStatus::Open]);

        expect($session->isOpen())->toBeTrue();
    });

    it('isOpen returns false for closed sessions', function (): void {
        $session = TellerSession::factory()->closed()->create();

        expect($session->isOpen())->toBeFalse();
    });

    it('scope open filters correctly', function (): void {
        TellerSession::factory()->create(['status' => TellerSessionStatus::Open]);
        TellerSession::factory()->closed()->create();

        $open = TellerSession::open()->get();

        expect($open->every(fn ($s): bool => $s->status === TellerSessionStatus::Open))->toBeTrue();
    });

    it('scope forUser filters correctly', function (): void {
        $user = User::factory()->create();
        TellerSession::factory()->create(['user_id' => $user->id]);
        TellerSession::factory()->create();

        $sessions = TellerSession::forUser($user->id)->get();

        expect($sessions->every(fn ($s): bool => $s->user_id === $user->id))->toBeTrue();
    });
});

// ============================================================================
// TellerTransaction
// ============================================================================
describe('TellerTransaction', function (): void {
    it('can be created', function (): void {
        $session = TellerSession::factory()->create();

        $txn = TellerTransaction::create([
            'reference_number' => 'TT001',
            'teller_session_id' => $session->id,
            'transaction_type' => TellerTransactionType::SavingsDeposit,
            'amount' => 5000000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 15000000,
            'direction' => 'in',
            'description' => 'Savings deposit',
            'performed_by' => $session->user_id,
            'is_reversed' => false,
            'needs_authorization' => false,
        ]);

        expect($txn)->toBeInstanceOf(TellerTransaction::class)
            ->and($txn->transaction_type)->toBe(TellerTransactionType::SavingsDeposit)
            ->and($txn->is_reversed)->toBeFalse()->toBeBool()
            ->and($txn->needs_authorization)->toBeFalse()->toBeBool();
    });

    it('has relationships', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT002',
            'teller_session_id' => $session->id,
            'transaction_type' => TellerTransactionType::SavingsWithdrawal,
            'amount' => 1000000,
            'teller_balance_before' => 15000000,
            'teller_balance_after' => 14000000,
            'direction' => 'out',
            'performed_by' => $session->user_id,
            'is_reversed' => false,
            'needs_authorization' => false,
        ]);

        expect($txn->tellerSession())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->customer())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->performer())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->authorizer())->toBeInstanceOf(BelongsTo::class)
            ->and($txn->reversedBy())->toBeInstanceOf(BelongsTo::class);
    });

    it('isCashIn returns true for in direction', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT003',
            'teller_session_id' => $session->id,
            'transaction_type' => TellerTransactionType::SavingsDeposit,
            'amount' => 1000000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 11000000,
            'direction' => 'in',
            'performed_by' => $session->user_id,
            'is_reversed' => false,
            'needs_authorization' => false,
        ]);

        expect($txn->isCashIn())->toBeTrue()
            ->and($txn->isCashOut())->toBeFalse();
    });

    it('isCashOut returns true for out direction', function (): void {
        $session = TellerSession::factory()->create();
        $txn = TellerTransaction::create([
            'reference_number' => 'TT004',
            'teller_session_id' => $session->id,
            'transaction_type' => TellerTransactionType::SavingsWithdrawal,
            'amount' => 1000000,
            'teller_balance_before' => 10000000,
            'teller_balance_after' => 9000000,
            'direction' => 'out',
            'performed_by' => $session->user_id,
            'is_reversed' => false,
            'needs_authorization' => false,
        ]);

        expect($txn->isCashOut())->toBeTrue()
            ->and($txn->isCashIn())->toBeFalse();
    });
});

// ============================================================================
// EodProcess
// ============================================================================
describe('EodProcess', function (): void {
    it('can be created', function (): void {
        $user = User::factory()->create();

        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Pending,
            'total_steps' => 10,
            'completed_steps' => 0,
            'started_by' => $user->id,
        ]);

        expect($eod)->toBeInstanceOf(EodProcess::class)
            ->and($eod->process_date)->toBeInstanceOf(Carbon::class)
            ->and($eod->status)->toBe(EodStatus::Pending);
    });

    it('has relationships', function (): void {
        $user = User::factory()->create();
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Pending,
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => $user->id,
        ]);

        expect($eod->steps())->toBeInstanceOf(HasMany::class)
            ->and($eod->startedBy())->toBeInstanceOf(BelongsTo::class)
            ->and($eod->startedBy->id)->toBe($user->id);
    });

    it('isRunning returns true for running status', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 10,
            'completed_steps' => 3,
            'started_at' => now(),
            'started_by' => User::factory()->create()->id,
        ]);

        expect($eod->isRunning())->toBeTrue()
            ->and($eod->isCompleted())->toBeFalse();
    });

    it('isCompleted returns true for completed status', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Completed,
            'total_steps' => 10,
            'completed_steps' => 10,
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'started_by' => User::factory()->create()->id,
        ]);

        expect($eod->isCompleted())->toBeTrue()
            ->and($eod->isRunning())->toBeFalse();
    });

    it('progressPercentage calculates correctly', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 10,
            'completed_steps' => 7,
            'started_by' => User::factory()->create()->id,
        ]);

        expect($eod->progressPercentage())->toBe(70);
    });

    it('progressPercentage returns 0 when total_steps is 0', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Pending,
            'total_steps' => 0,
            'completed_steps' => 0,
            'started_by' => User::factory()->create()->id,
        ]);

        expect($eod->progressPercentage())->toBe(0);
    });

    it('progressPercentage returns 100 when complete', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Completed,
            'total_steps' => 8,
            'completed_steps' => 8,
            'started_by' => User::factory()->create()->id,
        ]);

        expect($eod->progressPercentage())->toBe(100);
    });
});

// ============================================================================
// EodProcessStep
// ============================================================================
describe('EodProcessStep', function (): void {
    it('can be created', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => User::factory()->create()->id,
        ]);

        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Interest Accrual',
            'status' => EodStatus::Completed,
            'records_processed' => 150,
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
            'metadata' => ['accounts_processed' => 150],
        ]);

        expect($step)->toBeInstanceOf(EodProcessStep::class)
            ->and($step->status)->toBe(EodStatus::Completed)
            ->and($step->started_at)->toBeInstanceOf(Carbon::class)
            ->and($step->completed_at)->toBeInstanceOf(Carbon::class)
            ->and($step->metadata)->toBeArray();
    });

    it('has eodProcess relationship', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => User::factory()->create()->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Test Step',
            'status' => EodStatus::Pending,
        ]);

        expect($step->eodProcess())->toBeInstanceOf(BelongsTo::class)
            ->and($step->eodProcess->id)->toBe($eod->id);
    });

    it('durationInSeconds calculates correctly', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 1,
            'started_by' => User::factory()->create()->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Step 1',
            'status' => EodStatus::Completed,
            'started_at' => now()->subSeconds(120),
            'completed_at' => now(),
        ]);

        expect($step->durationInSeconds())->toBe(120);
    });

    it('durationInSeconds returns null when incomplete', function (): void {
        $eod = EodProcess::create([
            'process_date' => now(),
            'status' => EodStatus::Running,
            'total_steps' => 5,
            'completed_steps' => 0,
            'started_by' => User::factory()->create()->id,
        ]);
        $step = EodProcessStep::create([
            'eod_process_id' => $eod->id,
            'step_number' => 1,
            'step_name' => 'Step 1',
            'status' => EodStatus::Running,
            'started_at' => now(),
        ]);

        expect($step->durationInSeconds())->toBeNull();
    });
});
