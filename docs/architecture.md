# Architecture Map — CekLaundry

**Status:** fondasi M1 dan operasional inti M2 diimplementasikan; lihat [verifikasi M1](audits/m1-verification.md) dan [verifikasi M2](audits/m2-verification.md). Spesifikasi keseluruhan tetap [arsitektur sumber](initiate-file/architecture.md). M3–M6 masih rancangan.

Monolit Laravel12, PHP8.4 dan MySQL8.4/InnoDB. Panel Inertia/React/TypeScript strict memakai Vite tanpa SSR; halaman depan Blade dengan CSS terpisah tidak mengunduh React.

| Komponen aktual | Tanggung jawab |
|---|---|
| `routes/auth.php`, `AuthenticationController`, `AccountService` | Login, wajib ganti password, logout/reset, revocation sesi/token |
| `ResolveTenant`, `EnsureBusinessAccess`, `RequireRole`, policies | Identitas terbaru setiap request; tenant/cabang/peran dan lifecycle |
| `TenantContext`, model concerns | Scope fail closed; admin satu cabang; job membersihkan context dalam finally |
| `BusinessTransaction` | Root business lock lalu baca ulang actor/lifecycle, READ COMMITTED, retry unit maksimal3 |
| `TenantProvisioner`, `DeveloperBusinessSummary`, `LifecycleService` | Provision atomik, DTO agregat developer, kalender WIB dan invalidasi pending |
| `BranchService`, `AccountService`, `ServiceCatalogService` | Cabang/admin/master/lokal dan guard invariannya |
| `MasterSyncService` | Snapshot/fingerprint server, preview tanpa long transaction, apply semua cabang + audit atomik |
| `PricingService`, `EstimationService`, `TransactionService` | Hitung harga integer di server; snapshot item, create idempoten, edit dengan version dan batas finansial |
| `CustomerService`, `CustomerMergeService` | Identitas pelanggan bersama per bisnis; merge di bawah root lock, repoint transaksi dan ledger |
| `PaymentService`, `TransactionStateMachine`, `CancellationService` | Ledger pembayaran append-only, DP, status satu langkah, batal dan kompensasi ledger |
| `OperationsDashboardService`, controller App | Ringkasan cabang, pencarian, transaksi, pelanggan dan pengaturan DP owner |
| `PublicReceiptService`, `ReceiptRateLimiter`, `ReceiptPrintService` | DTO publik tersamar, limiter bersama 30/IP/menit, resi thermal dan QR SVG lokal |
| `ManualReceiptLinkService` | Tautan wa.me manual; tidak melakukan pengiriman server atau menulis log M2 |
| `SendPasswordReset`, `OutboundGuard` | Job terenkripsi, token+queue satu transaksi, SMTP global sekali, hold/cutoff |
| `resources/js/Pages/Management.tsx`, `Owner/Sync.tsx` | Form reusable per resource, daftar, konfirmasi dan pratinjau |
| `NoStore`, konfigurasi Inertia | No-store/no-referrer, history terenkripsi, clear history setelah logout |
| Docker app/web/db/worker/cron | Runtime lokal dan CI; panduan [development](development.md) |

Controller memakai validasi request sederhana langsung dan meneruskan validasi domain ke service; form frontend katalog/cabang/admin memakai komponen bersama. Tidak diperlukan kelas Request/Page kosong per variasi resource. Ini pilihan organisasi kode; kontrak validasi/otorisasi tiket tetap ditegakkan server.

Developer tidak mempunyai bypass policy operasional. Bisnis root dan User tidak memakai scope implisit, sehingga setiap akses administratif dibatasi eksplisit. Model operasional wajib context. Composite FK menjaga parent satu bisnis. Tidak ada network call di dalam root lock. Keamanan akun memakai root business/user lock tetapi tidak memerlukan hak tulis bisnis; BACA_SAJA tetap boleh ganti/reset.

Business write: autentikasi → identitas terkini → root lock → cek ulang akun/cabang/lifecycle → validasi domain → write child/audit → commit. HTTP lintas tenant404, salah peran403, business-write baca-saja423, preview stale409.

Alur M2 memakai BusinessTransaction dan root lock yang sama dengan M1. Semua mutasi harga, pelanggan, transaksi, pembayaran dan status memeriksa aktor/cabang terbaru lalu menulis secara atomik. Pembacaan publik mencari kode resi global, membatasi per IP sebelum lookup, dan menyusun DTO tersamar terpisah dari cetak panel.

Belum diimplementasikan: notifikasi pelanggan otomatis dan pencatatan WA manual M3, loyalti/promo aktif M4, laporan M5, provisioning demo dan PWA M6.
