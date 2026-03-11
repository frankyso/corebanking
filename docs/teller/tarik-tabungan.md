# Tarik Tabungan

Fitur Tarik Tabungan memungkinkan teller memproses penarikan tunai dari rekening tabungan nasabah. Transaksi ini akan mengurangi saldo rekening tabungan dan mencatat pengeluaran kas pada sesi teller.

## Informasi Akses

| Item            | Detail                          |
| --------------- | ------------------------------- |
| **URL**         | `/admin/teller-dashboard`       |
| **Permission**  | `teller.open-session`           |
| **Menu**        | Teller > Dashboard > Tarik Tabungan |
| **Role**        | Teller, Supervisor, Admin       |
| **Prasyarat**   | Sesi teller harus aktif         |

## Formulir Tarik Tabungan

Klik tombol **Tarik Tabungan** pada Dashboard Teller untuk membuka formulir penarikan.

| Field                  | Tipe Input              | Wajib | Keterangan                                     |
| ---------------------- | ----------------------- | ----- | ----------------------------------------------- |
| **Rekening Tabungan**  | Searchable Select       | Ya    | Cari dan pilih rekening tabungan nasabah        |
| **Jumlah**             | Numeric (prefix Rp)     | Ya    | Nominal penarikan dalam Rupiah                  |
| **Keterangan**         | Text                    | Tidak | Catatan atau deskripsi tambahan untuk transaksi |

!!! tip "Pencarian Rekening"
    Field Rekening Tabungan mendukung pencarian berdasarkan nomor rekening maupun nama nasabah. Ketik sebagian nomor rekening atau nama untuk menemukan rekening yang dituju.

## Langkah-Langkah

1. Pastikan sesi teller sudah **aktif** pada Dashboard Teller.
2. Klik tombol **Tarik Tabungan**.
3. Cari dan pilih **Rekening Tabungan** nasabah yang akan melakukan penarikan.
4. Masukkan **Jumlah** penarikan dalam Rupiah.
5. Tambahkan **Keterangan** jika diperlukan (opsional).
6. Klik tombol **Simpan** untuk memproses penarikan.
7. Sistem akan memproses transaksi dan menampilkan notifikasi keberhasilan.

## Validasi

| Validasi                          | Pesan Error                                          |
| --------------------------------- | ---------------------------------------------------- |
| Sesi teller tidak aktif           | Anda harus membuka sesi terlebih dahulu              |
| Rekening tabungan tidak dipilih   | Rekening tabungan wajib dipilih                      |
| Rekening tidak berstatus Active   | Rekening tabungan harus berstatus aktif              |
| Jumlah tidak diisi                | Jumlah penarikan wajib diisi                         |
| Jumlah bernilai nol atau negatif  | Jumlah penarikan harus bernilai positif              |
| Saldo tidak mencukupi             | Saldo tersedia tidak mencukupi untuk penarikan ini   |

## Perhitungan Saldo Tersedia

Saldo yang tersedia untuk penarikan dihitung berdasarkan formula berikut:

```
Saldo Tersedia = Saldo Rekening - Hold Amount
```

| Komponen          | Keterangan                                                  |
| ----------------- | ----------------------------------------------------------- |
| **Saldo Rekening**| Saldo total yang tercatat pada rekening tabungan            |
| **Hold Amount**   | Jumlah saldo yang sedang ditahan (misalnya jaminan kredit)  |
| **Saldo Tersedia**| Jumlah maksimum yang dapat ditarik oleh nasabah             |

!!! danger "Saldo Tidak Mencukupi"
    Jika jumlah penarikan melebihi saldo tersedia, sistem akan menampilkan peringatan dan transaksi tidak dapat diproses. Pastikan untuk memeriksa saldo tersedia sebelum memproses penarikan.

!!! warning "Status Rekening"
    Penarikan hanya dapat dilakukan pada rekening tabungan yang berstatus **Active**. Rekening yang diblokir, ditutup, atau dormant tidak dapat melakukan penarikan.

## Proses di Balik Layar

Ketika penarikan berhasil diproses, sistem secara otomatis melakukan hal berikut:

1. **SavingsTransaction** - Membuat record transaksi tabungan bertipe debit (pengurangan saldo).
2. **Update Saldo** - Memperbarui saldo rekening tabungan nasabah.
3. **Jurnal Otomatis** - Membuat entri jurnal akuntansi secara otomatis (debit Tabungan, kredit Kas).
4. **Update Sesi** - Menambahkan nominal penarikan ke total kas keluar pada sesi teller.

## Lihat Juga

- [Dashboard Teller](dashboard-teller.md)
- [Buka & Tutup Sesi](buka-tutup-sesi.md)
- [Setor Tabungan](setor-tabungan.md)
