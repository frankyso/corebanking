<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\RiskRating;
use App\Filament\Resources\CustomerResource;
use App\Models\Branch;
use App\Services\CustomerService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;

class CreateCustomer extends CreateRecord
{
    use HasWizard;

    protected static string $resource = CustomerResource::class;

    public function getSteps(): array
    {
        return [
            Step::make('Informasi Dasar')
                ->icon('heroicon-o-user')
                ->schema([
                    Select::make('customer_type')
                        ->label('Tipe Nasabah')
                        ->options(CustomerType::class)
                        ->required()
                        ->live(),
                    Select::make('branch_id')
                        ->label('Cabang')
                        ->options(Branch::query()->where('is_active', true)->pluck('name', 'id'))
                        ->required()
                        ->searchable(),
                    Select::make('risk_rating')
                        ->label('Tingkat Risiko')
                        ->options(RiskRating::class)
                        ->default(RiskRating::Low->value),
                ]),

            Step::make('Data Identitas')
                ->icon('heroicon-o-identification')
                ->schema([
                    ...static::individualFields(),
                    ...static::corporateFields(),
                ]),

            Step::make('Alamat')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    TextInput::make('address.type')
                        ->label('Tipe Alamat')
                        ->default('domicile')
                        ->required(),
                    Textarea::make('address.address')
                        ->label('Alamat')
                        ->required()
                        ->rows(3),
                    TextInput::make('address.rt_rw')
                        ->label('RT/RW'),
                    TextInput::make('address.kelurahan')
                        ->label('Kelurahan'),
                    TextInput::make('address.kecamatan')
                        ->label('Kecamatan'),
                    TextInput::make('address.city')
                        ->label('Kota')
                        ->required(),
                    TextInput::make('address.province')
                        ->label('Provinsi')
                        ->required(),
                    TextInput::make('address.postal_code')
                        ->label('Kode Pos')
                        ->maxLength(5),
                ]),

            Step::make('Kontak')
                ->icon('heroicon-o-phone')
                ->schema([
                    TextInput::make('phone.type')
                        ->label('Tipe Telepon')
                        ->default('mobile')
                        ->required(),
                    TextInput::make('phone.number')
                        ->label('Nomor Telepon')
                        ->required()
                        ->tel(),
                    Toggle::make('phone.is_primary')
                        ->label('Nomor Utama')
                        ->default(true),
                ]),

            Step::make('Dokumen')
                ->icon('heroicon-o-document-text')
                ->schema([
                    TextInput::make('document.type')
                        ->label('Tipe Dokumen')
                        ->default('ktp')
                        ->required(),
                    TextInput::make('document.document_number')
                        ->label('Nomor Dokumen')
                        ->required(),
                    DatePicker::make('document.expiry_date')
                        ->label('Tanggal Kedaluwarsa'),
                ]),
        ];
    }

    protected static function individualFields(): array
    {
        return [
            TextInput::make('individual.nik')
                ->label('NIK')
                ->maxLength(16)
                ->required()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.full_name')
                ->label('Nama Lengkap')
                ->maxLength(150)
                ->required()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.birth_place')
                ->label('Tempat Lahir')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            DatePicker::make('individual.birth_date')
                ->label('Tanggal Lahir')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            Select::make('individual.gender')
                ->label('Jenis Kelamin')
                ->options(Gender::class)
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            Select::make('individual.marital_status')
                ->label('Status Perkawinan')
                ->options(MaritalStatus::class)
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.mother_maiden_name')
                ->label('Nama Ibu Kandung')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.religion')
                ->label('Agama')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.education')
                ->label('Pendidikan')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.nationality')
                ->label('Kewarganegaraan')
                ->default('IDN')
                ->maxLength(3)
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.npwp')
                ->label('NPWP')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.phone_mobile')
                ->label('No. HP')
                ->tel()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.email')
                ->label('Email')
                ->email()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.occupation')
                ->label('Pekerjaan')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.employer_name')
                ->label('Nama Perusahaan')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.monthly_income')
                ->label('Penghasilan Bulanan')
                ->numeric()
                ->prefix('Rp')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.source_of_fund')
                ->label('Sumber Dana')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
            TextInput::make('individual.transaction_purpose')
                ->label('Tujuan Transaksi')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Individual->value),
        ];
    }

    protected static function corporateFields(): array
    {
        return [
            TextInput::make('corporate.company_name')
                ->label('Nama Perusahaan')
                ->maxLength(200)
                ->required()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.legal_type')
                ->label('Bentuk Badan Hukum')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.nib')
                ->label('NIB')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.npwp_company')
                ->label('NPWP Perusahaan')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.deed_number')
                ->label('Nomor Akta')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            DatePicker::make('corporate.deed_date')
                ->label('Tanggal Akta')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.sk_kemenkumham')
                ->label('SK Kemenkumham')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.business_sector')
                ->label('Sektor Usaha')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            Textarea::make('corporate.address_company')
                ->label('Alamat Perusahaan')
                ->rows(3)
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.city')
                ->label('Kota')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.province')
                ->label('Provinsi')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.postal_code')
                ->label('Kode Pos')
                ->maxLength(5)
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.phone_office')
                ->label('Telepon Kantor')
                ->tel()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.email')
                ->label('Email')
                ->email()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.annual_revenue')
                ->label('Pendapatan Tahunan')
                ->numeric()
                ->prefix('Rp')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.total_employees')
                ->label('Jumlah Karyawan')
                ->numeric()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.contact_person_name')
                ->label('Nama Contact Person')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.contact_person_phone')
                ->label('Telepon Contact Person')
                ->tel()
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
            TextInput::make('corporate.contact_person_position')
                ->label('Jabatan Contact Person')
                ->visible(fn (Get $get): bool => $get('customer_type') === CustomerType::Corporate->value),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['approval_status'] = ApprovalStatus::Pending->value;
        $data['status'] = CustomerStatus::PendingApproval->value;

        $branchCode = auth()->user()->branch?->code ?? '001';
        $data['cif_number'] = app(CustomerService::class)
            ->getSequenceService()
            ->generateCifNumber($branchCode);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;
        $data = $this->form->getState();

        if ($record->customer_type === CustomerType::Individual && ! empty($data['individual'])) {
            $record->individualDetail()->create(
                collect($data['individual'])->filter()->all()
            );
        }

        if ($record->customer_type === CustomerType::Corporate && ! empty($data['corporate'])) {
            $record->corporateDetail()->create(
                collect($data['corporate'])->filter()->all()
            );
        }

        if (! empty($data['address']['address'])) {
            $record->addresses()->create(
                array_merge(
                    collect($data['address'])->filter()->all(),
                    ['is_primary' => true]
                )
            );
        }

        if (! empty($data['phone']['number'])) {
            $record->phones()->create(
                collect($data['phone'])->filter()->all()
            );
        }

        if (! empty($data['document']['document_number'])) {
            $record->documents()->create(
                collect($data['document'])->filter()->all()
            );
        }
    }
}
