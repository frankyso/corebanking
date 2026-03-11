# Buka & Tutup Sesi Teller

Sesi teller merupakan mekanisme pencatatan aktivitas transaksi harian petugas teller. Setiap teller wajib membuka sesi sebelum melakukan transaksi dan menutup sesi di akhir hari kerja.

## Informasi Akses

| Item            | Detail                          |
| --------------- | ------------------------------- |
| **URL**         | `/admin/teller-dashboard`       |
| **Permission**  | `teller.open-session`           |
| **Menu**        | Teller > Dashboard              |
| **Role**        | Teller, Supervisor, Admin       |

## Ketentuan Sesi

- Setiap teller hanya dapat memiliki **satu sesi aktif** pada satu waktu.
- Sesi **wajib ditutup** sebelum akhir hari kerja.
- Seluruh transaksi yang dilakukan selama sesi akan **terhubung langsung** ke sesi teller tersebut.

!!! warning "Penting"
    Seluruh transaksi (setor tabungan, tarik tabungan, bayar angsuran) yang diproses selama sesi akan tercatat dan terkait dengan sesi teller yang sedang aktif. Pastikan sesi ditutup dengan benar di akhir hari.

## Buka Sesi

### Langkah-Langkah

1. Buka halaman **Dashboard Teller** melalui menu navigasi.
2. Klik tombol **Buka Sesi**.
3. Isi formulir pembukaan sesi:

| Field          | Tipe Input        | Keterangan                                      |
| -------------- | ----------------- | ------------------------------------------------ |
| **Vault**      | Select (dropdown) | Pilih vault/brankas yang akan digunakan          |
| **Kas Awal**   | Numeric (Rp)      | Masukkan saldo kas awal yang diterima dari vault |

4. Klik tombol **Buka Sesi** untuk mengkonfirmasi.
5. Dashboard akan berubah ke tampilan sesi aktif dengan informasi saldo dan tombol aksi transaksi.

!!! tip "Pemilihan Vault"
    Pilih vault yang sesuai dengan cabang tempat Anda bertugas. Vault yang tersedia sudah difilter berdasarkan cabang dan status aktif.

### Validasi Pembukaan Sesi

| Validasi                        | Pesan Error                                    |
| ------------------------------- | ---------------------------------------------- |
| Sudah ada sesi aktif            | Anda sudah memiliki sesi yang sedang aktif     |
| Vault tidak dipilih             | Vault wajib dipilih                            |
| Kas awal tidak diisi            | Kas awal wajib diisi                           |
| Kas awal bernilai negatif       | Kas awal harus bernilai positif                |

## Tutup Sesi

### Langkah-Langkah

1. Pada **Dashboard Teller**, klik tombol **Tutup Sesi**.
2. Sistem menampilkan ringkasan sesi yang mencakup:

| Informasi           | Keterangan                                          |
| -------------------- | --------------------------------------------------- |
| **Kas Awal**         | Saldo kas saat sesi dibuka                          |
| **Total Kas Masuk**  | Akumulasi seluruh penerimaan kas selama sesi        |
| **Total Kas Keluar** | Akumulasi seluruh pengeluaran kas selama sesi       |
| **Saldo Saat Ini**   | Saldo kas akhir (Kas Awal + Masuk - Keluar)        |
| **Jumlah Transaksi** | Total transaksi yang diproses selama sesi           |

3. Masukkan **Catatan Penutupan** (opsional) untuk mencatat hal-hal penting selama sesi.
4. Klik tombol **Tutup Sesi** untuk mengkonfirmasi penutupan.
5. Sesi ditutup dan dashboard kembali ke tampilan tanpa sesi aktif.

!!! note "Catatan Penutupan"
    Gunakan catatan penutupan untuk mencatat kejadian penting selama sesi, seperti selisih kas atau transaksi yang memerlukan perhatian khusus.

### Validasi Penutupan Sesi

| Validasi                        | Pesan Error                                    |
| ------------------------------- | ---------------------------------------------- |
| Tidak ada sesi aktif            | Tidak ada sesi yang dapat ditutup              |

## Alur Kerja Sesi Teller

```
Teller Login
    │
    ▼
Dashboard Teller (Belum Ada Sesi)
    │
    ▼
Buka Sesi (Pilih Vault + Kas Awal)
    │
    ▼
Dashboard Teller (Sesi Aktif)
    │
    ├── Setor Tabungan
    ├── Tarik Tabungan
    └── Bayar Angsuran
    │
    ▼
Tutup Sesi (Review + Catatan)
    │
    ▼
Dashboard Teller (Belum Ada Sesi)
```

## Lihat Juga

- [Dashboard Teller](dashboard-teller.md)
- [Vault / Brankas](vault.md)
