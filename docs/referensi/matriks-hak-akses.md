# Matriks Hak Akses

Halaman ini berisi matriks lengkap hak akses untuk setiap peran di aplikasi Core Banking BPR.

## Legenda

| Simbol | Keterangan |
|--------|------------|
| V | Akses penuh (semua aksi) |
| R | Hanya baca (view) |
| P | Sebagian (partial - lihat detail di bawah) |
| - | Tidak ada akses |

## Matriks per Modul

### Cabang (Branch)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | - | - | - |
| create | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |
| delete | V | - | - | - | - | - | - | - |

### Pengguna (User)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | - | - | - |
| create | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |
| delete | V | - | - | - | - | - | - | - |
| assign-role | V | - | - | - | - | - | - | - |

### Nasabah (Customer)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | V | V | V | V | - | V | V |
| create | V | V | V | - | - | - | - | - |
| update | V | V | V | - | - | - | - | - |
| delete | V | V | - | - | - | - | - | - |
| approve | V | V | - | - | - | - | - | - |
| block | V | V | - | - | - | - | - | - |

### Produk Tabungan (Savings Product)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | V | - | - | - | - | - |
| create | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |
| delete | V | - | - | - | - | - | - | - |

### Rekening Tabungan (Savings Account)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | V | V | V | - | - | V | - |
| create | V | V | V | - | - | - | - | - |
| deposit | V | V | - | V | - | - | - | - |
| withdraw | V | V | - | V | - | - | - | - |
| close | V | V | - | - | - | - | - | - |
| freeze | V | V | - | - | - | - | - | - |

### Produk Deposito (Deposit Product)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | V | - | - | - | - | - |
| create | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |
| delete | V | - | - | - | - | - | - | - |

### Rekening Deposito (Deposit Account)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | V | V | - | - | - | V | - |
| create | V | V | V | - | - | - | - | - |
| close | V | V | - | - | - | - | - | - |
| rollover | V | V | - | - | - | - | - | - |

### Produk Kredit (Loan Product)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | V | - | - | - |
| create | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |
| delete | V | - | - | - | - | - | - | - |

### Permohonan Kredit (Loan Application)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | V | - | - | V | - | - | - |
| create | V | V | - | - | V | - | - | - |
| update | V | V | - | - | V | - | - | - |
| approve | V | V | - | - | - | - | - | - |
| reject | V | V | - | - | - | - | - | - |
| disburse | V | V | - | - | - | - | - | - |

### Rekening Kredit (Loan Account)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | V | - | V | - |
| payment | V | - | - | - | V | - | - | - |
| restructure | V | - | - | - | - | - | - | - |
| write-off | V | - | - | - | - | - | - | - |

### Teller

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| open-session | V | - | - | V | - | - | - | - |
| close-session | V | - | - | V | - | - | - | - |
| deposit | V | - | - | V | - | - | - | - |
| withdraw | V | - | - | V | - | - | - | - |
| authorize | V | V | - | - | - | - | - | - |

### Vault / Brankas

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | - | - | - |
| request-cash | V | - | - | - | - | - | - | - |
| return-cash | V | - | - | - | - | - | - | - |

### Bagan Akun (Chart of Account)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | V | V | - |
| create | V | - | - | - | - | V | - | - |
| update | V | - | - | - | - | V | - | - |
| delete | V | - | - | - | - | - | - | - |

### Jurnal (Journal Entry)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | V | V | - |
| create | V | - | - | - | - | V | - | - |
| approve | V | - | - | - | - | V | - | - |
| reverse | V | - | - | - | - | V | - | - |

### Laporan (Report)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | V | - | - | - | V | V | V |
| export | V | V | - | - | - | V | V | V |

### End of Day (EOD)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | V | - | - |
| execute | V | - | - | - | - | V | - | - |

### Parameter Sistem (System Parameter)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |

### Hari Libur (Holiday)

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | - | - | - |
| create | V | - | - | - | - | - | - | - |
| update | V | - | - | - | - | - | - | - |
| delete | V | - | - | - | - | - | - | - |

### Audit

| Aksi | SuperAdmin | BranchManager | CustomerService | Teller | LoanOfficer | Accounting | Auditor | Compliance |
|------|-----------|---------------|-----------------|--------|-------------|------------|---------|------------|
| view | V | - | - | - | - | - | V | V |

!!! info "Catatan"
    - **SuperAdmin** secara otomatis memiliki semua permission tanpa terkecuali
    - Hak akses dapat dikustomisasi oleh SuperAdmin melalui [Manajemen Pengguna](../administrasi/manajemen-pengguna.md)
    - Filter cabang berlaku otomatis: pengguna non-SuperAdmin/Auditor/Compliance/BranchManager hanya melihat data cabangnya sendiri
