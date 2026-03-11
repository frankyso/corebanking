# Glosarium

Daftar istilah dan singkatan yang digunakan dalam aplikasi Core Banking BPR.

## Istilah Umum Perbankan

| Istilah | Deskripsi |
|---------|-----------|
| **BPR** | Bank Perkreditan Rakyat - lembaga keuangan yang melayani simpanan dan kredit untuk masyarakat |
| **CIF** | Customer Information File - nomor identitas unik nasabah dalam sistem perbankan |
| **DPK** | Dana Pihak Ketiga - dana yang dihimpun dari masyarakat (tabungan, deposito) |
| **OJK** | Otoritas Jasa Keuangan - lembaga pengawas sektor keuangan Indonesia |
| **SOP** | Standard Operating Procedure - prosedur operasional standar |

## Istilah Kredit

| Istilah | Deskripsi |
|---------|-----------|
| **Plafon** | Batas maksimum fasilitas kredit yang disetujui |
| **Baki Debet** | Sisa pokok pinjaman yang belum dibayar (outstanding principal) |
| **DPD** | Days Past Due - jumlah hari keterlambatan pembayaran angsuran |
| **NPL** | Non-Performing Loan - kredit bermasalah (kolektibilitas 3, 4, dan 5) |
| **NPL Ratio** | Rasio kredit bermasalah terhadap total kredit outstanding |
| **CKPN** | Cadangan Kerugian Penurunan Nilai - penyisihan untuk antisipasi kerugian kredit |
| **Kolektibilitas** | Tingkat kualitas kredit berdasarkan ketepatan pembayaran (Kol 1-5) |
| **Agunan** | Jaminan yang diserahkan debitur sebagai pengaman kredit (collateral) |
| **Tenor** | Jangka waktu kredit dalam bulan |
| **Angsuran** | Pembayaran berkala untuk melunasi kredit (pokok + bunga) |
| **Write-off** | Penghapusbukuan kredit macet dari neraca |
| **Restrukturisasi** | Perubahan ketentuan kredit untuk membantu debitur yang kesulitan |
| **Bunga Flat** | Perhitungan bunga berdasarkan pokok awal selama masa kredit |
| **Bunga Efektif** | Perhitungan bunga berdasarkan sisa pokok (annuity) |
| **Bunga Sliding** | Perhitungan bunga berdasarkan sisa pokok dengan angsuran pokok tetap |
| **LTV** | Loan to Value - rasio kredit terhadap nilai agunan |
| **Pencairan** | Proses penyaluran dana kredit ke rekening debitur (disbursement) |

## Kolektibilitas Kredit

| Kol | Nama | DPD | CKPN Rate |
|-----|------|-----|-----------|
| 1 | Lancar (Current) | 0 hari | 1% |
| 2 | Dalam Perhatian Khusus (Special Mention) | 1-90 hari | 5% |
| 3 | Kurang Lancar (Substandard) | 91-120 hari | 15% |
| 4 | Diragukan (Doubtful) | 121-180 hari | 50% |
| 5 | Macet (Loss) | > 180 hari | 100% |

## Istilah Simpanan

| Istilah | Deskripsi |
|---------|-----------|
| **Tabungan** | Simpanan yang dapat ditarik sewaktu-waktu |
| **Deposito** | Simpanan berjangka dengan tenor dan suku bunga tetap |
| **Setoran** | Penyetoran uang tunai ke rekening tabungan (deposit) |
| **Penarikan** | Pengambilan uang tunai dari rekening tabungan (withdrawal) |
| **Dormant** | Status rekening tidak aktif karena tidak ada transaksi dalam periode tertentu |
| **Hold Amount** | Saldo yang diblokir/ditahan sehingga tidak dapat ditarik |
| **Saldo Tersedia** | Saldo total dikurangi hold amount (available balance) |
| **Rollover** | Perpanjangan otomatis deposito saat jatuh tempo |
| **ARO** | Automatic Roll Over - perpanjangan otomatis pokok deposito |
| **ARO + Bunga** | Perpanjangan otomatis pokok + bunga deposito |
| **Kapitalisasi** | Bunga deposito ditambahkan ke pokok |

## Istilah Akuntansi

| Istilah | Deskripsi |
|---------|-----------|
| **CoA** | Chart of Account - bagan akun / daftar perkiraan akuntansi |
| **GL** | General Ledger - buku besar akuntansi |
| **Jurnal** | Catatan transaksi keuangan dengan entri debit dan kredit |
| **Neraca** | Laporan posisi keuangan (aset = liabilitas + ekuitas) |
| **Neraca Saldo** | Daftar saldo semua akun pada periode tertentu (trial balance) |
| **Laba Rugi** | Laporan kinerja keuangan (pendapatan - beban = laba/rugi) |
| **Debit** | Sisi kiri pencatatan jurnal (menambah aset/beban) |
| **Kredit** | Sisi kanan pencatatan jurnal (menambah liabilitas/ekuitas/pendapatan) |
| **Posting** | Proses finalisasi jurnal sehingga mempengaruhi saldo GL |
| **Reversal** | Pembatalan jurnal yang sudah diposting |
| **Accrual** | Pengakuan pendapatan/beban yang masih harus diterima/dibayar |

## Istilah Operasional

| Istilah | Deskripsi |
|---------|-----------|
| **EOD** | End of Day - proses akhir hari yang menjalankan batch processing |
| **Sesi Teller** | Periode kerja teller dari buka sampai tutup kas |
| **Kas Awal** | Saldo awal kas saat teller membuka sesi |
| **Vault / Brankas** | Tempat penyimpanan uang tunai cabang |
| **Otorisasi** | Persetujuan transaksi oleh pejabat berwenang |

## Istilah Sistem

| Istilah | Deskripsi |
|---------|-----------|
| **RBAC** | Role-Based Access Control - pengaturan akses berdasarkan peran |
| **SPA** | Single Page Application - aplikasi yang berjalan di satu halaman tanpa reload |
| **Audit Trail** | Catatan perubahan data yang melacak siapa, kapan, dan apa yang diubah |
