# Bayar Angsuran

Fitur Bayar Angsuran memungkinkan teller menerima pembayaran angsuran kredit dari nasabah. Pembayaran akan dialokasikan ke jadwal angsuran yang belum terbayar dan memperbarui saldo pinjaman nasabah.

## Informasi Akses

| Item            | Detail                          |
| --------------- | ------------------------------- |
| **URL**         | `/admin/teller-dashboard`       |
| **Permission**  | `teller.open-session`           |
| **Menu**        | Teller > Dashboard > Bayar Angsuran |
| **Role**        | Teller, Supervisor, Admin       |
| **Prasyarat**   | Sesi teller harus aktif         |

## Formulir Bayar Angsuran

Klik tombol **Bayar Angsuran** pada Dashboard Teller untuk membuka formulir pembayaran.

| Field                | Tipe Input              | Wajib | Keterangan                                      |
| -------------------- | ----------------------- | ----- | ------------------------------------------------ |
| **Rekening Kredit**  | Searchable Select       | Ya    | Cari dan pilih rekening kredit/pinjaman nasabah  |
| **Jumlah**           | Numeric (prefix Rp)     | Ya    | Nominal pembayaran angsuran dalam Rupiah         |
| **Keterangan**       | Text                    | Tidak | Catatan atau deskripsi tambahan untuk transaksi  |

!!! tip "Pencarian Rekening Kredit"
    Field Rekening Kredit mendukung pencarian berdasarkan nomor rekening kredit maupun nama nasabah peminjam. Ketik sebagian nomor rekening atau nama untuk menemukan rekening yang dituju.

## Langkah-Langkah

1. Pastikan sesi teller sudah **aktif** pada Dashboard Teller.
2. Klik tombol **Bayar Angsuran**.
3. Cari dan pilih **Rekening Kredit** nasabah yang akan membayar angsuran.
4. Masukkan **Jumlah** pembayaran angsuran dalam Rupiah.
5. Tambahkan **Keterangan** jika diperlukan (opsional).
6. Klik tombol **Simpan** untuk memproses pembayaran.
7. Sistem akan memproses transaksi dan menampilkan notifikasi keberhasilan.

## Validasi

| Validasi                          | Pesan Error                                        |
| --------------------------------- | -------------------------------------------------- |
| Sesi teller tidak aktif           | Anda harus membuka sesi terlebih dahulu            |
| Rekening kredit tidak dipilih     | Rekening kredit wajib dipilih                      |
| Jumlah tidak diisi                | Jumlah pembayaran wajib diisi                      |
| Jumlah bernilai nol atau negatif  | Jumlah pembayaran harus bernilai positif           |

## Alokasi Pembayaran

Pembayaran angsuran yang diterima akan dialokasikan ke jadwal angsuran (installment schedule) yang belum terbayar. Sistem menerapkan pembayaran secara berurutan berdasarkan tanggal jatuh tempo angsuran.

| Komponen                     | Keterangan                                              |
| ---------------------------- | ------------------------------------------------------- |
| **Pokok (Principal)**        | Porsi pembayaran yang mengurangi saldo pokok pinjaman   |
| **Bunga (Interest)**         | Porsi pembayaran untuk bunga pinjaman                   |

## Proses di Balik Layar

Ketika pembayaran angsuran berhasil diproses, sistem secara otomatis melakukan hal berikut:

1. **LoanPayment** - Membuat record pembayaran pinjaman yang mencatat detail pembayaran.
2. **Update Jadwal Angsuran** - Menandai angsuran yang terbayar pada jadwal angsuran.
3. **Update Saldo Pinjaman** - Memperbarui outstanding principal (saldo pokok) dan outstanding interest (bunga terutang) pada rekening kredit.
4. **Jurnal Otomatis** - Membuat entri jurnal akuntansi secara otomatis.
5. **Update Sesi** - Menambahkan nominal pembayaran ke total kas masuk pada sesi teller.

!!! info "Pembayaran Parsial dan Penuh"
    Sistem mendukung pembayaran dengan nominal sesuai angsuran. Pastikan jumlah yang dibayarkan sesuai dengan angsuran yang jatuh tempo untuk menghindari perbedaan alokasi.

## Lihat Juga

- [Dashboard Teller](dashboard-teller.md)
- [Buka & Tutup Sesi](buka-tutup-sesi.md)
- [Setor Tabungan](setor-tabungan.md)
- [Tarik Tabungan](tarik-tabungan.md)
