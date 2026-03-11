<?php

namespace Database\Seeders;

use App\Enums\ApprovalStatus;
use App\Enums\CustomerStatus;
use App\Enums\CustomerType;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\RiskRating;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::first();
        $user = User::first();

        $individuals = [
            [
                'cif_number' => '00126000001',
                'customer_type' => CustomerType::Individual,
                'risk_rating' => RiskRating::Low,
                'individual' => [
                    'nik' => '3201010101900001',
                    'full_name' => 'Budi Santoso',
                    'birth_place' => 'Jakarta',
                    'birth_date' => '1990-01-01',
                    'gender' => Gender::Male,
                    'marital_status' => MaritalStatus::Married,
                    'mother_maiden_name' => 'Siti Aminah',
                    'religion' => 'Islam',
                    'education' => 'S1',
                    'nationality' => 'IDN',
                    'npwp' => '09.123.456.7-012.000',
                    'phone_mobile' => '081234567890',
                    'email' => 'budi.santoso@example.com',
                    'occupation' => 'Pegawai Swasta',
                    'employer_name' => 'PT Maju Jaya',
                    'monthly_income' => 15_000_000,
                    'source_of_fund' => 'Gaji',
                    'transaction_purpose' => 'Tabungan',
                ],
            ],
            [
                'cif_number' => '00126000002',
                'customer_type' => CustomerType::Individual,
                'risk_rating' => RiskRating::Low,
                'individual' => [
                    'nik' => '3201020202850002',
                    'full_name' => 'Dewi Lestari',
                    'birth_place' => 'Bandung',
                    'birth_date' => '1985-02-02',
                    'gender' => Gender::Female,
                    'marital_status' => MaritalStatus::Single,
                    'mother_maiden_name' => 'Ratna Dewi',
                    'religion' => 'Islam',
                    'education' => 'S2',
                    'nationality' => 'IDN',
                    'npwp' => '09.234.567.8-012.000',
                    'phone_mobile' => '081234567891',
                    'email' => 'dewi.lestari@example.com',
                    'occupation' => 'Wiraswasta',
                    'employer_name' => 'CV Dewi Collection',
                    'monthly_income' => 25_000_000,
                    'source_of_fund' => 'Usaha',
                    'transaction_purpose' => 'Tabungan & Pinjaman',
                ],
            ],
            [
                'cif_number' => '00126000003',
                'customer_type' => CustomerType::Individual,
                'risk_rating' => RiskRating::Medium,
                'individual' => [
                    'nik' => '3201030303800003',
                    'full_name' => 'Ahmad Hidayat',
                    'birth_place' => 'Surabaya',
                    'birth_date' => '1980-03-03',
                    'gender' => Gender::Male,
                    'marital_status' => MaritalStatus::Married,
                    'mother_maiden_name' => 'Nurhayati',
                    'religion' => 'Islam',
                    'education' => 'S1',
                    'nationality' => 'IDN',
                    'npwp' => '09.345.678.9-012.000',
                    'phone_mobile' => '081234567892',
                    'email' => 'ahmad.hidayat@example.com',
                    'occupation' => 'Direktur',
                    'employer_name' => 'PT Hidayat Group',
                    'monthly_income' => 150_000_000,
                    'source_of_fund' => 'Usaha',
                    'transaction_purpose' => 'Deposito',
                ],
            ],
        ];

        foreach ($individuals as $data) {
            $customer = Customer::create([
                'cif_number' => $data['cif_number'],
                'customer_type' => $data['customer_type'],
                'status' => CustomerStatus::Active,
                'risk_rating' => $data['risk_rating'],
                'branch_id' => $branch->id,
                'approval_status' => ApprovalStatus::Approved,
                'created_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $customer->individualDetail()->create($data['individual']);

            $customer->addresses()->create([
                'type' => 'domicile',
                'address' => fake()->streetAddress(),
                'rt_rw' => '001/002',
                'kelurahan' => 'Menteng',
                'kecamatan' => 'Menteng',
                'city' => 'Jakarta Pusat',
                'province' => 'DKI Jakarta',
                'postal_code' => '10310',
                'is_primary' => true,
            ]);

            $customer->phones()->create([
                'type' => 'mobile',
                'number' => $data['individual']['phone_mobile'],
                'is_primary' => true,
            ]);

            $customer->documents()->create([
                'type' => 'ktp',
                'document_number' => $data['individual']['nik'],
                'is_verified' => true,
            ]);
        }

        $corporateData = [
            'cif_number' => '00126000004',
            'customer_type' => CustomerType::Corporate,
            'risk_rating' => RiskRating::Medium,
        ];

        $corporate = Customer::create([
            'cif_number' => $corporateData['cif_number'],
            'customer_type' => $corporateData['customer_type'],
            'status' => CustomerStatus::Active,
            'risk_rating' => $corporateData['risk_rating'],
            'branch_id' => $branch->id,
            'approval_status' => ApprovalStatus::Approved,
            'created_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $corporate->corporateDetail()->create([
            'company_name' => 'PT Sejahtera Mandiri',
            'legal_type' => 'PT',
            'nib' => '1234567890123',
            'npwp_company' => '01.234.567.8-012.000',
            'deed_number' => 'AHU-12345.AH.01.01.2020',
            'deed_date' => '2020-06-15',
            'sk_kemenkumham' => 'AHU-12345.AH.01.01.Tahun 2020',
            'business_sector' => 'Perdagangan Umum',
            'address_company' => 'Jl. Sudirman No. 100, Jakarta Selatan',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'phone_office' => '021-5551234',
            'email' => 'info@sejahteramandiri.co.id',
            'annual_revenue' => 5_000_000_000,
            'total_employees' => 50,
            'contact_person_name' => 'Rudi Hartono',
            'contact_person_phone' => '081234567899',
            'contact_person_position' => 'Direktur Utama',
        ]);

        $corporate->addresses()->create([
            'type' => 'office',
            'address' => 'Jl. Sudirman No. 100',
            'kelurahan' => 'Senayan',
            'kecamatan' => 'Kebayoran Baru',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'postal_code' => '12190',
            'is_primary' => true,
        ]);

        $corporate->phones()->create([
            'type' => 'office',
            'number' => '021-5551234',
            'is_primary' => true,
        ]);

        $corporate->documents()->create([
            'type' => 'akta',
            'document_number' => 'AHU-12345.AH.01.01.2020',
            'is_verified' => true,
        ]);

        $pendingCustomer = Customer::create([
            'cif_number' => '00126000005',
            'customer_type' => CustomerType::Individual,
            'status' => CustomerStatus::PendingApproval,
            'risk_rating' => RiskRating::Low,
            'branch_id' => $branch->id,
            'approval_status' => ApprovalStatus::Pending,
            'created_by' => $user->id,
        ]);

        $pendingCustomer->individualDetail()->create([
            'nik' => '3201050505950005',
            'full_name' => 'Siti Rahayu',
            'birth_place' => 'Semarang',
            'birth_date' => '1995-05-05',
            'gender' => Gender::Female,
            'marital_status' => MaritalStatus::Single,
            'mother_maiden_name' => 'Kartini',
            'religion' => 'Islam',
            'education' => 'D3',
            'nationality' => 'IDN',
            'phone_mobile' => '081234567895',
            'email' => 'siti.rahayu@example.com',
            'occupation' => 'Karyawan',
            'monthly_income' => 8_000_000,
            'source_of_fund' => 'Gaji',
            'transaction_purpose' => 'Tabungan',
        ]);
    }
}
