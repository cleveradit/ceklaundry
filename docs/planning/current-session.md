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

Seluruh [TICKET-021–029](Ticket-Implemented/index.md) berstatus DONE dan diarsipkan setelah verifikasi lokal serta CI. Pengguna mengotorisasi TICKET-030–036 pada 30 September 2026. Fitur M4 sudah di `main`: pengaturan stempel, ledger/kompensasi, penukaran, promo, snapshot transaksi, tampilan publik/struk, dan QA browser. [Audit M4](../audits/m4-verification.md) memetakan bukti serta kasus yang baru diperiksa sebagian. Suite MySQL lokal lulus 114 tes/963 assertion. Pint, frontend lint/typecheck/build, validator dokumentasi, serta browser M1–M4 lulus lokal dan [CI pada commit kode `9d0e433`](https://github.com/cleveradit/ceklaundry/actions/runs/36725111623) lulus seluruh gate. TICKET-030–036 DONE dan [diarsipkan](Ticket-Implemented/index.md).

Pengguna mengotorisasi implementasi seluruh TICKET-037–043 pada 1 Oktober 2026. M5 diimplementasikan pada `main` ([PR #2](https://github.com/cleveradit/ceklaundry/pull/2)): riwayat/filter, pendapatan berbasis payment, tagihan, lima kartu dashboard, grafik harian/bulanan, CSV, dan snapshot baca khusus MySQL. [Audit M5](../audits/m5-verification.md) memetakan 13 AC dan empat kriteria plan. Suite lokal lulus 126 tes/1095 assertion; Pint 207 file dan frontend lulus. Browser M1–M5 lulus, termasuk viewport HP 390 × 844. Dataset terverifikasi 200 bisnis/50.000 transaksi/99.994 payment menghasilkan P95 dashboard lokal 1796 ms dan CI 1716 ms pada profil throttling. [CI commit kode akhir `691737e`](https://github.com/cleveradit/ceklaundry/actions/runs/36869810998) lulus seluruh gate.

TICKET-037–043 berstatus DONE dan [diarsipkan](Ticket-Implemented/index.md). M5 sudah di `main`; belum deploy produksi. QA dihentikan setelah verifikasi dan file credential fixture sementara dibersihkan; runtime development tetap tersedia di port 8088. M6 demo/PWA masih menunggu tiket, mulai nomor TICKET-044. [Pengembangan lokal](../development.md) berisi perintah build, QA, dan perawatan runtime.

## Riwayat M1

TICKET-001–010 selesai dan ada di [arsip](Ticket-Implemented/index.md). [CI M1](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049) lulus dengan 52 tes/306 assertions, perjalanan browser, dan gate kualitas. [Audit M1](../audits/m1-verification.md) memuat bukti dan batasnya.

## Instruksi Git terbaru

Pada 1 Oktober 2026 pengguna meminta perubahan langsung di `main` dan push ke remote, tanpa membuat branch baru. M5 dipindahkan dengan fast-forward dari `codex/m5-reports`; pekerjaan berikutnya mengikuti instruksi ini.
