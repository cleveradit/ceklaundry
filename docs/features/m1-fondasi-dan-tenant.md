# M1 — Fondasi dan tenant

**Status:** Live

Autentikasi developer/owner/admin, bootstrap tanpa password bawaan, wajib ganti password, pengelolaan bisnis dan masa aktif, cabang/admin, katalog master/lokal, serta pratinjau/sinkronisasi atomik tersedia. Live berarti implementasi lokal yang diuji, bukan deploy produksi.

| Peran | Kemampuan |
|---|---|
| Developer | Membuat bisnis/owner/default settings, melihat angka agregat, masa aktif/nonaktif/aktif, reset owner dengan audit |
| Owner | Lima menu: ringkasan, cabang, admin, master, sebar layanan; edit lokal melalui cabang; reset admin |
| Admin | Membaca identitas dan katalog cabang terkini, keamanan akun; operasional transaksi belum tersedia |

Masa aktif mengikuti kalender WIB: peringatan mulai H−7, tanggal habis masih aktif, tenggang tujuh hari, baca-saja mulai H+8. Nonaktif/deadline demo mengalahkan akses panel. Baca-saja tetap dapat login/membaca/ganti password/reset; server menolak tulis bisnis423. Scanner dan perubahan lifecycle menutup pending lama tanpa mengubah pengiriman yang sudah in-flight.

Sync mencocokkan nama mengikuti kolasi DB, menyalin master aktif, mereaktivasi lokal yang cocok, mempertahankan layanan khusus, dan tidak mengubah snapshot histori. Rename master menambah nama baru. Preview usang409; semua cabang dan audit commit bersama. Tidak ada sync otomatis saat master diubah.

Email reset masuk database queue terenkripsi, berlaku60 menit sekali, memakai SMTP global dan tidak otomatis retry. Email nyata baru berfungsi setelah operator mengisi konfigurasi SMTP. Password sementara dari operator wajib diganti; sesi/token dicabut dan audit tidak memuat password. Bootstrap dan restore hold ada di [[development]]: [panduan](../development.md).

Scope model fail closed tanpa context, tenant berasal dari identitas server, admin membaca assignment terbaru, dan developer tidak dapat mengakses detail operasi. Lihat [[m1-verification]]: [bukti dan batas AC](../audits/m1-verification.md).

Gotcha yang telah diuji: Referrer-Policy no-referrer membutuhkan pencatatan GET Inertia untuk redirect form; history browser dibersihkan setelah session invalidate agar tombol Back tidak menampilkan tenant lama. Konfirmasi sinkronisasi setelah edit tab lain harus membuat preview baru. Program loyalti/transaksi/notifikasi pelanggan/demo UI menunggu milestone berikutnya.
