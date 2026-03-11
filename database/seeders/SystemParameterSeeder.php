<?php

namespace Database\Seeders;

use App\Models\SystemParameter;
use Illuminate\Database\Seeder;

class SystemParameterSeeder extends Seeder
{
    public function run(): void
    {
        $parameters = [
            ['group' => 'bank', 'key' => 'bank_name', 'value' => 'BPR Nusantara', 'type' => 'string', 'description' => 'Nama Bank', 'is_editable' => true],
            ['group' => 'bank', 'key' => 'bank_code', 'value' => '600001', 'type' => 'string', 'description' => 'Kode Bank (Sandi BI)', 'is_editable' => false],
            ['group' => 'bank', 'key' => 'npwp', 'value' => '00.000.000.0-000.000', 'type' => 'string', 'description' => 'NPWP Bank', 'is_editable' => true],

            ['group' => 'deposit', 'key' => 'tax_rate', 'value' => '20', 'type' => 'decimal', 'description' => 'Tarif pajak bunga deposito (%)', 'is_editable' => true],
            ['group' => 'deposit', 'key' => 'early_withdrawal_penalty_rate', 'value' => '0.5', 'type' => 'decimal', 'description' => 'Penalti pencairan dini (%)', 'is_editable' => true],

            ['group' => 'savings', 'key' => 'tax_rate', 'value' => '20', 'type' => 'decimal', 'description' => 'Tarif pajak bunga tabungan (%)', 'is_editable' => true],
            ['group' => 'savings', 'key' => 'dormant_period_days', 'value' => '365', 'type' => 'integer', 'description' => 'Periode dormant (hari)', 'is_editable' => true],
            ['group' => 'savings', 'key' => 'min_balance', 'value' => '10000', 'type' => 'decimal', 'description' => 'Saldo minimum tabungan', 'is_editable' => true],

            ['group' => 'loan', 'key' => 'max_dti_ratio', 'value' => '30', 'type' => 'decimal', 'description' => 'Rasio DTI maksimum (%)', 'is_editable' => true],
            ['group' => 'loan', 'key' => 'late_charge_rate', 'value' => '0.05', 'type' => 'decimal', 'description' => 'Tarif denda keterlambatan (% per hari)', 'is_editable' => true],

            ['group' => 'teller', 'key' => 'max_transaction_amount', 'value' => '100000000', 'type' => 'decimal', 'description' => 'Limit transaksi tanpa otorisasi', 'is_editable' => true],

            ['group' => 'system', 'key' => 'current_date', 'value' => now()->toDateString(), 'type' => 'date', 'description' => 'Tanggal sistem berjalan', 'is_editable' => false],
            ['group' => 'system', 'key' => 'eod_status', 'value' => 'idle', 'type' => 'string', 'description' => 'Status EOD', 'is_editable' => false],
        ];

        foreach ($parameters as $param) {
            SystemParameter::create($param);
        }
    }
}
