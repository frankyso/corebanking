<?php

use App\Enums\JournalSource;
use App\Enums\JournalStatus;
use App\Filament\Resources\JournalEntryResource\Pages\ListJournalEntries;
use App\Filament\Resources\JournalEntryResource\Pages\ViewJournalEntry;
use App\Filament\Resources\JournalEntryResource\RelationManagers\LinesRelationManager;
use App\Models\Branch;
use App\Models\JournalEntry;
use App\Models\User;
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

it('can render journal entry list page with data', function (): void {
    $journals = JournalEntry::factory()
        ->count(3)
        ->create([
            'branch_id' => $this->branch->id,
            'created_by' => $this->user->id,
        ]);

    Livewire::test(ListJournalEntries::class)
        ->assertOk()
        ->assertCanSeeTableRecords($journals);
});

it('can search journal entries by journal number', function (): void {
    $target = JournalEntry::factory()->create([
        'journal_number' => 'JRN20260101XXXXX',
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    JournalEntry::factory()->create([
        'journal_number' => 'JRN20260102YYYYY',
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ListJournalEntries::class)
        ->searchTable('JRN20260101XXXXX')
        ->assertCanSeeTableRecords([$target]);
});

it('can filter journal entries by status', function (): void {
    $draft = JournalEntry::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'status' => JournalStatus::Draft,
    ]);

    $posted = JournalEntry::factory()->posted()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ListJournalEntries::class)
        ->filterTable('status', JournalStatus::Draft->value)
        ->assertCanSeeTableRecords([$draft])
        ->assertCanNotSeeTableRecords([$posted]);
});

it('can filter journal entries by source', function (): void {
    $manual = JournalEntry::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'source' => JournalSource::Manual,
    ]);

    $system = JournalEntry::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
        'source' => JournalSource::System,
    ]);

    Livewire::test(ListJournalEntries::class)
        ->filterTable('source', JournalSource::Manual->value)
        ->assertCanSeeTableRecords([$manual])
        ->assertCanNotSeeTableRecords([$system]);
});

it('can render journal entry view page', function (): void {
    $journal = JournalEntry::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(ViewJournalEntry::class, ['record' => $journal->getRouteKey()])
        ->assertOk();
});

it('can render lines relation manager', function (): void {
    $journal = JournalEntry::factory()->create([
        'branch_id' => $this->branch->id,
        'created_by' => $this->user->id,
    ]);

    Livewire::test(LinesRelationManager::class, [
        'ownerRecord' => $journal,
        'pageClass' => ViewJournalEntry::class,
    ])->assertOk();
});
