<?php

namespace App\Filament\Resources\DepositAccountResource\Pages;

use App\Enums\InterestPaymentMethod;
use App\Enums\RolloverType;
use App\Filament\Resources\DepositAccountResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DepositProduct;
use App\Models\SavingsAccount;
use App\Services\DepositService;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateDepositAccount extends CreateRecord
{
    protected static string $resource = DepositAccountResource::class;

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
                            ->mapWithKeys(fn (Customer $customer) => [$customer->id => "{$customer->cif_number} - {$customer->display_name}"])
                    )
                    ->searchable()
                    ->required()
                    ->live(),
                Select::make('deposit_product_id')
                    ->label('Produk Deposito')
                    ->options(DepositProduct::query()->active()->pluck('name', 'id'))
                    ->required(),
                Select::make('branch_id')
                    ->label('Cabang')
                    ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                    ->default(fn () => auth()->user()->branch_id)
                    ->required(),
                TextInput::make('principal_amount')
                    ->label('Nominal Pokok')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->minValue(0),
                TextInput::make('tenor_months')
                    ->label('Tenor (bulan)')
                    ->numeric()
                    ->required()
                    ->minValue(1),
                Select::make('interest_payment_method')
                    ->label('Metode Pembayaran Bunga')
                    ->options(InterestPaymentMethod::class)
                    ->required(),
                Select::make('rollover_type')
                    ->label('Tipe Perpanjangan')
                    ->options(RolloverType::class)
                    ->default(RolloverType::None->value)
                    ->required(),
                Select::make('savings_account_id')
                    ->label('Rekening Tabungan Pencairan')
                    ->options(function (Get $get) {
                        $customerId = $get('customer_id');
                        if (! $customerId) {
                            return [];
                        }

                        return SavingsAccount::query()
                            ->where('customer_id', $customerId)
                            ->active()
                            ->pluck('account_number', 'id');
                    })
                    ->searchable()
                    ->helperText('Rekening tabungan untuk pencairan bunga/pokok'),
                DatePicker::make('placement_date')
                    ->label('Tanggal Penempatan')
                    ->default(now())
                    ->required(),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = DepositProduct::findOrFail($data['deposit_product_id']);

        try {
            return app(DepositService::class)->place(
                product: $product,
                customerId: $data['customer_id'],
                branchId: $data['branch_id'],
                principalAmount: (float) $data['principal_amount'],
                tenorMonths: (int) $data['tenor_months'],
                interestPaymentMethod: InterestPaymentMethod::from($data['interest_payment_method']),
                rolloverType: RolloverType::from($data['rollover_type']),
                savingsAccountId: $data['savings_account_id'] ?? null,
                performer: auth()->user(),
                placementDate: $data['placement_date'] ? Carbon::parse($data['placement_date']) : null,
            );
        } catch (\InvalidArgumentException $e) {
            Notification::make()
                ->title('Gagal menempatkan deposito')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
