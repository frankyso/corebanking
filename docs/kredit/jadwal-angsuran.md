# Jadwal Angsuran & Pembayaran

Halaman ini menjelaskan mekanisme jadwal angsuran dan riwayat pembayaran pada rekening kredit. Jadwal angsuran digenerate secara otomatis oleh sistem saat kredit dicairkan dan menjadi acuan bagi nasabah dan bank untuk memantau kewajiban pembayaran.

---

## Hak Akses

| Role           | Lihat Jadwal   | Lihat Pembayaran | Proses Pembayaran |
|----------------|:--------------:|:----------------:|:-----------------:|
| SuperAdmin     | Semua cabang   | Semua cabang     | Tidak             |
| Auditor        | Semua cabang   | Semua cabang     | Tidak             |
| Compliance     | Semua cabang   | Semua cabang     | Tidak             |
| BranchManager  | Semua cabang   | Semua cabang     | Tidak             |
| LoanOfficer    | Cabang sendiri | Cabang sendiri   | Tidak             |
| Teller         | Cabang sendiri | Cabang sendiri   | Ya (via Dashboard)|
| CustomerService| Cabang sendiri | Cabang sendiri   | Tidak             |

!!! info "Informasi"
    Pembayaran angsuran kredit diproses melalui **Dashboard Teller** menggunakan tombol aksi **Bayar Angsuran**, bukan dari halaman rekening kredit. Halaman rekening kredit hanya menampilkan data jadwal dan riwayat pembayaran dalam mode hanya baca.

---

## Jadwal Angsuran (Schedules)

Jadwal angsuran merupakan rencana pembayaran bulanan yang digenerate otomatis saat kredit dicairkan. Jadwal ini dapat dilihat pada tab **Schedules** di halaman detail rekening kredit.

### Kolom Tabel Jadwal

| Kolom              | Keterangan                                                     |
|--------------------|-----------------------------------------------------------------|
| Angsuran ke        | Nomor urut angsuran, dimulai dari 1 hingga jumlah tenor.        |
| Tanggal Jatuh Tempo| Tanggal batas pembayaran angsuran untuk periode tersebut.       |
| Pokok              | Porsi pembayaran pokok pinjaman pada periode tersebut.          |
| Bunga              | Porsi pembayaran bunga pada periode tersebut.                   |
| Total Angsuran     | Total yang harus dibayar (Pokok + Bunga).                       |
| Sisa Pokok         | Sisa pokok pinjaman setelah angsuran pada periode tersebut dibayar. |

!!! note "Catatan"
    Jadwal angsuran bersifat tetap dan tidak berubah setelah digenerate, kecuali dalam kasus restrukturisasi kredit. Perubahan jadwal hanya dapat dilakukan oleh proses sistem khusus.

---

## Metode Perhitungan Angsuran

Perhitungan angsuran berbeda-beda tergantung pada **Tipe Bunga** yang diatur pada produk kredit.

### 1. Bunga Flat

Bunga dihitung dari pokok awal pinjaman dan tetap sepanjang tenor.

| Komponen        | Rumus                                                    |
|-----------------|----------------------------------------------------------|
| Bunga per bulan | Pokok Awal x (Suku Bunga / 12)                          |
| Pokok per bulan | Pokok Awal / Tenor                                       |
| Total Angsuran  | Pokok per bulan + Bunga per bulan (tetap setiap bulan)   |

!!! example "Contoh Perhitungan Flat"
    **Pinjaman:** Rp 12.000.000 | **Bunga:** 12% p.a. | **Tenor:** 12 bulan

    - Bunga per bulan: Rp 12.000.000 x (12% / 12) = **Rp 120.000**
    - Pokok per bulan: Rp 12.000.000 / 12 = **Rp 1.000.000**
    - Total angsuran per bulan: **Rp 1.120.000** (tetap)
    - Total bunga selama tenor: **Rp 1.440.000**

### 2. Bunga Efektif (Annuity)

Bunga dihitung dari sisa pokok pinjaman. Total angsuran tetap setiap bulan, namun proporsi pokok dan bunga berubah.

| Komponen        | Rumus                                                            |
|-----------------|------------------------------------------------------------------|
| Total Angsuran  | Pokok x [i(1+i)^n] / [(1+i)^n - 1], di mana i = suku bunga bulanan, n = tenor |
| Bunga per bulan | Sisa Pokok x (Suku Bunga / 12)                                  |
| Pokok per bulan | Total Angsuran - Bunga per bulan                                 |

!!! example "Contoh Perhitungan Efektif (Annuity)"
    **Pinjaman:** Rp 12.000.000 | **Bunga:** 12% p.a. | **Tenor:** 12 bulan

    - Suku bunga bulanan (i): 12% / 12 = 1%
    - Total angsuran per bulan: **Rp 1.066.185** (tetap)
    - Bulan ke-1: Pokok Rp 946.185 + Bunga Rp 120.000
    - Bulan ke-12: Pokok Rp 1.055.629 + Bunga Rp 10.556
    - Total bunga selama tenor: **Rp 794.217**

### 3. Bunga Sliding

Bunga dihitung dari sisa pokok pinjaman. Pokok per bulan tetap, sehingga total angsuran menurun seiring berjalannya tenor.

| Komponen        | Rumus                                                    |
|-----------------|----------------------------------------------------------|
| Pokok per bulan | Pokok Awal / Tenor (tetap)                               |
| Bunga per bulan | Sisa Pokok x (Suku Bunga / 12) (menurun)                |
| Total Angsuran  | Pokok per bulan + Bunga per bulan (menurun setiap bulan) |

!!! example "Contoh Perhitungan Sliding"
    **Pinjaman:** Rp 12.000.000 | **Bunga:** 12% p.a. | **Tenor:** 12 bulan

    - Pokok per bulan: Rp 12.000.000 / 12 = **Rp 1.000.000** (tetap)
    - Bulan ke-1: Bunga Rp 120.000, Total **Rp 1.120.000**
    - Bulan ke-6: Bunga Rp 70.000, Total **Rp 1.070.000**
    - Bulan ke-12: Bunga Rp 10.000, Total **Rp 1.010.000**
    - Total bunga selama tenor: **Rp 780.000**

---

## Perbandingan Tipe Bunga

| Aspek              | Flat          | Efektif (Annuity) | Sliding       |
|--------------------|---------------|---------------------|---------------|
| Total Angsuran     | Tetap         | Tetap               | Menurun       |
| Porsi Pokok        | Tetap         | Meningkat           | Tetap         |
| Porsi Bunga        | Tetap         | Menurun             | Menurun       |
| Total Bunga        | Tertinggi     | Sedang              | Terendah      |
| Beban Awal         | Sedang        | Sedang              | Tertinggi     |

---

## Riwayat Pembayaran (Payments)

Riwayat pembayaran menampilkan seluruh transaksi pembayaran angsuran yang telah diterima. Data ini dapat dilihat pada tab **Payments** di halaman detail rekening kredit.

### Kolom Tabel Pembayaran

| Kolom              | Keterangan                                                     |
|--------------------|-----------------------------------------------------------------|
| Tanggal Bayar      | Tanggal pembayaran angsuran diterima oleh bank.                 |
| Jumlah Bayar       | Total nominal pembayaran yang diterima dari nasabah.            |
| Pokok Dibayar      | Porsi pembayaran yang dialokasikan untuk mengurangi pokok.      |
| Bunga Dibayar      | Porsi pembayaran yang dialokasikan untuk bunga.                 |
| Denda              | Denda keterlambatan yang dibayarkan (jika ada).                 |

!!! info "Informasi"
    Alokasi pembayaran mengikuti urutan prioritas: **Denda** terlebih dahulu, kemudian **Bunga**, dan terakhir **Pokok**. Urutan ini sesuai dengan standar akuntansi perbankan.

---

## DPD (Days Past Due)

DPD atau hari keterlambatan dihitung berdasarkan item jadwal angsuran yang telah melewati tanggal jatuh tempo namun belum dibayar.

### Cara Perhitungan DPD

| Kondisi                                  | DPD                                           |
|------------------------------------------|------------------------------------------------|
| Semua angsuran dibayar tepat waktu       | 0                                              |
| Ada angsuran tertunggak                  | Selisih hari antara tanggal jatuh tempo angsuran tertunggak paling lama dengan tanggal hari ini |

!!! warning "Perhatian"
    DPD diperbarui setiap hari melalui proses **End of Day (EOD)**. Nilai DPD yang ditampilkan merupakan posisi terakhir berdasarkan EOD terakhir. DPD menjadi dasar penentuan kolektibilitas rekening kredit.

---

## Panduan Langkah demi Langkah

### Melihat Jadwal Angsuran

1. Buka menu **Kredit > Rekening Kredit**.
2. Cari dan klik rekening kredit yang diinginkan.
3. Pada halaman detail, pilih tab **Schedules**.
4. Periksa jadwal angsuran dari angsuran ke-1 hingga angsuran terakhir.

### Melihat Riwayat Pembayaran

1. Buka menu **Kredit > Rekening Kredit**.
2. Cari dan klik rekening kredit yang diinginkan.
3. Pada halaman detail, pilih tab **Payments**.
4. Periksa riwayat pembayaran yang telah diterima.

### Memproses Pembayaran Angsuran (via Teller)

1. Buka menu **Teller > Dashboard**.
2. Pastikan sesi teller sudah dibuka.
3. Klik tombol **Bayar Angsuran**.
4. Cari rekening kredit nasabah berdasarkan nomor rekening atau nama.
5. Masukkan jumlah pembayaran.
6. Verifikasi data dan konfirmasi pembayaran.
7. Sistem akan otomatis mengalokasikan pembayaran ke denda, bunga, dan pokok.
8. Pembayaran tercatat pada tab **Payments** di rekening kredit.

### Mengidentifikasi Angsuran Tertunggak

1. Buka halaman detail rekening kredit.
2. Periksa nilai **DPD** pada section Balance & Collectibility.
3. Buka tab **Schedules** dan bandingkan tanggal jatuh tempo dengan tanggal hari ini.
4. Angsuran yang telah melewati tanggal jatuh tempo namun belum ada pembayaran pada tab **Payments** merupakan angsuran tertunggak.

!!! tip "Tips"
    Bandingkan jumlah item pada tab **Schedules** yang telah jatuh tempo dengan jumlah item pada tab **Payments** untuk memastikan semua angsuran telah dibayar tepat waktu.

---

## Lihat Juga

- [Rekening Kredit](rekening-kredit.md)
- [Kolektibilitas & CKPN](kolektibilitas-ckpn.md)
- [Produk Kredit](../master-data/produk-kredit.md)
- [Dashboard Teller](../teller/dashboard-teller.md)
