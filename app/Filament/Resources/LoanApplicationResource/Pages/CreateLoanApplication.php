<?php

namespace App\Filament\Resources\LoanApplicationResource\Pages;

use App\Actions\Loan\CreateLoanApplication as CreateLoanApplicationAction;
use App\DTOs\Loan\CreateLoanApplicationData;
use App\Exceptions\DomainException;
use App\Filament\Resources\LoanApplicationResource;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoanProduct;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class CreateLoanApplication extends CreateRecord
{
    protected static string $resource = LoanApplicationResource::class;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Nasabah')
                    ->schema([
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
                        Select::make('branch_id')
                            ->label('Cabang')
                            ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                            ->default(fn () => auth()->user()->branch_id)
                            ->required(),
                    ])->columns(2),

                Section::make('Data Kredit')
                    ->schema([
                        Select::make('loan_product_id')
                            ->label('Produk Kredit')
                            ->options(LoanProduct::query()->active()->pluck('name', 'id'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if ($state) {
                                    $product = LoanProduct::find($state);
                                    if ($product) {
                                        $set('interest_rate_display', (float) $product->interest_rate.'%');
                                    }
                                }
                            }),
                        TextInput::make('interest_rate_display')
                            ->label('Suku Bunga')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Pilih produk terlebih dahulu'),
                        TextInput::make('requested_amount')
                            ->label('Jumlah Pinjaman')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(0),
                        TextInput::make('requested_tenor_months')
                            ->label('Tenor (bulan)')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->helperText(function (Get $get) {
                                $productId = $get('loan_product_id');
                                if (! $productId) {
                                    return null;
                                }
                                $product = LoanProduct::find($productId);

                                return $product ? "Min: {$product->min_tenor_months} bln, Maks: {$product->max_tenor_months} bln" : null;
                            }),
                    ])->columns(2),

                Section::make('Keterangan')
                    ->schema([
                        Textarea::make('purpose')
                            ->label('Tujuan Penggunaan')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('loan_officer_id')
                            ->label('Account Officer')
                            ->options(User::query()->where('is_active', true)->pluck('name', 'id'))
                            ->searchable(),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $product = LoanProduct::findOrFail($data['loan_product_id']);

        try {
            $record = app(CreateLoanApplicationAction::class)->execute(new CreateLoanApplicationData(
                product: $product,
                customerId: $data['customer_id'],
                branchId: $data['branch_id'],
                requestedAmount: (float) $data['requested_amount'],
                requestedTenor: (int) $data['requested_tenor_months'],
                purpose: $data['purpose'],
                creator: auth()->user(),
                loanOfficerId: $data['loan_officer_id'] ?? null,
            ));
        } catch (DomainException $e) {
            Notification::make()
                ->title('Gagal membuat permohonan')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw new \RuntimeException('Unreachable');
        }

        return $record;
    }
}
