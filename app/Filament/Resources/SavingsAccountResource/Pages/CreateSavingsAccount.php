<?php

namespace App\Filament\Resources\SavingsAccountResource\Pages;

use App\Filament\Resources\SavingsAccountResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\SavingsProduct;
use App\Services\SavingsService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateSavingsAccount extends CreateRecord
{
    protected static string $resource = SavingsAccountResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Nasabah')
                    ->options(
                        Customer::query()
                            ->active()
                            ->with(['individualDetail', 'corporateDetail'])
                            ->get()
                            ->mapWithKeys(fn (Customer $customer): array => [$customer->id => "{$customer->cif_number} - {$customer->display_name}"])
                    )
                    ->searchable()
                    ->required(),
                Select::make('savings_product_id')
                    ->label('Produk Tabungan')
                    ->options(SavingsProduct::query()->active()->pluck('name', 'id'))
                    ->required(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => auth()->user()->branch_id)
                    ->required(),
                TextInput::make('initial_deposit')
                    ->label('Setoran Awal')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = SavingsProduct::findOrFail($data['savings_product_id']);

        try {
            $record = app(SavingsService::class)->open(
                product: $product,
                customerId: $data['customer_id'],
                branchId: $data['branch_id'],
                initialDeposit: (float) $data['initial_deposit'],
                performer: auth()->user(),
            );
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Gagal membuka rekening')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw new \RuntimeException('Unreachable');
        }

        return $record;
    }
}
