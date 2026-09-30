# Current Session

## Hasil M2

Pengguna mengotorisasi implementasi seluruh TICKET-011–020. Operasional inti M2 kini berada pada branch `main`: harga dan estimasi, direktori pelanggan, transaksi, pembayaran/DP, status/edit/batal, merge pelanggan, dashboard dan pencarian, cek resi publik, cetak 58 mm dengan QR, serta tautan WhatsApp manual. Seluruh [TICKET-011–020](Ticket-Implemented/index.md) berstatus DONE dan diarsipkan. Uji printer thermal fisik dilewati atas instruksi pengguna; belum ada deploy produksi.

[Audit M2](../audits/m2-verification.md) memetakan AC, uji browser, concurrency, PDF/QR, dan batas integrasi M3–M6. Suite backend lulus 75 tes/671 assertions; [CI remote](https://github.com/cleveradit/ceklaundry/actions/runs/36463821819) lulus seluruh gate pada commit `1385fc9`. Browser M1 dan M2 lulus di Chrome 154 dengan database QA terpisah. QR pada struk didekode kembali ke URL status yang tepat; PDF Chromium satu halaman berukuran 58 × 220 mm dan terbaca setelah dirender. Perangkat printer thermal dan HP fisik belum diuji.

## Hasil M3

Pengguna mengotorisasi seluruh TICKET-021–029. Konfigurasi notifikasi, verifikasi email transaksi publik, email siap/pengingat, WA opsional dengan kuota, pengamanan recipient, pengiriman manual, log/recovery, serta restore hold diimplementasikan pada branch `codex/m3-notifications` ([PR #1](https://github.com/cleveradit/ceklaundry/pull/1)). Lihat [audit M3](../audits/m3-verification.md) untuk setiap AC dan batas bukti. Suite MySQL lokal lulus 105 tes/846 assertion; dua tes tambahan merge WA dan isolasi dua bisnis lulus terarah. [CI remote pada commit handoff](https://github.com/cleveradit/ceklaundry/actions/runs/36577057521) lulus 107 tes/856 assertion beserta Pint, frontend, dokumentasi, dan browser M1–M3.

Adapter WA diverifikasi memakai respons HTTP fake. Tidak ada credential provider nyata, sehingga accepted dari provider dan penerimaan pelanggan belum dibuktikan. Restore hold dan command rekonsiliasi lulus tes serta dry run QA; pemulihan backup fisik dan cutover lintas instance masih perlu staging. Demo suppression/preview menunggu M6.

## Runtime lokal

Lima layanan Compose berjalan pada http://localhost:8088. Kolom notifikasi sudah ada pada skema M1, sehingga M3 tidak memerlukan migrasi baru atau penghapusan volume development. Database uji `ceklaundry_test` terpisah dan destruktif; jangan menjalankan browser seed bersamaan dengan backend suite. Container QA dihentikan dan file kredensial fixture sementara dibersihkan. Akun developer tetap harus dibuat interaktif melalui `docker compose exec app php artisan app:bootstrap-developer`; tidak ada password bawaan.

## Handoff

Seluruh [TICKET-021–029](Ticket-Implemented/index.md) berstatus DONE dan diarsipkan setelah verifikasi lokal serta CI. Pengguna mengotorisasi TICKET-030–036 pada 30 September 2026. Fitur M4 berada pada branch lokal `codex/m4-loyalty-promo`: pengaturan stempel, ledger/kompensasi, penukaran, promo, snapshot transaksi, tampilan publik/struk, dan QA browser telah diimplementasikan. [Audit M4](../audits/m4-verification.md) memetakan bukti serta kasus yang baru diperiksa sebagian. Suite MySQL final lulus 114 tes/963 assertion. Pint, frontend lint/typecheck/build, validator dokumentasi, serta browser M1–M4 lulus. TICKET-030–035 DONE dan diarsipkan; [TICKET-036](TICKET-036-verifikasi-dan-handoff-m4.md) tetap READY untuk CI remote. Push ditolak karena Git Credential Manager host belum terautentikasi ke GitHub. Setelah autentikasi tersedia, push branch, catat URL run dan hasil CI, lalu tutup TICKET-036.

M5 laporan dan M6 demo/PWA masih menunggu tiket. [Pengembangan lokal](../development.md) berisi perintah build, QA, dan perawatan runtime.

## Riwayat M1

TICKET-001–010 selesai dan ada di [arsip](Ticket-Implemented/index.md). [CI M1](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049) lulus dengan 52 tes/306 assertions, perjalanan browser, dan gate kualitas. [Audit M1](../audits/m1-verification.md) memuat bukti dan batasnya.
