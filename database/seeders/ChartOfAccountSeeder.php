<?php

namespace Database\Seeders;

use App\Enums\AccountGroup;
use App\Enums\NormalBalance;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;

class ChartOfAccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            // ASET
            ['account_code' => '1.00.00.000', 'account_name' => 'ASET', 'account_group' => AccountGroup::Asset, 'level' => 1, 'is_header' => true, 'normal_balance' => NormalBalance::Debit],
            ['account_code' => '1.01.00.000', 'account_name' => 'Kas dan Setara Kas', 'account_group' => AccountGroup::Asset, 'level' => 2, 'is_header' => true, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.00.00.000'],
            ['account_code' => '1.01.01.000', 'account_name' => 'Kas Besar (Vault)', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.01.00.000'],
            ['account_code' => '1.01.02.000', 'account_name' => 'Kas Teller', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.01.00.000'],
            ['account_code' => '1.01.03.000', 'account_name' => 'Kas ATM', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.01.00.000'],
            ['account_code' => '1.02.00.000', 'account_name' => 'Penempatan pada Bank Lain', 'account_group' => AccountGroup::Asset, 'level' => 2, 'is_header' => true, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.00.00.000'],
            ['account_code' => '1.02.01.000', 'account_name' => 'Giro pada Bank Lain', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.02.00.000'],
            ['account_code' => '1.03.00.000', 'account_name' => 'Kredit yang Diberikan', 'account_group' => AccountGroup::Asset, 'level' => 2, 'is_header' => true, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.00.00.000'],
            ['account_code' => '1.03.01.000', 'account_name' => 'Kredit Modal Kerja', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.03.00.000'],
            ['account_code' => '1.03.02.000', 'account_name' => 'Kredit Investasi', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.03.00.000'],
            ['account_code' => '1.03.03.000', 'account_name' => 'Kredit Konsumsi', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.03.00.000'],
            ['account_code' => '1.03.09.000', 'account_name' => 'CKPN Kredit', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '1.03.00.000'],
            ['account_code' => '1.04.00.000', 'account_name' => 'Pendapatan Bunga yang akan Diterima', 'account_group' => AccountGroup::Asset, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.00.00.000'],
            ['account_code' => '1.05.00.000', 'account_name' => 'Aset Tetap', 'account_group' => AccountGroup::Asset, 'level' => 2, 'is_header' => true, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.00.00.000'],
            ['account_code' => '1.05.01.000', 'account_name' => 'Tanah dan Bangunan', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.05.00.000'],
            ['account_code' => '1.05.02.000', 'account_name' => 'Inventaris', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '1.05.00.000'],
            ['account_code' => '1.05.09.000', 'account_name' => 'Akumulasi Penyusutan', 'account_group' => AccountGroup::Asset, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '1.05.00.000'],

            // KEWAJIBAN
            ['account_code' => '2.00.00.000', 'account_name' => 'KEWAJIBAN', 'account_group' => AccountGroup::Liability, 'level' => 1, 'is_header' => true, 'normal_balance' => NormalBalance::Credit],
            ['account_code' => '2.01.00.000', 'account_name' => 'Tabungan', 'account_group' => AccountGroup::Liability, 'level' => 2, 'is_header' => true, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.00.00.000'],
            ['account_code' => '2.01.01.000', 'account_name' => 'Tabungan Umum', 'account_group' => AccountGroup::Liability, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.01.00.000'],
            ['account_code' => '2.01.02.000', 'account_name' => 'Tabungan Pelajar', 'account_group' => AccountGroup::Liability, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.01.00.000'],
            ['account_code' => '2.02.00.000', 'account_name' => 'Deposito Berjangka', 'account_group' => AccountGroup::Liability, 'level' => 2, 'is_header' => true, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.00.00.000'],
            ['account_code' => '2.02.01.000', 'account_name' => 'Deposito 1 Bulan', 'account_group' => AccountGroup::Liability, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.02.00.000'],
            ['account_code' => '2.02.02.000', 'account_name' => 'Deposito 3 Bulan', 'account_group' => AccountGroup::Liability, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.02.00.000'],
            ['account_code' => '2.02.03.000', 'account_name' => 'Deposito 6 Bulan', 'account_group' => AccountGroup::Liability, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.02.00.000'],
            ['account_code' => '2.02.04.000', 'account_name' => 'Deposito 12 Bulan', 'account_group' => AccountGroup::Liability, 'level' => 3, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.02.00.000'],
            ['account_code' => '2.03.00.000', 'account_name' => 'Bunga yang Masih Harus Dibayar', 'account_group' => AccountGroup::Liability, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.00.00.000'],
            ['account_code' => '2.04.00.000', 'account_name' => 'Hutang Pajak', 'account_group' => AccountGroup::Liability, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.00.00.000'],
            ['account_code' => '2.05.00.000', 'account_name' => 'Kewajiban Lain-lain', 'account_group' => AccountGroup::Liability, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '2.00.00.000'],

            // EKUITAS
            ['account_code' => '3.00.00.000', 'account_name' => 'EKUITAS', 'account_group' => AccountGroup::Equity, 'level' => 1, 'is_header' => true, 'normal_balance' => NormalBalance::Credit],
            ['account_code' => '3.01.00.000', 'account_name' => 'Modal Disetor', 'account_group' => AccountGroup::Equity, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '3.00.00.000'],
            ['account_code' => '3.02.00.000', 'account_name' => 'Cadangan Umum', 'account_group' => AccountGroup::Equity, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '3.00.00.000'],
            ['account_code' => '3.03.00.000', 'account_name' => 'Laba Ditahan', 'account_group' => AccountGroup::Equity, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '3.00.00.000'],
            ['account_code' => '3.04.00.000', 'account_name' => 'Laba Tahun Berjalan', 'account_group' => AccountGroup::Equity, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '3.00.00.000'],

            // PENDAPATAN
            ['account_code' => '4.00.00.000', 'account_name' => 'PENDAPATAN', 'account_group' => AccountGroup::Revenue, 'level' => 1, 'is_header' => true, 'normal_balance' => NormalBalance::Credit],
            ['account_code' => '4.01.00.000', 'account_name' => 'Pendapatan Bunga Kredit', 'account_group' => AccountGroup::Revenue, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '4.00.00.000'],
            ['account_code' => '4.02.00.000', 'account_name' => 'Pendapatan Provisi & Komisi', 'account_group' => AccountGroup::Revenue, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '4.00.00.000'],
            ['account_code' => '4.03.00.000', 'account_name' => 'Pendapatan Administrasi', 'account_group' => AccountGroup::Revenue, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '4.00.00.000'],
            ['account_code' => '4.04.00.000', 'account_name' => 'Pendapatan Denda', 'account_group' => AccountGroup::Revenue, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '4.00.00.000'],
            ['account_code' => '4.05.00.000', 'account_name' => 'Pendapatan Lainnya', 'account_group' => AccountGroup::Revenue, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Credit, 'parent_code' => '4.00.00.000'],

            // BEBAN
            ['account_code' => '5.00.00.000', 'account_name' => 'BEBAN', 'account_group' => AccountGroup::Expense, 'level' => 1, 'is_header' => true, 'normal_balance' => NormalBalance::Debit],
            ['account_code' => '5.01.00.000', 'account_name' => 'Beban Bunga Tabungan', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
            ['account_code' => '5.02.00.000', 'account_name' => 'Beban Bunga Deposito', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
            ['account_code' => '5.03.00.000', 'account_name' => 'Beban CKPN', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
            ['account_code' => '5.04.00.000', 'account_name' => 'Beban Tenaga Kerja', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
            ['account_code' => '5.05.00.000', 'account_name' => 'Beban Umum dan Administrasi', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
            ['account_code' => '5.06.00.000', 'account_name' => 'Beban Penyusutan', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
            ['account_code' => '5.07.00.000', 'account_name' => 'Beban Lainnya', 'account_group' => AccountGroup::Expense, 'level' => 2, 'is_header' => false, 'normal_balance' => NormalBalance::Debit, 'parent_code' => '5.00.00.000'],
        ];

        $codeToId = [];

        foreach ($accounts as $account) {
            $parentCode = $account['parent_code'] ?? null;
            unset($account['parent_code']);

            if ($parentCode && isset($codeToId[$parentCode])) {
                $account['parent_id'] = $codeToId[$parentCode];
            }

            $coa = ChartOfAccount::create($account);
            $codeToId[$coa->account_code] = $coa->id;
        }
    }
}
