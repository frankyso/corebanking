# Setor Tabungan

Fitur Setor Tabungan memungkinkan teller menerima dan memproses setoran tunai ke rekening tabungan nasabah. Transaksi ini akan menambah saldo rekening tabungan dan mencatat penerimaan kas pada sesi teller.

## Informasi Akses

| Item            | Detail                          |
| --------------- | ------------------------------- |
| **URL**         | `/admin/teller-dashboard`       |
| **Permission**  | `teller.open-session`           |
| **Menu**        | Teller > Dashboard > Setor Tabungan |
| **Role**        | Teller, Supervisor, Admin       |
| **Prasyarat**   | Sesi teller harus aktif         |

## Formulir Setor Tabungan

Klik tombol **Setor Tabungan** pada Dashboard Teller untuk membuka formulir setoran.

| Field                  | Tipe Input              | Wajib | Keterangan                                     |
| ---------------------- | ----------------------- | ----- | ----------------------------------------------- |
| **Rekening Tabungan**  | Searchable Select       | Ya    | Cari dan pilih rekening tabungan nasabah        |
| **Jumlah**             | Numeric (prefix Rp)     | Ya    | Nominal setoran dalam Rupiah                    |
| **Keterangan**         | Text                    | Tidak | Catatan atau deskripsi tambahan untuk transaksi |

!!! tip "Pencarian Rekening"
    Field Rekening Tabungan mendukung pencarian berdasarkan nomor rekening maupun nama nasabah. Ketik sebagian nomor rekening atau nama untuk menemukan rekening yang dituju.

## Langkah-Langkah

1. Pastikan sesi teller sudah **aktif** pada Dashboard Teller.
2. Klik tombol **Setor Tabungan**.
3. Cari dan pilih **Rekening Tabungan** nasabah yang akan menyetor.
4. Masukkan **Jumlah** setoran dalam Rupiah.
5. Tambahkan **Keterangan** jika diperlukan (opsional).
6. Klik tombol **Simpan** untuk memproses setoran.
7. Sistem akan memproses transaksi dan menampilkan notifikasi keberhasilan.

## Validasi

| Validasi                          | Pesan Error                                      |
| --------------------------------- | ------------------------------------------------ |
| Sesi teller tidak aktif           | Anda harus membuka sesi terlebih dahulu          |
| Rekening tabungan tidak dipilih   | Rekening tabungan wajib dipilih                  |
| Rekening tidak berstatus Active   | Rekening tabungan harus berstatus aktif          |
| Jumlah tidak diisi                | Jumlah setoran wajib diisi                       |
| Jumlah bernilai nol atau negatif  | Jumlah setoran harus bernilai positif            |

!!! warning "Status Rekening"
    Setoran hanya dapat dilakukan pada rekening tabungan yang berstatus **Active**. Rekening yang diblokir, ditutup, atau dormant tidak dapat menerima setoran melalui teller.

## Proses di Balik Layar

Ketika setoran berhasil diproses, sistem secara otomatis melakukan hal berikut:

1. **SavingsTransaction** - Membuat record transaksi tabungan bertipe kredit (penambahan saldo).
2. **Update Saldo** - Memperbarui saldo rekening tabungan nasabah.
3. **Jurnal Otomatis** - Membuat entri jurnal akuntansi secara otomatis (debit Kas, kredit Tabungan).
4. **Update Sesi** - Menambahkan nominal setoran ke total kas masuk pada sesi teller.

## Lihat Juga

- [Dashboard Teller](dashboard-teller.md)
- [Buka & Tutup Sesi](buka-tutup-sesi.md)
- [Tarik Tabungan](tarik-tabungan.md)
