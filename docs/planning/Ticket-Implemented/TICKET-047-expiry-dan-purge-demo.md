# Implementation Plan: TICKET-047 (Expiry dan purge demo)

**Ticket:** `TICKET-047`

**Status:** `DONE`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-044`, `TICKET-045`, `TICKET-046`

**Tahap:** M6 — US-604 AC 1,3

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M6](../../plan.md#10-m6--demo-dan-pwa), [PRD 9.4](../../initiate-file/prd.md), [US-604](../../initiate-file/user-stories.md), [arsitektur 7](../../initiate-file/architecture.md), [skema 3](../../initiate-file/database-schema.md) |
| Expiry | Tepat `created_at + 7×24 jam`; pada saat itu semua request panel/sesi/status publik demo ditolak meski cron tertunda. |
| Jadwal | Purge tiap menit; pada sistem sehat selesai ≤5 menit setelah expiry, setelah scheduler pulih diproses pada putaran pertama. |
| Keamanan | Hanya `is_demo=true` yang sudah expired dapat dihapus; root lock dan validasi ulang, child sebelum parent, tanpa menonaktifkan FK checks. Dua purge paralel aman dan retry idempoten. |
| Lingkup data | Hapus domain, sesi termasuk switched, reset token, jobs/failed_jobs bertanda business_id server, dan cache tenant; backup mengikuti retensi terpisah. |
| Keterlacakan | FR-M05; US-604 AC 1,3; DAT-02, AND-12, SEC-02, ISO-05. |
| Otorisasi | Pengguna mengotorisasi implementasi seluruh TICKET-044–049 pada 1 Oktober 2026. |

## 2. Objective

Demo kedaluwarsa tepat waktu pada setiap permukaan akses dan seluruh data live-nya dibersihkan oleh scheduler. Purge harus dapat diulang setelah gangguan serta tidak pernah menyentuh tenant nyata.

## 3. Non-Negotiable Technical Contract

1. `app/Services/LifecycleService.php`, `app/Http/Middleware/EnsureBusinessAccess.php`, `app/Services/PublicReceiptService.php`, `app/Http/Controllers/Auth/AuthenticationController.php`: penolakan akses demo expired pada tiap request, bukan menunggu purge; pesan demo berakhir berbahasa Indonesia.
2. `app/Services/DemoPurgeService.php`, `app/Console/Commands/PurgeExpiredDemos.php`, `routes/console.php`: command terjadwal tiap menit, validasi `is_demo`+expiry ulang di business root lock, hapus child sebelum parent dengan domain delete atomik dan infra cleanup idempoten.
3. `database/migrations/2026_09_28_000001_create_m1_foundation.php`, `database/migrations/2026_09_28_000002_create_m1_supporting_tables.php`: ikuti FK `RESTRICT` yang ada; jangan menonaktifkan FK checks atau mengubah data tenant nyata menjadi cascade delete.
4. `app/Jobs/SendNotification.php`, `app/Jobs/SendPasswordReset.php`, `app/Services/NotificationRecoveryService.php`: payload/job demo yang tertinggal setelah expiry tidak dapat mengirim atau menciptakan ulang data; metadata business_id top-level dipakai hanya bila ditulis server dan cocok.
5. `tests/Integration/DemoPurgeTest.php`, `tests/Integration/DemoPurgeConcurrencyTest.php`: verifikasi child/infra cleanup, scheduler outage, dua koneksi MySQL, dan tenant nyata tetap utuh.

## 4. Scope of Changes

1. Pastikan status expiry dievaluasi pada panel, role switch, receipt publik, dan jalur kerja yang memakai business demo. Sesi lama harus ditolak sebelum pembersihan fisik.
2. Implementasikan purge mengikuti urutan arsitektur 7: log/status/payment/loyalty/item/audit → transaksi → promo_branches → promo → loyalty_settings → services → master_services → customers → users → branches → business_settings → business.
3. Bersihkan sessions, password_reset_tokens, jobs/failed_jobs, dan cache keys tenant yang dapat diidentifikasi aman. Retry tidak menghapus tenant lain dan tidak gagal permanen ketika child sudah hilang.
4. Uji cron mati melewati expiry, pemulihan satu putaran, dua purge bersamaan, serta expiry yang tepat di batas waktu.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Batas expiry | Request tepat sebelum dan pada `created_at+7×24 jam` | Sebelum diizinkan; pada batas ditolak, termasuk status publik dan sesi lama | `[x]` |
| Cron sehat | Demo baru expired | Seluruh live data terhapus ≤5 menit | `[x]` |
| Scheduler pulih | Cron mati melewati expiry lalu hidup | Akses tetap ditolak selama outage; purge pada putaran pertama | `[x]` |
| Dua purge | Dua proses MySQL bersamaan untuk demo sama | Satu hasil bersih, keduanya selesai aman tanpa FK disable | `[x]` |
| Infra | Switched sessions, reset token, queued/failed jobs, cache | Semua jejak live demo yang ditentukan hilang; worker tak mengirim | `[x]` |
| Tenant nyata | Business non-demo dengan data serupa | Tidak ada baris/domain/infra miliknya terhapus | `[x]` |
| Retry | Purge dipanggil lagi setelah sukses atau kegagalan parsial infra | Idempoten, akhirnya bersih | `[x]` |

## 6. Verification Commands

1. `rtk proxy docker compose exec -T app php artisan test --filter=DemoFlowTest`
2. `rtk proxy docker compose exec -T app php artisan test --filter=DemoConcurrencyTest`
3. `rtk proxy docker compose exec -T app php artisan schedule:list`
4. `rtk proxy docker compose exec -T app ./vendor/bin/pint --test`

Expected: penolakan akses tidak bergantung pada cron; pembersihan MySQL memenuhi batas waktu, idempotensi, dan isolasi.

## 7. Out of Scope

1. Menghapus tenant nyata atau mengubah retensi backup produksi.
2. Membuat hard-delete endpoint umum untuk bisnis.

## 8. Completion Checklist

- [x] Otorisasi implementasi diterima; status `DONE` setelah verifikasi lokal/CI.
- [x] Guard expiry, command, dan scheduler diterapkan.
- [x] Semua kasus batas, outage, concurrency, dan isolasi lulus.
- [x] Tidak ada FK checks yang dinonaktifkan atau tenant nyata yang terhapus.

Bukti aktual dan batas pengujian perangkat ada di [audit M6](../../audits/m6-verification.md). Status DONE mencakup implementasi dan gate lokal/CI; pemasangan serta splash Android/iOS fisik tetap belum diverifikasi.
