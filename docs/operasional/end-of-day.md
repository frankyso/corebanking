# End of Day (EOD)

Proses End of Day (EOD) adalah proses akhir hari yang wajib dijalankan setiap hari kerja untuk memproses seluruh perhitungan otomatis seperti bunga, kolektibilitas, CKPN, dan pembaruan saldo buku besar.

## Hak Akses

| Role | Permission | Akses |
|------|-----------|-------|
| Manager | `eod.execute` | Menjalankan proses EOD |
| Admin | `eod.execute` | Menjalankan proses EOD |

## Mengakses Halaman EOD

Halaman End of Day merupakan custom page pada Filament yang dapat diakses melalui:

- **URL:** `/admin/eod-process`
- **Menu:** Operasional → End of Day

![End of Day](../assets/screenshots/operasional/eod-process.png)

## Cara Menjalankan EOD

### 1. Pilih Tanggal Proses

Pilih tanggal yang akan diproses menggunakan **date picker**. Umumnya tanggal proses adalah tanggal hari ini.

### 2. Jalankan Proses EOD

Klik tombol **Run EOD**. Sistem akan menampilkan dialog konfirmasi sebelum memulai proses.

!!! warning "Peringatan"
    - EOD harus dijalankan **satu kali per hari**
    - Pastikan **seluruh sesi teller telah ditutup** sebelum menjalankan EOD
    - Proses EOD tidak dapat dibatalkan setelah dimulai

### 3. Pantau Progres

Selama proses berlangsung, sistem menampilkan informasi berikut:

| Informasi | Keterangan |
|-----------|------------|
| Progress Bar | Persentase penyelesaian proses keseluruhan |
| Step Name | Nama langkah yang sedang dijalankan |
| Status | Status langkah saat ini |
| Records Processed | Jumlah record yang telah diproses |
| Duration | Durasi waktu eksekusi |
| Errors | Jumlah error yang terjadi (jika ada) |

## Tahapan Proses EOD

Proses EOD menjalankan langkah-langkah berikut secara berurutan:

| No | Langkah | Keterangan |
|----|---------|------------|
| 1 | Interest Accrual (Tabungan) | Menghitung dan mencatat bunga harian untuk seluruh rekening tabungan aktif |
| 2 | Interest Accrual (Deposito) | Menghitung dan mencatat bunga harian untuk seluruh deposito berjangka aktif |
| 3 | Loan Collectibility Update | Memperbarui status kolektibilitas seluruh kredit berdasarkan tunggakan |
| 4 | CKPN Calculation | Menghitung Cadangan Kerugian Penurunan Nilai untuk portofolio kredit |
| 5 | GL Balance Update | Memperbarui saldo seluruh akun pada buku besar (General Ledger) |
| 6 | Daily Balance Snapshot | Menyimpan snapshot saldo harian untuk keperluan pelaporan |

## Status EOD

| Status | Keterangan |
|--------|------------|
| **Pending** | Proses EOD belum dijalankan untuk tanggal tersebut |
| **Running** | Proses EOD sedang berjalan |
| **Completed** | Proses EOD berhasil diselesaikan |
| **Failed** | Proses EOD gagal, perlu ditangani oleh administrator |

## Riwayat Proses

Di bagian bawah halaman ditampilkan riwayat **10 proses EOD terakhir**, meliputi tanggal proses, waktu mulai, waktu selesai, status, dan jumlah error.

!!! danger "Jika EOD Gagal"
    Jika proses EOD gagal (status **Failed**):

    1. Periksa pesan error pada detail proses
    2. Perbaiki penyebab kegagalan
    3. Hubungi administrator sistem jika diperlukan
    4. Jalankan ulang proses EOD untuk tanggal yang sama

!!! note "Catatan"
    Proses EOD menghasilkan jurnal otomatis dengan sumber **EOD** untuk setiap perhitungan bunga dan CKPN. Jurnal ini dapat dilihat pada modul Jurnal Umum dengan filter sumber = EOD.
