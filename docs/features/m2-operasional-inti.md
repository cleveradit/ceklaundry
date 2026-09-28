# M2 — Operasional inti

**Status:** diimplementasikan pada branch `codex/implement-m2`. Bukti dan batas uji ada di [audit M2](../audits/m2-verification.md). Live di sini berarti fungsi tersedia di runtime lokal/CI, bukan deploy produksi.

## Alur operator

Owner menyiapkan cabang dan layanan lokal, lalu dapat mengaktifkan atau mematikan DP baru di `/owner/settings/payment`. Admin bekerja hanya di cabang tugasnya. Owner dapat memilih cabang untuk dashboard dan transaksi; developer tidak mendapat akses data operasi.

Di `/app/transactions/create`, operator mencari pelanggan lama dengan nama/nomor HP atau membuat pelanggan baru, menambahkan layanan, menghitung penawaran server, lalu menyimpan. Layanan kg memakai berat aktual kelipatan 0,1 kg dan minimum untuk harga; layanan item memakai jumlah unit. Harga, durasi, nama dan satuan disalin ke item transaksi. Penawaran mempunyai fingerprint sehingga perubahan layanan atau saklar DP di antara preview dan simpan ditolak dengan 409 dan penawaran terbaru ditampilkan untuk dikonfirmasi lagi. Kunci request membuat retry create identik mengembalikan transaksi yang sama.

Pembayaran awal opsional dan transaksi disimpan atomik. Pembayaran berikutnya memakai kunci request tersendiri, hanya menerima tunai/transfer, tidak boleh melebihi sisa, serta mengubah status bayar `BELUM_BAYAR` → `DP` → `LUNAS`. Jika DP baru nonaktif, pembayaran pertama harus penuh; cicilan transaksi yang sudah DP tetap boleh dilanjutkan. Pembayaran yang sudah tercatat tidak diedit atau dihapus.

Status bergerak `DITERIMA` → `DIPROSES` → `SIAP_DIAMBIL` → `SUDAH_DIAMBIL`. Penyerahan memerlukan lunas. Pembatalan hanya saat belum diambil; pembayaran dan riwayatnya tetap tersimpan, pengembalian dana dilakukan di luar aplikasi. Edit harga/layanan hanya saat `DITERIMA` dan belum ada pembayaran atau ledger loyalti. Catatan, estimasi, dan perkiraan jumlah baju memiliki jalur edit terbatas. Konflik versi menghasilkan 409.

Pelanggan unik per bisnis berdasarkan nomor HP canonical `62…`; pencarian dan edit identitas tersedia di `/app/customers`. Owner atau admin yang berwenang dapat menggabungkan duplikat. Merge memindahkan transaksi dan ledger, menghitung ulang saldo stempel, serta menyimpan audit. Admin tidak boleh merge bila ada transaksi di cabang lain. Snapshot transaksi dan email notifikasi lama tetap historis; pengiriman otomatis baru M3.

Dashboard `/app` memperlihatkan jumlah dan berat masuk hari ini, status aktif, daftar siap diambil dan terlambat untuk cabang yang dipilih. `/app/transactions` mencari kode resi, nama dan nomor pelanggan dalam cakupan peran/cabang. Tampilan dan angka tidak memberi developer jalan pintas ke transaksi.

## Resi pelanggan

Homepage Blade menyediakan cek kode tanpa JavaScript. `/t/{kode}` hanya memperlihatkan identitas tersamar, status/timeline, item, waktu, biaya dan kontak cabang. Lookup form, URL langsung, serta cetak publik memakai limiter bersama 30 permintaan/IP/menit; kode salah memberi pesan umum. Respon memakai no-store, no-referrer dan noindex.

Operator dapat mencetak resi 58 mm dari panel dengan identitas penuh setelah otorisasi; versi cetak publik memakai DTO tersamar. QR SVG dibuat lokal dan mengarah ke URL status. Tombol WhatsApp membuka `wa.me` dengan pesan ringkas; ini tindakan manual, bukan pengiriman otomatis atau bukti pesan diterima. Pencatatan pembukaan WA menunggu M3.

## Batas integrasi

Notifikasi email/WA otomatis dan verifikasi alamat menunggu M3. Pemberian/penukaran stempel serta promo menunggu M4; M2 hanya menyiapkan ledger dan kompensasi batal untuk data yang sudah ada. Laporan keuangan M5 dan demo/PWA M6 belum tersedia. Tidak ada klaim uji printer thermal fisik atau provider WA dari perjalanan browser.
