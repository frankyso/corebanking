# Persetujuan & Pencairan

Halaman ini menjelaskan alur proses persetujuan dan pencairan kredit pada sistem Core Banking BPR. Setiap permohonan kredit harus melalui tahapan review, persetujuan, dan pencairan yang melibatkan beberapa peran dengan wewenang berbeda untuk memastikan prinsip kehati-hatian bank terpenuhi.

---

## Hak Akses per Tahap

| Aksi               | Role yang Berwenang        | Permission yang Diperlukan |
|--------------------|----------------------------|----------------------------|
| Membuat Permohonan | LoanOfficer                | `loan-application.create`  |
| Review Permohonan  | LoanOfficer, BranchManager | `loan-application.review`  |
| Menyetujui         | BranchManager              | `loan-application.approve` |
| Mencairkan         | BranchManager              | `loan-application.disburse`|
| Menolak            | BranchManager              | `loan-application.reject`  |

!!! warning "Perhatian"
    Prinsip **four-eyes principle** diterapkan dalam proses kredit. Petugas yang membuat permohonan tidak boleh menjadi petugas yang menyetujui permohonan tersebut. Sistem akan memvalidasi hal ini secara otomatis.

---

## Alur Persetujuan

### 1. Submitted (Pengajuan)

Tahap awal ketika Loan Officer membuat permohonan kredit baru.

| Item                | Keterangan                                                     |
|---------------------|-----------------------------------------------------------------|
| Status              | **Submitted**                                                   |
| Dibuat oleh         | LoanOfficer                                                     |
| Aksi selanjutnya    | Review oleh LoanOfficer/BranchManager, atau Reject              |
| Dapat diedit        | Ya, oleh LoanOfficer yang membuat                               |

**Yang dilakukan pada tahap ini:**

1. Loan Officer mengisi formulir permohonan kredit.
2. Melengkapi data agunan pada halaman detail permohonan.
3. Memverifikasi kelengkapan dokumen nasabah.
4. Mengajukan permohonan untuk direview.

---

### 2. UnderReview (Dalam Review)

Permohonan sedang direview dan dianalisis oleh pejabat berwenang.

| Item                | Keterangan                                                     |
|---------------------|-----------------------------------------------------------------|
| Status              | **UnderReview**                                                 |
| Direview oleh       | LoanOfficer atau BranchManager                                  |
| Aksi selanjutnya    | Approve oleh BranchManager, atau Reject                         |
| Dapat diedit        | Tidak                                                           |

**Yang dilakukan pada tahap ini:**

1. Pejabat berwenang memeriksa kelengkapan data permohonan.
2. Melakukan analisis kelayakan kredit nasabah.
3. Mengevaluasi nilai agunan dan rasio LTV (Loan to Value).
4. Memeriksa riwayat kredit dan risk rating nasabah.
5. Menentukan jumlah dan tenor yang disetujui (dapat berbeda dari yang diminta).

!!! info "Informasi"
    Jumlah yang disetujui dan tenor yang disetujui dapat berbeda dari yang diminta nasabah berdasarkan hasil analisis kelayakan. Pejabat berwenang akan mengisi field **Jumlah Disetujui** dan **Tenor Disetujui** pada saat review.

---

### 3. Approved (Disetujui)

Permohonan telah disetujui oleh Branch Manager dan siap untuk dicairkan.

| Item                | Keterangan                                                     |
|---------------------|-----------------------------------------------------------------|
| Status              | **Approved**                                                    |
| Disetujui oleh      | BranchManager                                                   |
| Aksi selanjutnya    | Disburse oleh BranchManager                                     |
| Dapat diedit        | Tidak                                                           |

**Yang dilakukan pada tahap ini:**

1. Branch Manager memverifikasi hasil analisis dan rekomendasi.
2. Menetapkan jumlah dan tenor final yang disetujui.
3. Memberikan persetujuan resmi melalui aksi **Approve** pada sistem.
4. Sistem mencatat nama pejabat dan tanggal persetujuan secara otomatis.

!!! note "Catatan"
    Setelah status berubah menjadi **Approved**, permohonan menunggu proses pencairan. Pencairan hanya dapat dilakukan oleh pejabat yang memiliki permission `loan-application.disburse`.

---

### 4. Disbursed (Dicairkan)

Kredit telah dicairkan dan rekening kredit telah dibuat dalam sistem.

| Item                | Keterangan                                                     |
|---------------------|-----------------------------------------------------------------|
| Status              | **Disbursed**                                                   |
| Dicairkan oleh      | BranchManager (dengan permission disburse)                      |
| Status akhir        | Ya, tidak dapat diubah                                          |

**Proses yang terjadi saat pencairan:**

1. Sistem membuat **Rekening Kredit** (Loan Account) baru secara otomatis.
2. Sistem men-generate **Jadwal Angsuran** (Payment Schedule) berdasarkan jumlah disetujui, tenor disetujui, suku bunga, dan tipe bunga produk kredit.
3. Sistem membuat **Jurnal Pencairan** (Journal Entry) yang mencatat:
    - Debit: Akun GL Kredit (aset pinjaman)
    - Kredit: Akun kas/rekening nasabah (dana pencairan)
    - Kredit: Akun pendapatan fee (biaya administrasi dan provisi, jika ada)
4. Sistem mencatat tanggal pencairan (**Disbursed At**) secara otomatis.

!!! success "Pencairan Berhasil"
    Setelah pencairan berhasil, pengguna akan diarahkan ke halaman **Rekening Kredit** yang baru dibuat. Jadwal angsuran dapat dilihat pada tab Schedules di halaman detail rekening kredit.

---

### 5. Rejected (Ditolak)

Permohonan ditolak oleh Branch Manager. Penolakan dapat dilakukan pada status **Submitted**, **UnderReview**, maupun **Approved**.

| Item                | Keterangan                                                     |
|---------------------|-----------------------------------------------------------------|
| Status              | **Rejected**                                                    |
| Ditolak oleh        | BranchManager                                                   |
| Status akhir        | Ya, tidak dapat diubah                                          |

**Proses penolakan:**

1. Branch Manager memilih aksi **Reject** pada halaman detail permohonan.
2. Sistem mewajibkan pengisian **Alasan Penolakan** (Rejection Reason).
3. Alasan penolakan akan tersimpan dan dapat dilihat pada halaman detail permohonan.
4. Loan Officer yang membuat permohonan mendapat notifikasi penolakan.

!!! danger "Penolakan Bersifat Final"
    Permohonan yang telah ditolak tidak dapat diaktifkan kembali. Jika nasabah ingin mengajukan kembali, harus dibuat permohonan kredit baru.

---

## Ringkasan Diagram Alur

```
┌───────────┐     ┌─────────────┐     ┌──────────┐     ┌───────────┐
│ Submitted │────►│ UnderReview │────►│ Approved │────►│ Disbursed │
└───────────┘     └─────────────┘     └──────────┘     └───────────┘
      │                  │                  │
      │                  │                  │
      ▼                  ▼                  ▼
┌──────────┐      ┌──────────┐      ┌──────────┐
│ Rejected │      │ Rejected │      │ Rejected │
└──────────┘      └──────────┘      └──────────┘
```

---

## Panduan Langkah demi Langkah

### Mereview Permohonan Kredit

1. Buka menu **Kredit > Permohonan Kredit**.
2. Filter status **Submitted** untuk melihat permohonan yang menunggu review.
3. Klik pada permohonan yang akan direview.
4. Periksa seluruh data permohonan: nasabah, produk, jumlah, tenor, tujuan, dan agunan.
5. Klik tombol aksi **Review** untuk mengubah status menjadi **UnderReview**.
6. Tentukan **Jumlah Disetujui** dan **Tenor Disetujui** berdasarkan hasil analisis.

### Menyetujui Permohonan Kredit

1. Buka menu **Kredit > Permohonan Kredit**.
2. Filter status **UnderReview** untuk melihat permohonan yang menunggu persetujuan.
3. Klik pada permohonan yang akan disetujui.
4. Verifikasi hasil review, data agunan, dan risk rating nasabah.
5. Pastikan **Jumlah Disetujui** dan **Tenor Disetujui** telah terisi.
6. Klik tombol aksi **Approve** untuk menyetujui permohonan.
7. Status akan berubah menjadi **Approved**.

### Mencairkan Kredit

1. Buka menu **Kredit > Permohonan Kredit**.
2. Filter status **Approved** untuk melihat permohonan yang siap dicairkan.
3. Klik pada permohonan yang akan dicairkan.
4. Periksa kembali seluruh data dan pastikan dokumen lengkap.
5. Klik tombol aksi **Disburse** untuk melakukan pencairan.
6. Sistem akan otomatis:
    - Membuat rekening kredit baru.
    - Membuat jadwal angsuran.
    - Membuat jurnal pencairan.
7. Pengguna akan diarahkan ke halaman rekening kredit yang baru dibuat.

### Menolak Permohonan Kredit

1. Buka halaman detail permohonan kredit.
2. Klik tombol aksi **Reject**.
3. Isi **Alasan Penolakan** pada dialog yang muncul.
4. Konfirmasi penolakan.
5. Status permohonan berubah menjadi **Rejected**.

!!! warning "Perhatian"
    Sebelum menolak permohonan, pastikan telah berkomunikasi dengan Loan Officer terkait. Penolakan bersifat final dan tidak dapat dibatalkan.

---

## Checklist Sebelum Persetujuan

Berikut checklist yang perlu diperhatikan sebelum menyetujui permohonan kredit:

| No | Item Pemeriksaan                                  | Keterangan                               |
|----|---------------------------------------------------|------------------------------------------|
| 1  | Data nasabah lengkap dan valid                    | CIF, identitas, alamat, dokumen          |
| 2  | Risk rating nasabah sesuai                        | Tidak dalam status Blocked               |
| 3  | Produk kredit sesuai kebutuhan                    | Jenis kredit dan tujuan penggunaan       |
| 4  | Jumlah dan tenor dalam batas produk               | Tidak melebihi limit produk kredit       |
| 5  | Agunan memenuhi rasio LTV                         | Nilai taksasi mencukupi                  |
| 6  | Tidak ada kredit bermasalah                       | Kolektibilitas kredit existing baik      |
| 7  | Dokumen pendukung lengkap                         | Sesuai ketentuan yang berlaku            |

---

## Lihat Juga

- [Permohonan Kredit](permohonan-kredit.md)
- [Rekening Kredit](rekening-kredit.md)
- [Jadwal Angsuran](jadwal-angsuran.md)
- [Kolektibilitas & CKPN](kolektibilitas-ckpn.md)
