# Verifikasi operasional inti M2

Tanggal: 29 September 2026. Lingkup TICKET-011–020, tanpa deploy produksi. Rujukan spesifikasi: [user stories](../initiate-file/user-stories.md), [plan M2](../plan.md#6-m2--operasional-inti), dan [audit M1](m1-verification.md).

## Lingkungan dan bukti

Backend berjalan di PHP8.4/MySQL8.4 InnoDB melalui Compose. Tes memakai `ceklaundry_test` yang terpisah dari database development. Concurrency memakai proses PHP dan koneksi MySQL terpisah, barrier, serta row lock root bisnis nyata. Suite backend, Pint, ESLint, TypeScript, Vite, validator spesifikasi dan `git diff --check` menjadi gate lokal; hasil final dicatat pada penutupan audit.

Playwright memakai Chrome154 di Windows, desktop1440×1000 untuk perjalanan M1 dan viewport390×844 untuk perjalanan M2. Runner M1 dan M2 memakai seed yang me-reset database QA secara berurutan. Browser M2 menyelesaikan transaksi Rp74.500, DP Rp30.000, pelunasan Rp44.500, pickup, pencarian customer, cek publik tanpa JavaScript, dan print. Screenshot mobile tidak overflow horizontal; assertion posisi memastikan angka kartu tidak menimpa label. Resi panel dan publik berbeda DTO; nama/nomor pelanggan publik tersamar.

PNG QR yang dirender browser dibaca dengan `jsqr` dan menghasilkan URL status tepat. PDF hasil dialog print Chromium berukuran **163,92 × 624 pt** (58 × 220 mm), satu halaman, dengan isi terbaca setelah dirender ulang. Cetak ke perangkat thermal fisik dan pengiriman WhatsApp/provider tidak diuji pada lingkungan ini. `APP_URL` QA memang `http://127.0.0.1:8089`; produksi harus memakai URL HTTPS terkonfigurasi agar QR dapat dipakai pelanggan.

## Matriks AC M2

Setiap rentang AC mencakup seluruh nomor di rentang tersebut. Nama tes berada di `tests/Feature/Operations/`, `tests/Feature/Public/`, `tests/Integration/`, dan `tests/browser/`. “Tertunda” berarti bagian lintas milestone belum tersedia, bukan dianggap lulus M2.

| AC | Bukti aktual | Status dan batas |
|---|---|---|
| US-201 AC1–4 | RuntimeSmokeTest, ReceiptLookupTest, browser/m2.cjs | Lulus M2: formulir Blade/no-JS, redirect, pesan umum, tombol demo nonaktif. Provision demo diuji M6. |
| US-202 AC1–3 | M2JourneyTest, ReceiptLookupTest, ReceiptLimiterTest, browser/m2.cjs | Lulus M2: DTO status, masking dan limiter 30/IP/menit. |
| US-202 AC4 | ReceiptLimiterTest, ReceiptLookupTest | Lulus untuk form, direct URL dan cetak publik; endpoint email/verify menunggu M3 dan harus memakai limiter sama. |
| US-202 AC5 | ReceiptLookupTest, browser/m2.cjs, browser/m1.cjs | Lulus payload/header/no-JS dan logout/back M1; uji ulang email/verify M3. |
| US-203 AC1–4 | CustomerDirectoryTest, PricingQuoteTest, TransactionCreateTest, M2JourneyTest, browser/m2.cjs | Lulus input pelanggan baru/lama, harga snapshot dan minimum, kondisi publik. |
| US-203 AC5–8 | TransactionCreateTest, PricingQuoteTest, ReceiptLookupTest | Lulus resi CSPRNG/unique, petunjuk dua resi, snapshot email dan validasi kuantitas/overflow. |
| US-203 AC9–10 | TransactionCreateTest, TransactionCreateConcurrencyTest, browser/m2.cjs | Lulus replay/rollback, branch race dan 409 dengan quote terbaru untuk request JSON. |
| US-204 AC1–3 | PricingQuoteTest, TransactionLifecycleTest, M2JourneyTest | Lulus estimasi maksimum durasi dan edit sah; route menolak edit sejak DIPROSES. |
| US-205 AC1–5 | TransactionLifecycleTest, TransactionTransitionMatrixTest, TransactionLifecycleConcurrencyTest | Lulus riwayat/timestamp, seluruh 25 pasangan status, no-op, alasan batal dan konflik versi. |
| US-206 AC1–9 | M2JourneyTest, PaymentTest, PaymentConcurrencyTest, TransactionCreateTest | Lulus DP/pelunasan, overpay, saklar saat pembayaran, replay, terminal, Rp0 dan dua pembayaran beradu. |
| US-206 AC10–15 | PaymentTest, PaymentConcurrencyTest, TransactionLifecycleTest | Lulus serialisasi pembayaran dan toggle, initial payment atomik/replay; eksklusi pendapatan laporan M5 dan pemberian loyalti M4 perlu uji ulang. |
| US-207 AC1–2 | M2JourneyTest, TransactionTransitionMatrixTest, browser/m2.cjs | Lulus pickup ditolak sebelum LUNAS, lalu timestamp diambil terisi. |
| US-208 AC1–5 | TransactionLifecycleTest, TransactionCreateTest, TransactionTransitionMatrixTest | Lulus kunci finansial/nonfinansial dan terminal; pembaruan email transaksi FR-C04 menunggu M3. |
| US-208 AC6–10 | TransactionLifecycleTest, CustomerMergeTest, TransactionTransitionMatrixTest | Batal beralasan, payment historis dan kompensasi ledger fixture lulus; laporan M5 serta program loyalti M4 perlu uji E2E. |
| US-209 AC1–4 | ReceiptLookupTest, ReceiptPrintTest, browser/m2.cjs, PDF QA | Lulus DTO cetak, QR yang didekode, lebar58mm dan masking. Uji printer fisik dilewati atas instruksi pengguna; tidak ada klaim hasil perangkat. |
| US-210 AC1 | ManualReceiptLinkTest, browser/m2.cjs | Lulus tautan `wa.me` berisi kode, total/sisa, estimasi dan status; pembukaan tidak membuktikan pesan terkirim. |
| US-210 AC2 | ManualReceiptLinkTest, ReceiptPrintTest | Baca-saja tanpa write lulus; log `dibuka_manual` M3 dan `ditekan_demo` M6 tertunda. |
| US-211 AC1–3 | OperationsDashboardTest, browser/m2.cjs | Lulus agregat cabang, kg aktual, antrean, terlambat, daftar semua status dan tampilan mobile. |
| US-212 AC1–2 | OperationsDashboardTest, OwnerOperationsTest | Lulus pencarian bound kode/nama/nomor dalam cakupan admin/owner. |
| US-213 AC1–4 | CustomerDirectoryTest, CustomerMergeTest, browser/m2.cjs | Lulus identitas canonical/unique dan snapshot email transaksi. |
| US-213 AC5–9 | — | Tertunda M3: claim, marker provider dan retarget/review log WA belum tersedia. |
| US-214 AC1–5 | CustomerMergeTest, CustomerMergeConcurrencyTest | Lulus merge, audit, pembatasan admin dan dua merge beradu; race dengan semua writer M4/M3 perlu uji ulang saat writer tersebut ada. |
| US-214 AC6–9 | — | Tertunda M3: log/claim WA pending dan in-flight belum tersedia. |
| US-215 AC1–2 | OwnerOperationsTest, M2JourneyTest, CustomerMergeTest | Lulus owner memakai route operasi yang sama lintas cabang dan tercatat sebagai pembuat. |
| US-215 AC3 | — | Tertunda M3/M4 untuk notifikasi, promo dan penukaran. |

## Integrasi M1 dan batas lanjutan

Guard cabang nonaktif diuji ulang dengan writer `TransactionService` nyata pada TransactionCreateConcurrencyTest. Harga/item snapshot tidak berubah akibat edit master/sync; riwayat pembayaran tidak hilang saat pembatalan. Customer merge, pencarian, dashboard, public DTO dan print tidak membuka ID transaksi lintas bisnis/cabang. Developer hanya mendapat agregat sesuai M1.

M3 wajib menambahkan notifikasi otomatis, verifikasi email, limiter publik yang sama, log WA manual dan penyesuaian nomor pada customer edit/merge. M4 wajib menguji pemberian/penukaran stempel serta kompensasi batal dengan alur nyata, dan promo. M5 wajib memastikan transaksi batal serta pembayarannya dikecualikan dari pendapatan. M6 wajib menguji demo, PWA dan perilaku WA demo. Uji printer 58 mm fisik tetap perlu dilakukan pada perangkat operator sebelum mengandalkan hasil cetak produksi.

## Penutupan gate

Suite backend penuh lulus **75 tes / 671 assertions** tanpa warning pada PHP8.4/MySQL8.4, termasuk proses concurrency yang memakai koneksi MySQL terpisah. Pint lulus untuk 152 file; ESLint, TypeScript, Vite build dan quality-gate-probe lulus. Validator spesifikasi lulus (72 FR tertelusur, tanpa error) dan `git diff --check` bersih. Perjalanan browser M1 serta M2 lulus di Chrome154.

[GitHub Actions run 36463821819](https://github.com/cleveradit/ceklaundry/actions/runs/36463821819) lulus pada commit `1385fc9`: build, migrasi, Pint, backend/MySQL, frontend, kontrak dokumentasi, browser M1/M2 dan upload screenshot/PDF. Pada 29 September 2026 pengguna memutuskan untuk melewati uji printer thermal fisik. Keputusan ini menutup gate TICKET-020 tanpa mengubah fakta bahwa keluaran perangkat belum terverifikasi; belum ada deploy produksi.
