# Data Model Reference — CekLaundry

**Status:** migrasi M1, M2 dan M4 berjalan pada MySQL8.4/InnoDB/utf8mb4_0900_as_ci; M3 dan M5 memakai tabel tersebut tanpa migrasi baru. Skema lengkap target ada di [database-schema.md](initiate-file/database-schema.md); batas bukti terbaru ada di [audit M5](audits/m5-verification.md).

| Kelompok | Tabel aktual | Cakupan |
|---|---|---|
| Tenant/akses | businesses, business_settings, users, branches | M1, lifecycle dan satu owner per bisnis melalui generated unique key |
| Katalog | master_services, services | M1, unique nama per bisnis/cabang, domain angka/check dan composite FK |
| Pengaturan hadiah | loyalty_settings | Default false/10/null/null, pengelolaan program dan guard hadiah M4 |
| Operasional M2 | customers, transactions, transaction_items, payments, status_histories, loyalty_histories | Identitas, snapshot layanan, pembayaran append-only, jejak status dan kompensasi ledger |
| Notifikasi M3 | notification_logs | Recipient snapshot, claim/delivery, kuota WA, retry/recovery dan restore hold |
| Promo M4 | promos, promo_branches | Eligibility cabang/tanggal/minimum, snapshot promo pada transaksi |
| Audit | audit_logs | Append-only model, safe JSON, tanpa updated_at |
| Infrastruktur | sessions, password_reset_tokens, jobs, job_batches, failed_jobs, cache, cache_locks | Database session/queue/cache; payload auth terenkripsi |

Migrasi `2026_09_28_000001_create_m1_foundation.php` dan `...000002_create_m1_supporting_tables.php` melengkapi migrasi infrastruktur Laravel. Migrasi `...000003_create_m2_payment_histories.php` menambah `payments`, `status_histories`, dan `loyalty_histories` dengan FK/unique/check. Migrasi `2026_09_30_000001_create_m4_promo_branches.php` menambah pivot `promo_branches`; field snapshot transaksi memakai skema fondasi.

Data tenant memakai business_id; admin wajib branch_id dan peran memiliki CHECK. FK komposit mencegah parent/cabang/pelanggan lintas bisnis. Harga dan durasi bilangan bulat positif, minimum kg maksimal satu desimal; minimum item harus null. Nama dinormalisasi whitespace oleh service, case-insensitive dan accent-sensitive oleh kolasi DB.

Credential bisnis memakai cast encrypted dan hidden; .env/APP_KEY tidak masuk repo. Password bcrypt dibatasi72 byte sebelum hash. Tidak ada remember_token. Token reset di-hash; token plaintext hanya ada pada payload job terenkripsi dan tautan yang dikirim.

Snapshot transaksi tidak berubah akibat edit master/lokal/sync/promo. Kode resi unik global enam karakter; request key create dan payment unik per bisnis untuk replay aman. Versi transaksi mencegah edit/status stale; ledger pembayaran tidak diubah/dihapus saat batal. Histori status ditambah setiap perpindahan, kecuali no-op status sama. Saldo stempel berasal dari penjumlahan ledger earning/redemption/kompensasi M4; cache pelanggan disinkronkan dan saldo negatif tetap ditampilkan.

Laporan M5 tidak menyimpan tabel agregat baru. Pendapatan derived dari `payments.waktu/jumlah` untuk transaksi nonbatal; sisa tagihan derived dari total akhir dikurangi SUM payment pada transaksi aktif. Grafik dan angka satu respons memakai snapshot MySQL konsisten. Riwayat/CSV memakai waktu masuk dan identitas pelanggan terkini; kg aktual terpisah dari berat minimum penagihan.

Statistik developer hanya COUNT cabang dan transaksi rentang30×24jam termasuk batal/nonaktif; tidak membawa ID/baris operasional. FK RESTRICT mempertahankan histori. Demo cleanup menyeluruh belum diimplementasikan.
