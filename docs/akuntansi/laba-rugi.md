# Laba Rugi (Income Statement)

Laporan Laba Rugi menampilkan kinerja keuangan BPR selama periode tertentu, meliputi pendapatan dan beban operasional. Selisih antara total pendapatan dan total beban menghasilkan Laba atau Rugi Bersih.

## Hak Akses

| Role | Permission | Akses |
|------|-----------|-------|
| Accounting | `report.view` | Lihat dan generate laporan |
| Manager | `report.view` | Lihat dan generate laporan |
| Auditor | `report.view` | Lihat laporan |

## Mengakses Laporan Laba Rugi

Halaman Laba Rugi merupakan custom page pada Filament yang dapat diakses melalui:

- **URL:** `/admin/income-statement`
- **Menu:** Akuntansi → Laba Rugi

![Laba Rugi](../assets/screenshots/akuntansi/income-statement.png)

## Cara Penggunaan

### Pemilihan Periode

Tentukan rentang waktu laporan dengan mengisi:

1. **Tanggal Mulai** — Tanggal awal periode laporan
2. **Tanggal Akhir** — Tanggal akhir periode laporan

Setelah memilih periode, klik tombol **Generate** untuk menampilkan laporan.

### Section Laporan

Laporan Laba Rugi dibagi menjadi dua section utama:

#### Pendapatan (Income)

Menampilkan seluruh akun pendapatan BPR selama periode yang dipilih.

| Kolom | Keterangan |
|-------|------------|
| Kode Akun | Kode akun pendapatan |
| Nama Akun | Nama lengkap akun pendapatan |
| Saldo | Jumlah pendapatan selama periode dalam Rupiah |

Contoh akun pendapatan:

- Pendapatan Bunga Kredit
- Pendapatan Bunga Penempatan
- Pendapatan Provisi dan Komisi
- Pendapatan Operasional Lainnya

Di akhir section ditampilkan **Subtotal Pendapatan**.

#### Beban (Expenses)

Menampilkan seluruh akun beban BPR selama periode yang dipilih.

| Kolom | Keterangan |
|-------|------------|
| Kode Akun | Kode akun beban |
| Nama Akun | Nama lengkap akun beban |
| Saldo | Jumlah beban selama periode dalam Rupiah |

Contoh akun beban:

- Beban Bunga Tabungan
- Beban Bunga Deposito
- Beban Tenaga Kerja
- Beban Umum dan Administrasi
- Beban CKPN (Cadangan Kerugian Penurunan Nilai)

Di akhir section ditampilkan **Subtotal Beban**.

### Laba/Rugi Bersih

Di bagian bawah laporan ditampilkan perhitungan:

```
Laba/Rugi Bersih = Total Pendapatan - Total Beban
```

!!! info "Interpretasi"
    - Jika hasilnya **positif**, BPR membukukan **Laba Bersih** selama periode tersebut.
    - Jika hasilnya **negatif**, BPR mengalami **Rugi Bersih** selama periode tersebut.

!!! tip "Tips"
    Untuk mendapatkan gambaran kinerja yang akurat, pastikan seluruh jurnal pada periode terkait telah berstatus **Posted** dan proses EOD telah dijalankan untuk setiap hari dalam periode laporan.
