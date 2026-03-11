# Neraca (Balance Sheet)

Neraca adalah laporan keuangan yang menampilkan posisi keuangan BPR pada tanggal tertentu, meliputi Aset, Liabilitas, dan Ekuitas. Laporan ini mematuhi persamaan dasar akuntansi: **Aset = Liabilitas + Ekuitas**.

## Hak Akses

| Role | Permission | Akses |
|------|-----------|-------|
| Accounting | `report.view` | Lihat dan generate laporan |
| Manager | `report.view` | Lihat dan generate laporan |
| Auditor | `report.view` | Lihat laporan |

## Mengakses Neraca

Halaman Neraca merupakan custom page pada Filament yang dapat diakses melalui:

- **URL:** `/admin/balance-sheet`
- **Menu:** Akuntansi → Neraca

![Neraca](../assets/screenshots/akuntansi/balance-sheet.png)

## Cara Penggunaan

### Pemilihan Tanggal

Pilih tanggal laporan menggunakan **date picker** yang tersedia. Neraca akan menampilkan posisi keuangan per tanggal yang dipilih.

### Section Laporan

Laporan Neraca dibagi menjadi tiga section dengan kode warna untuk memudahkan pembacaan:

#### Aset (Biru)

Menampilkan seluruh akun aset yang dimiliki BPR.

| Kolom | Keterangan |
|-------|------------|
| Kode | Kode akun aset |
| Nama Akun | Nama lengkap akun |
| Saldo | Saldo akun per tanggal laporan dalam Rupiah |

Contoh akun aset:

- Kas dan Setara Kas
- Penempatan pada Bank Lain
- Kredit yang Diberikan
- Aset Tetap
- Aset Lain-lain

#### Liabilitas (Merah)

Menampilkan seluruh akun kewajiban BPR.

| Kolom | Keterangan |
|-------|------------|
| Kode | Kode akun liabilitas |
| Nama Akun | Nama lengkap akun |
| Saldo | Saldo akun per tanggal laporan dalam Rupiah |

Contoh akun liabilitas:

- Tabungan
- Deposito Berjangka
- Pinjaman Diterima
- Kewajiban Lain-lain

#### Ekuitas (Hijau)

Menampilkan seluruh akun modal BPR.

| Kolom | Keterangan |
|-------|------------|
| Kode | Kode akun ekuitas |
| Nama Akun | Nama lengkap akun |
| Saldo | Saldo akun per tanggal laporan dalam Rupiah |

Contoh akun ekuitas:

- Modal Disetor
- Cadangan Umum
- Laba Ditahan
- Laba Tahun Berjalan

### Subtotal dan Grand Total

- Setiap kelompok akun (Aset, Liabilitas, Ekuitas) menampilkan **subtotal**
- Di bagian bawah laporan ditampilkan **grand total** untuk masing-masing sisi

!!! warning "Persamaan Akuntansi"
    Neraca yang benar harus memenuhi persamaan: **Aset = Liabilitas + Ekuitas**. Jika tidak seimbang, periksa kembali jurnal dan neraca saldo untuk menemukan selisih.

!!! tip "Tips"
    Jalankan proses End of Day (EOD) terlebih dahulu sebelum generate Neraca untuk memastikan seluruh transaksi hari tersebut telah diproses dan saldo akun telah diperbarui.
