<?php

use App\Filament\Resources\HolidayResource\Pages\CreateHoliday;
use App\Filament\Resources\HolidayResource\Pages\EditHoliday;
use App\Filament\Resources\HolidayResource\Pages\ListHolidays;
use App\Models\Branch;
use App\Models\Holiday;
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

it('can render holiday list page with records', function (): void {
    $holidays = Holiday::factory()->count(3)->create();

    Livewire::test(ListHolidays::class)
        ->assertOk()
        ->assertCanSeeTableRecords($holidays)
        ->assertCanRenderTableColumn('date')
        ->assertCanRenderTableColumn('name')
        ->assertCanRenderTableColumn('type');
});

it('can search holidays by name', function (): void {
    $holidays = Holiday::factory()->count(3)->sequence(
        ['date' => '2027-04-01'],
        ['date' => '2027-04-02'],
        ['date' => '2027-04-03'],
    )->create();
    $target = $holidays->first();

    Livewire::test(ListHolidays::class)
        ->searchTable($target->name)
        ->assertCanSeeTableRecords($holidays->where('name', $target->name));
});

it('validates required fields on create form', function (): void {
    Livewire::test(CreateHoliday::class)
        ->fillForm([
            'date' => null,
            'name' => null,
            'type' => null,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'date' => 'required',
            'name' => 'required',
            'type' => 'required',
        ]);
});

it('can create a holiday', function (): void {
    Livewire::test(CreateHoliday::class)
        ->fillForm([
            'date' => '2026-12-25',
            'name' => 'Hari Natal',
            'type' => 'national',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas(Holiday::class, [
        'date' => '2026-12-25',
        'name' => 'Hari Natal',
        'type' => 'national',
    ]);
});

it('can load edit page with holiday data', function (): void {
    $holiday = Holiday::factory()->create([
        'name' => 'Hari Kemerdekaan',
        'type' => 'national',
    ]);

    Livewire::test(EditHoliday::class, ['record' => $holiday->getRouteKey()])
        ->assertOk()
        ->assertFormFieldExists('date')
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('type');
});

it('can filter holidays by type', function (): void {
    $national = Holiday::factory()->create(['type' => 'national']);
    $company = Holiday::factory()->create(['type' => 'company']);

    Livewire::test(ListHolidays::class)
        ->filterTable('type', 'national')
        ->assertCanSeeTableRecords([$national])
        ->assertCanNotSeeTableRecords([$company]);
});
