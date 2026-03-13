<?php

use App\Filament\Pages\AuditTrailPage;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsAccount;
use App\Models\User;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
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

it('can render audit trail page', function (): void {
    Livewire::test(AuditTrailPage::class)
        ->assertOk();
});

it('initializes with default filter values', function (): void {
    Livewire::test(AuditTrailPage::class)
        ->assertSet('dateFrom', now()->subDays(7)->format('Y-m-d'))
        ->assertSet('dateTo', now()->format('Y-m-d'))
        ->assertSet('modelType', '')
        ->assertSet('eventType', '')
        ->assertSet('userId', null)
        ->assertSet('search', '');
});

it('filters audits by model type', function (): void {
    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 1,
        'old_values' => '{}',
        'new_values' => '{"name":"Test"}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => 1,
        'old_values' => '{}',
        'new_values' => '{"name":"Admin"}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AuditTrailPage::class)
        ->set('modelType', Customer::class);

    $audits = $component->instance()->audits;
    expect($audits->total())->toBe(1);
});

it('filters audits by event type', function (): void {
    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 1,
        'old_values' => '{}',
        'new_values' => '{"name":"Test"}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'updated',
        'auditable_type' => Customer::class,
        'auditable_id' => 1,
        'old_values' => '{"name":"Test"}',
        'new_values' => '{"name":"Updated"}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AuditTrailPage::class)
        ->set('eventType', 'updated');

    $audits = $component->instance()->audits;
    expect($audits->total())->toBe(1);
});

it('filters audits by user', function (): void {
    $otherUser = User::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 1,
        'old_values' => '{}',
        'new_values' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 2,
        'old_values' => '{}',
        'new_values' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AuditTrailPage::class)
        ->set('userId', $this->user->id);

    $audits = $component->instance()->audits;
    expect($audits->total())->toBe(1);
});

it('filters audits by date range', function (): void {
    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 1,
        'old_values' => '{}',
        'new_values' => '{}',
        'created_at' => now()->subDays(2),
        'updated_at' => now()->subDays(2),
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 2,
        'old_values' => '{}',
        'new_values' => '{}',
        'created_at' => now()->subDays(30),
        'updated_at' => now()->subDays(30),
    ]);

    $component = Livewire::test(AuditTrailPage::class)
        ->set('dateFrom', now()->subDays(5)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'));

    $audits = $component->instance()->audits;
    expect($audits->total())->toBe(1);
});

it('searches audits by account number', function (): void {
    $savingsAccount = SavingsAccount::factory()->create([
        'account_number' => 'T99001000001234',
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'updated',
        'auditable_type' => SavingsAccount::class,
        'auditable_id' => $savingsAccount->id,
        'old_values' => '{"balance":"1000"}',
        'new_values' => '{"balance":"2000"}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 999,
        'old_values' => '{}',
        'new_values' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AuditTrailPage::class)
        ->set('search', 'T99001000001234');

    $audits = $component->instance()->audits;
    expect($audits->total())->toBe(1);
});

it('toggles expand on audit entry', function (): void {
    Livewire::test(AuditTrailPage::class)
        ->assertSet('expandedAuditId', null)
        ->call('toggleExpand', 42)
        ->assertSet('expandedAuditId', 42)
        ->call('toggleExpand', 42)
        ->assertSet('expandedAuditId', null);
});

it('resets all filters to defaults', function (): void {
    Livewire::test(AuditTrailPage::class)
        ->set('modelType', Customer::class)
        ->set('eventType', 'updated')
        ->set('userId', 1)
        ->set('search', 'test')
        ->call('resetFilters')
        ->assertSet('dateFrom', now()->subDays(7)->format('Y-m-d'))
        ->assertSet('dateTo', now()->format('Y-m-d'))
        ->assertSet('modelType', '')
        ->assertSet('eventType', '')
        ->assertSet('userId', null)
        ->assertSet('search', '')
        ->assertSet('expandedAuditId', null);
});

it('returns correct stats', function (): void {
    Audit::create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'event' => 'created',
        'auditable_type' => Customer::class,
        'auditable_id' => 1,
        'old_values' => '{}',
        'new_values' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $component = Livewire::test(AuditTrailPage::class);
    $stats = $component->instance()->stats;

    expect($stats)->toBeArray()
        ->and($stats)->toHaveKeys(['total', 'today', 'most_active_model', 'most_active_user'])
        ->and($stats['total'])->toBe(1)
        ->and($stats['today'])->toBe(1)
        ->and($stats['most_active_model'])->toBe('Nasabah');
});

it('returns correct model labels', function (): void {
    $component = Livewire::test(AuditTrailPage::class);
    $instance = $component->instance();

    expect($instance->getModelLabel('App\Models\Customer'))->toBe('Nasabah')
        ->and($instance->getModelLabel('App\Models\SavingsAccount'))->toBe('Tabungan')
        ->and($instance->getModelLabel('App\Models\UnknownModel'))->toBe('UnknownModel');
});

it('returns correct event labels and colors', function (): void {
    $component = Livewire::test(AuditTrailPage::class);
    $instance = $component->instance();

    expect($instance->getEventLabel('created'))->toBe('Dibuat')
        ->and($instance->getEventLabel('updated'))->toBe('Diubah')
        ->and($instance->getEventLabel('deleted'))->toBe('Dihapus')
        ->and($instance->getEventLabel('restored'))->toBe('Dipulihkan');

    expect($instance->getEventColor('created'))->toBe('success')
        ->and($instance->getEventColor('updated'))->toBe('primary')
        ->and($instance->getEventColor('deleted'))->toBe('danger')
        ->and($instance->getEventColor('restored'))->toBe('info');
});

it('returns auditable types mapping', function (): void {
    $component = Livewire::test(AuditTrailPage::class);
    $types = $component->instance()->auditableTypes();

    expect($types)->toBeArray()
        ->and($types)->toHaveKey('App\Models\Customer', 'Nasabah')
        ->and($types)->toHaveKey('App\Models\SavingsAccount', 'Tabungan')
        ->and($types)->toHaveKey('App\Models\DepositAccount', 'Deposito')
        ->and($types)->toHaveKey('App\Models\LoanAccount', 'Pinjaman');
});
