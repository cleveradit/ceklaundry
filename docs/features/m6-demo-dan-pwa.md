# M6 — Demo dan PWA

Status: implementasi lokal pada `main`; hasil verifikasi dan batasnya ada di [audit M6](../audits/m6-verification.md). Belum dideploy ke staging atau produksi.

## Coba demo

Tombol **Coba Demo** di halaman depan membuat bisnis baru dan langsung masuk sebagai owner. Setiap bisnis demo mempunyai dua cabang, tiga layanan master yang disalin menjadi enam layanan cabang, enam pelanggan, 15 transaksi berbagai status, promo, stempel, dan DP aktif. Fixture dibuat melalui layanan harga, transaksi, pembayaran, perubahan status, loyalti, dan pembatalan yang sama dengan alur biasa. Email fixture memakai domain `.invalid`; nomor dan token WA bersifat sintetis.

Banner **MODE DEMO** selalu tampil pada panel. Tombol ganti peran memakai owner dan admin khusus bisnis demo yang sama; admin terikat pada Cabang Utama. Parameter untuk memilih akun atau cabang lain ditolak. Akun dan cabang reserved tidak dapat dinonaktifkan. Perpindahan peran meregenerasi sesi.

Setiap jalur notifikasi demo menghasilkan log `ditekan_demo` untuk kanal yang memenuhi syarat tanpa menambah job atau memakai kuota. Email manual, verifikasi email transaksi, pengingat, dan tautan WA memakai simulasi. Tautan WA membuka pratinjau internal; nomor telepon pada status publik tidak menjadi tautan panggil. Reset password akun demo tidak membuat token atau mengirim email. Penerima dan isi simulasi dapat diperiksa dalam riwayat notifikasi transaksi.

Satu alamat IP dapat membuat paling banyak tiga demo per hari WIB. Demo berakhir tepat tujuh kali 24 jam setelah dibuat; akses panel dan status publik ditolak segera pada batas tersebut. Perintah `app:purge-expired-demos` berjalan setiap menit dan menghapus data domain serta sesi, token, job, dan cache yang terkait setelah masa berakhir. Purge aman dipanggil ulang dan mengunci bisnis sehingga dua proses tidak saling mendahului.

## PWA

Manifest berisi nama, warna tema dan ikon 192/512 px; ikon Apple touch 180 px disediakan. Service worker menyimpan halaman offline dan aset build ber-hash saja. Navigasi selalu meminta server; jika jaringan gagal, halaman **Anda sedang offline** tampil. Data panel, status publik, formulir, respons API, dan POST tidak disimpan atau dikirim ulang oleh service worker.

Versi service worker mengikuti hash bundle aplikasi. Versi baru diaktifkan saat aman; apabila pengguna sudah mengetik pada input, textarea, atau select, halaman menampilkan pemberitahuan dan meminta konfirmasi sebelum memuat ulang. Logout dan perpindahan akun mengambil halaman baru dari server, sehingga cache statis tidak berisi data tenant sebelumnya.

Pemasangan dan perilaku cache diuji dengan browser Chromium serta viewport HP di [audit M6](../audits/m6-verification.md). Pemasangan melalui perangkat Android/iOS fisik dan splash screen sistem operasi belum diverifikasi; perilaku iOS juga bergantung pada versi Safari dan kebijakan perangkat. Fitur offline terbatas pada halaman informasi, tanpa operasi transaksi offline.
