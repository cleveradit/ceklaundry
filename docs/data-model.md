# Data Model Reference — CekLaundry

**Status:** rancangan; belum ada migrasi atau database berjalan. Nama tabel, kolom, tipe, indeks, dan aturan integritas lengkap ada di [database-schema.md](initiate-file/database-schema.md). Setelah migrasi dibuat, verifikasi ringkasan ini terhadap kode.

## Penyimpanan data

Rancangan memakai MySQL 8.4/InnoDB dengan `utf8mb4`. Data operasional dipisahkan oleh bisnis; transaksi berada pada cabang, sedangkan pelanggan dan layanan master berada pada bisnis. Queue dan sesi memakai tabel Laravel.

## Kelompok tabel yang direncanakan

| Kelompok | Tabel | Peran |
|---|---|---|
| Tenant dan akses | `businesses`, `business_settings`, `users`, `branches` | Bisnis, masa aktif, peran, cabang, pengaturan |
| Layanan dan pelanggan | `master_services`, `services`, `customers` | Katalog master/cabang dan pelanggan per bisnis |
| Operasional | `transactions`, `transaction_items`, `payments`, `status_histories` | Cucian, item snapshot, pembayaran, riwayat status |
| Promosi dan loyalti | `promos`, `promo_branches`, `loyalty_settings`, `loyalty_histories` | Potongan dan stempel |
| Komunikasi dan audit | `notification_logs`, `audit_logs` | Hasil pengiriman dan aksi berisiko |
| Infrastruktur | `jobs`, `failed_jobs`, `job_batches`, `password_reset_tokens`, `sessions`, `cache` | Fasilitas Laravel |

## Aturan data utama

- `business_id` membatasi seluruh data operasional; admin juga dibatasi `branch_id`. `kode_resi` unik global untuk jalur publik.
- Harga layanan dan promo disalin sebagai snapshot pada transaksi/item agar transaksi lama tidak berubah ketika katalog berubah.
- `payments`, `status_histories`, `loyalty_histories`, dan `audit_logs` bersifat tambah-saja selama bisnis ada, kecuali pembersihan tenant demo sesuai [NFR](initiate-file/nfr.md).
- `status_bayar` diturunkan dari pembayaran; jumlah kumulatif tidak boleh melebihi `total_akhir`. Aturan yang tidak dapat menjadi constraint SQL dijaga service.
