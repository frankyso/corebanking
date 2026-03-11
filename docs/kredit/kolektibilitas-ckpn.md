# Kolektibilitas & CKPN

Halaman ini menjelaskan mekanisme penentuan kolektibilitas kredit dan perhitungan Cadangan Kerugian Penurunan Nilai (CKPN) pada sistem Core Banking BPR. Kolektibilitas dan CKPN merupakan indikator kualitas aset kredit yang wajib dipantau secara berkala sesuai ketentuan Otoritas Jasa Keuangan (OJK).

---

## Hak Akses

| Role           | Lihat Kolektibilitas | Lihat CKPN     |
|----------------|:--------------------:|:--------------:|
| SuperAdmin     | Semua cabang         | Semua cabang   |
| Auditor        | Semua cabang         | Semua cabang   |
| Compliance     | Semua cabang         | Semua cabang   |
| BranchManager  | Semua cabang         | Semua cabang   |
| LoanOfficer    | Cabang sendiri       | Cabang sendiri |
| Teller         | Cabang sendiri       | Cabang sendiri |
| CustomerService| Cabang sendiri       | Cabang sendiri |

!!! info "Informasi"
    Data kolektibilitas dan CKPN ditampilkan pada halaman detail **Rekening Kredit** di section **Balance & Collectibility**. Nilai ini diperbarui secara otomatis oleh proses End of Day (EOD) dan tidak dapat diubah secara manual.

---

## Kolektibilitas Kredit

Kolektibilitas adalah klasifikasi kualitas kredit berdasarkan jumlah hari keterlambatan pembayaran (Days Past Due / DPD). Sistem menerapkan 5 tingkat kolektibilitas sesuai dengan ketentuan regulator.

### Tabel Kolektibilitas

| Kol | Nama                        | DPD          | Warna    | Rate CKPN | Keterangan                                    |
|:---:|-----------------------------|:------------:|:--------:|:---------:|------------------------------------------------|
| 1   | Lancar (Current)            | 0            | Hijau    | 1%        | Kredit berjalan lancar tanpa keterlambatan.    |
| 2   | Dalam Perhatian Khusus (Special Mention) | 1 - 90  | Kuning   | 5%        | Terdapat keterlambatan ringan hingga sedang.   |
| 3   | Kurang Lancar (Substandard) | 91 - 120     | Oranye   | 15%       | Keterlambatan signifikan, perlu perhatian.     |
| 4   | Diragukan (Doubtful)        | 121 - 180    | Merah    | 50%       | Keterlambatan tinggi, kemungkinan gagal bayar. |
| 5   | Macet (Loss)                | > 180        | Abu-abu  | 100%      | Kredit tidak dapat ditagih, perlu write-off.   |

!!! warning "Perhatian"
    Kolektibilitas **3 (Kurang Lancar)**, **4 (Diragukan)**, dan **5 (Macet)** termasuk dalam kategori **Non-Performing Loan (NPL)**. Bank wajib melaporkan rasio NPL kepada OJK secara berkala.

### Cara Penentuan Kolektibilitas

Kolektibilitas ditentukan secara otomatis oleh sistem berdasarkan nilai DPD:

1. Sistem menghitung DPD dari angsuran tertunggak paling lama yang belum dibayar.
2. Berdasarkan nilai DPD, sistem menetapkan kolektibilitas sesuai tabel di atas.
3. Kolektibilitas diperbarui setiap hari melalui proses **End of Day (EOD)**.

```
DPD = 0           → Kol 1 (Lancar)
DPD = 1 - 90      → Kol 2 (Dalam Perhatian Khusus)
DPD = 91 - 120    → Kol 3 (Kurang Lancar)          ← NPL
DPD = 121 - 180   → Kol 4 (Diragukan)              ← NPL
DPD > 180         → Kol 5 (Macet)                   ← NPL
```

---

## CKPN (Cadangan Kerugian Penurunan Nilai)

CKPN adalah cadangan yang wajib dibentuk oleh bank untuk mengantisipasi potensi kerugian dari kredit yang mengalami penurunan kualitas. Besaran CKPN ditentukan berdasarkan kolektibilitas kredit.

### Rumus Perhitungan CKPN

CKPN per rekening kredit dihitung sebagai berikut:

```
CKPN = Outstanding Pokok x Rate CKPN (sesuai kolektibilitas)
```

### Contoh Perhitungan CKPN

!!! example "Contoh Perhitungan CKPN per Rekening"

    | Rekening      | Outstanding Pokok | Kolektibilitas | Rate CKPN | CKPN              |
    |---------------|------------------:|:--------------:|:---------:|-------------------:|
    | LN-2025-0001  | Rp 50.000.000     | Kol 1          | 1%        | Rp 500.000         |
    | LN-2025-0002  | Rp 30.000.000     | Kol 2          | 5%        | Rp 1.500.000       |
    | LN-2025-0003  | Rp 20.000.000     | Kol 3          | 15%       | Rp 3.000.000       |
    | LN-2025-0004  | Rp 15.000.000     | Kol 4          | 50%       | Rp 7.500.000       |
    | LN-2025-0005  | Rp 10.000.000     | Kol 5          | 100%      | Rp 10.000.000      |
    | **Total**     | **Rp 125.000.000**|                |           | **Rp 22.500.000**  |

### Total CKPN Bank

Total CKPN bank dihitung dari penjumlahan seluruh CKPN per rekening kredit:

```
Total CKPN = SUM( Outstanding per Rekening x Rate CKPN sesuai Kolektibilitas )
```

!!! note "Catatan"
    Nilai CKPN yang ditampilkan pada halaman detail rekening kredit (**CKPN Amount**) merupakan cadangan yang harus dibentuk untuk rekening tersebut. Total CKPN seluruh bank dapat dilihat pada laporan portofolio kredit.

---

## Non-Performing Loan (NPL)

NPL adalah kredit bermasalah yang termasuk dalam kolektibilitas 3, 4, dan 5.

### Rumus NPL

```
NPL Outstanding = Outstanding Kol 3 + Outstanding Kol 4 + Outstanding Kol 5

NPL Ratio = (NPL Outstanding / Total Outstanding Seluruh Kredit) x 100%
```

### Indikator Kesehatan

| NPL Ratio | Status                  | Keterangan                                         |
|:---------:|-------------------------|-----------------------------------------------------|
| < 5%      | Sehat (Healthy)         | Rasio NPL dalam batas aman sesuai ketentuan OJK.   |
| >= 5%     | Tidak Sehat (Unhealthy) | Rasio NPL melebihi batas, perlu tindakan korektif. |

!!! danger "Batas NPL"
    OJK menetapkan batas maksimal rasio NPL sebesar **5%**. Bank dengan rasio NPL di atas 5% akan mendapat teguran dan pengawasan khusus dari regulator. Diperlukan langkah penanganan segera seperti restrukturisasi, penagihan intensif, atau write-off.

### Contoh Perhitungan NPL Ratio

!!! example "Contoh Perhitungan NPL Ratio"

    | Kolektibilitas | Outstanding         |
    |:--------------:|--------------------:|
    | Kol 1          | Rp 800.000.000      |
    | Kol 2          | Rp 120.000.000      |
    | Kol 3          | Rp 30.000.000       |
    | Kol 4          | Rp 15.000.000       |
    | Kol 5          | Rp 10.000.000       |
    | **Total**      | **Rp 975.000.000**  |

    - NPL Outstanding = Rp 30.000.000 + Rp 15.000.000 + Rp 10.000.000 = **Rp 55.000.000**
    - NPL Ratio = (Rp 55.000.000 / Rp 975.000.000) x 100% = **5,64%**
    - Status: **Tidak Sehat** (melebihi batas 5%)

---

## Proses Update Otomatis

Kolektibilitas dan CKPN diperbarui secara otomatis melalui proses **End of Day (EOD)**.

### Langkah Proses EOD untuk Kredit

| No | Proses                           | Keterangan                                            |
|----|----------------------------------|-------------------------------------------------------|
| 1  | Perhitungan DPD                  | Menghitung DPD untuk setiap rekening kredit aktif.    |
| 2  | Penentuan Kolektibilitas         | Menetapkan kolektibilitas berdasarkan DPD.            |
| 3  | Perhitungan CKPN                 | Menghitung CKPN berdasarkan outstanding dan kolektibilitas. |
| 4  | Accrual Bunga                    | Mengakui bunga yang masih harus diterima (accrued interest). |
| 5  | Update Saldo                     | Memperbarui saldo outstanding pada setiap rekening.   |

!!! info "Informasi"
    Proses EOD dijalankan oleh operator yang berwenang melalui menu **Operasional > End of Day**. Pastikan proses EOD berjalan setiap hari kerja untuk menjaga akurasi data kolektibilitas dan CKPN.

---

## Tampilan pada Rekening Kredit

Data kolektibilitas dan CKPN ditampilkan pada section **Balance & Collectibility** di halaman detail rekening kredit:

| Field              | Keterangan                                                         |
|--------------------|--------------------------------------------------------------------|
| DPD                | Jumlah hari keterlambatan, ditampilkan dengan kode warna.          |
| Kolektibilitas     | Tingkat kolektibilitas (Kol 1-5) ditampilkan sebagai badge berwarna.|
| CKPN Amount        | Jumlah cadangan kerugian yang harus dibentuk untuk rekening ini.   |

Pada halaman daftar rekening kredit, kolom **DPD** dan **Kolektibilitas** juga ditampilkan untuk memudahkan pemantauan secara keseluruhan.

---

## Panduan Langkah demi Langkah

### Memantau Kolektibilitas Kredit

1. Buka menu **Kredit > Rekening Kredit**.
2. Perhatikan kolom **DPD** dan **Kolektibilitas** pada tabel daftar.
3. Gunakan filter **Kolektibilitas** untuk menyaring rekening berdasarkan tingkat kolektibilitas.
4. Klik pada rekening untuk melihat detail pada section **Balance & Collectibility**.

### Mengidentifikasi Kredit NPL

1. Buka menu **Kredit > Rekening Kredit**.
2. Gunakan filter **Kolektibilitas** dan pilih **Kol 3**, **Kol 4**, atau **Kol 5**.
3. Sistem akan menampilkan seluruh rekening kredit yang termasuk kategori NPL.
4. Periksa detail masing-masing rekening untuk menentukan langkah penanganan.

### Memastikan CKPN Terkini

1. Pastikan proses **End of Day (EOD)** telah dijalankan pada hari kerja terakhir.
2. Buka halaman detail rekening kredit.
3. Periksa nilai **CKPN Amount** pada section Balance & Collectibility.
4. Nilai CKPN yang ditampilkan merupakan posisi terakhir berdasarkan EOD terakhir.

!!! tip "Tips"
    Lakukan pemantauan kolektibilitas secara rutin, minimal mingguan, untuk mengidentifikasi kredit yang berpotensi menjadi NPL sejak dini. Tindakan preventif pada tahap kolektibilitas 2 (Dalam Perhatian Khusus) jauh lebih efektif daripada penanganan pada tahap kolektibilitas 3 ke atas.

---

## Glosarium

| Istilah     | Definisi                                                                      |
|-------------|-------------------------------------------------------------------------------|
| DPD         | Days Past Due - jumlah hari keterlambatan pembayaran angsuran.                |
| CKPN        | Cadangan Kerugian Penurunan Nilai - cadangan yang dibentuk untuk mengantisipasi potensi kerugian kredit. |
| NPL         | Non-Performing Loan - kredit bermasalah dengan kolektibilitas 3, 4, atau 5.   |
| LTV         | Loan to Value - rasio antara nilai pinjaman dengan nilai agunan.              |
| EOD         | End of Day - proses akhir hari yang memperbarui seluruh data kredit.          |
| Write-off   | Penghapusbukuan kredit dari neraca karena tidak dapat ditagih.                |
| OJK         | Otoritas Jasa Keuangan - lembaga pengawas sektor jasa keuangan di Indonesia.  |

---

## Lihat Juga

- [Rekening Kredit](rekening-kredit.md)
- [Jadwal Angsuran](jadwal-angsuran.md)
- [Permohonan Kredit](permohonan-kredit.md)
- [Produk Kredit](../master-data/produk-kredit.md)
