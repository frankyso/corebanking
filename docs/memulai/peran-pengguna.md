# Peran & Hak Akses Pengguna

Core Banking BPR menerapkan sistem **Role-Based Access Control (RBAC)** untuk memastikan setiap pengguna hanya dapat mengakses fitur sesuai dengan peran dan tanggung jawabnya.

## Daftar Peran

Sistem mendefinisikan **8 peran** dengan cakupan akses yang berbeda-beda:

### 1. SuperAdmin

Memiliki **akses penuh** ke seluruh modul dan fitur dalam sistem tanpa batasan. Peran ini biasanya diberikan kepada administrator IT atau pejabat tertinggi yang bertanggung jawab atas konfigurasi sistem.

### 2. BranchManager

Kepala cabang atau manajer yang bertanggung jawab atas operasional cabang. Memiliki akses luas untuk memantau dan mengelola nasabah, rekening, pengajuan kredit, serta laporan.

### 3. CustomerService

Petugas layanan nasabah yang menangani pembukaan rekening, pendaftaran nasabah baru, dan informasi produk perbankan.

### 4. Teller

Petugas teller yang menangani transaksi tunai harian seperti setoran dan penarikan pada loket.

### 5. LoanOfficer

Analis kredit yang menangani proses pengajuan, analisis, dan monitoring pinjaman nasabah.

### 6. Accounting

Petugas akuntansi yang mengelola pencatatan jurnal, chart of account, proses End of Day (EOD), dan pelaporan keuangan.

### 7. Auditor

Auditor internal yang memiliki akses baca ke seluruh data untuk keperluan pemeriksaan dan audit.

### 8. Compliance

Petugas kepatuhan yang memantau kepatuhan terhadap regulasi dan kebijakan internal bank.

---

## Matriks Hak Akses

Tabel berikut menunjukkan hak akses setiap peran terhadap modul dan fitur dalam sistem.

!!! info "Keterangan Simbol"
    - **V** = Akses penuh pada modul tersebut
    - **R** = Hanya baca (_read-only_)
    - **-** = Tidak memiliki akses

### Modul Nasabah (Customer)

| Hak Akses          | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| customer.view       | V          | V             | V               | R      | R           | -          | R       | R          |
| customer.create     | V          | V             | V               | -      | -           | -          | -       | -          |
| customer.update     | V          | V             | V               | -      | -           | -          | -       | -          |
| customer.delete     | V          | V             | -               | -      | -           | -          | -       | -          |

### Modul Produk Tabungan (Savings Product)

| Hak Akses               | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ------------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| savings-product.view      | V          | V             | R               | -      | -           | -          | -       | -          |
| savings-product.create    | V          | -             | -               | -      | -           | -          | -       | -          |
| savings-product.update    | V          | -             | -               | -      | -           | -          | -       | -          |
| savings-product.delete    | V          | -             | -               | -      | -           | -          | -       | -          |

### Modul Rekening Tabungan (Savings Account)

| Hak Akses               | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ------------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| savings-account.view      | V          | V             | V               | R      | -           | -          | R       | -          |
| savings-account.create    | V          | V             | V               | -      | -           | -          | -       | -          |
| savings-account.update    | V          | V             | -               | -      | -           | -          | -       | -          |
| savings-account.delete    | V          | V             | -               | -      | -           | -          | -       | -          |
| savings-account.deposit   | V          | V             | -               | V      | -           | -          | -       | -          |
| savings-account.withdraw  | V          | V             | -               | V      | -           | -          | -       | -          |

### Modul Produk Deposito (Deposit Product)

| Hak Akses               | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ------------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| deposit-product.view      | V          | V             | R               | -      | -           | -          | -       | -          |
| deposit-product.create    | V          | -             | -               | -      | -           | -          | -       | -          |
| deposit-product.update    | V          | -             | -               | -      | -           | -          | -       | -          |
| deposit-product.delete    | V          | -             | -               | -      | -           | -          | -       | -          |

### Modul Rekening Deposito (Deposit Account)

| Hak Akses               | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ------------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| deposit-account.view      | V          | V             | V               | -      | -           | -          | R       | -          |
| deposit-account.create    | V          | V             | V               | -      | -           | -          | -       | -          |
| deposit-account.update    | V          | V             | -               | -      | -           | -          | -       | -          |
| deposit-account.delete    | V          | V             | -               | -      | -           | -          | -       | -          |

### Modul Produk Kredit (Loan Product)

| Hak Akses             | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ----------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| loan-product.view       | V          | V             | -               | -      | R           | -          | -       | -          |
| loan-product.create     | V          | -             | -               | -      | -           | -          | -       | -          |
| loan-product.update     | V          | -             | -               | -      | -           | -          | -       | -          |
| loan-product.delete     | V          | -             | -               | -      | -           | -          | -       | -          |

### Modul Pengajuan Kredit (Loan Application)

| Hak Akses                | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| -------------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| loan-application.view      | V          | V             | -               | -      | V           | -          | -       | -          |
| loan-application.create    | V          | V             | -               | -      | V           | -          | -       | -          |
| loan-application.update    | V          | V             | -               | -      | V           | -          | -       | -          |
| loan-application.delete    | V          | V             | -               | -      | -           | -          | -       | -          |

### Modul Rekening Kredit (Loan Account)

| Hak Akses             | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ----------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| loan-account.view       | V          | V             | -               | -      | V           | -          | R       | -          |
| loan-account.payment    | V          | V             | -               | -      | V           | -          | -       | -          |

### Modul Teller

| Hak Akses            | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ---------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| teller.open-session    | V          | -             | -               | V      | -           | -          | -       | -          |
| teller.close-session   | V          | -             | -               | V      | -           | -          | -       | -          |
| teller.deposit         | V          | -             | -               | V      | -           | -          | -       | -          |
| teller.withdraw        | V          | -             | -               | V      | -           | -          | -       | -          |
| teller.authorize       | V          | V             | -               | -      | -           | -          | -       | -          |

### Modul Akuntansi

| Hak Akses                 | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| --------------------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| chart-of-account.view       | V          | -             | -               | -      | -           | V          | R       | -          |
| chart-of-account.create     | V          | -             | -               | -      | -           | V          | -       | -          |
| chart-of-account.update     | V          | -             | -               | -      | -           | V          | -       | -          |
| journal.view                | V          | -             | -               | -      | -           | V          | R       | -          |
| journal.create              | V          | -             | -               | -      | -           | V          | -       | -          |
| journal.approve             | V          | -             | -               | -      | -           | V          | -       | -          |
| journal.reverse             | V          | -             | -               | -      | -           | V          | -       | -          |

### Modul End of Day (EOD)

| Hak Akses       | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ----------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| eod.view          | V          | -             | -               | -      | -           | V          | -       | -          |
| eod.execute       | V          | -             | -               | -      | -           | V          | -       | -          |

### Modul Laporan (Report)

| Hak Akses       | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| ----------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| report.view       | V          | V             | -               | -      | -           | V          | R       | R          |
| report.export     | V          | V             | -               | -      | -           | V          | V       | V          |

### Modul Audit

| Hak Akses    | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
| -------------- | :--------: | :-----------: | :-------------: | :----: | :---------: | :--------: | :-----: | :--------: |
| audit.view     | V          | -             | -               | -      | -           | -          | V       | V          |

---

## Ringkasan Akses per Peran

!!! tip "Prinsip Least Privilege"
    Setiap peran dirancang berdasarkan prinsip _least privilege_ -- pengguna hanya diberikan hak akses minimum yang diperlukan untuk menjalankan tugasnya. Hal ini penting untuk menjaga keamanan dan integritas data perbankan.

| Peran             | Cakupan Utama                                                              |
| ----------------- | -------------------------------------------------------------------------- |
| **SuperAdmin**    | Akses penuh tanpa batasan ke seluruh modul dan fitur sistem                |
| **BranchManager** | Pengelolaan cabang: nasabah, rekening, kredit, laporan, otorisasi teller   |
| **CustomerService** | Layanan nasabah: pendaftaran, pembukaan rekening tabungan dan deposito   |
| **Teller**        | Transaksi loket: buka/tutup sesi, setor tunai, tarik tunai                |
| **LoanOfficer**   | Pembiayaan: pengajuan kredit, monitoring, pembayaran angsuran              |
| **Accounting**    | Akuntansi: jurnal, CoA, EOD, pelaporan keuangan                           |
| **Auditor**       | Audit: akses baca ke seluruh data, audit trail, ekspor laporan             |
| **Compliance**    | Kepatuhan: pemantauan nasabah, audit trail, ekspor laporan regulasi        |

!!! warning "Pengelolaan Peran"
    Penambahan atau perubahan peran pengguna hanya dapat dilakukan oleh **SuperAdmin** melalui menu **Administrasi > Manajemen Pengguna**. Pastikan untuk mendokumentasikan setiap perubahan hak akses sesuai dengan kebijakan internal bank.
