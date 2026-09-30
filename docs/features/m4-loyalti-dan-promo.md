# M4 — Loyalti dan promo

**Status:** Live pada runtime lokal/CI. Hasil uji serta batas buktinya ada di [audit M4](../audits/m4-verification.md); status ini tidak berarti sudah dideploy produksi.

## Pengaturan owner

Owner mengatur program stempel di `/owner/settings/loyalty`. Program berlaku untuk satu bisnis lintas cabang, awalnya nonaktif. Saat aktif, hadiah harus menunjuk master layanan kg yang aktif dan memiliki batas berat positif. N harus 1–255. Menonaktifkan program menghentikan penawaran dan perolehan baru tanpa menghapus ledger. Master hadiah aktif tidak dapat dinonaktifkan atau diubah menjadi layanan item. Nama layanan cabang harus cocok setelah normalisasi; perubahan nama master tidak menyinkronkan cabang otomatis.

Owner mengelola promo di `/owner/promos`: nama, persen atau nominal, minimum, tanggal WIB inklusif, semua atau sebagian cabang, dan saklar aktif. Perubahan atau penonaktifan berlaku untuk penawaran berikutnya. Transaksi yang sudah tersimpan tetap memakai snapshot lamanya.

## Harga dan ledger

Pada create atau edit finansial yang masih sah, operator memilih satu baris hadiah dan maksimal satu promo. Hadiah memakai berat aktual hingga batas program, walau minimum layanan tetap memengaruhi subtotal. Promo memakai dasar `subtotal − potongan_stempel`; persentase dibulatkan half-up ke rupiah dan nominal dibatasi dasar itu. Server menghitung ulang dan mengembalikan 409 saat quote yang disimpan sudah usang.

Pelanggan mendapat satu stempel ketika transaksi pertama kali LUNAS selama program aktif, termasuk transaksi total Rp0 tanpa penukaran. Transaksi penukaran tidak mendapat stempel. Ledger `loyalty_histories` menyimpan delta perolehan, penukaran, dan kompensasi pembatalan; saldo otoritatif adalah `SUM(jumlah)` dan cache pelanggan diperbarui dalam transaksi yang sama. Dua penukaran bersamaan untuk saldo terakhir diserialkan oleh lock dan hanya satu berhasil. Penggabungan pelanggan memindahkan ledger sumber serta menghitung ulang cache tujuan.

## Tampilan

Riwayat stempel pelanggan tersedia dari direktori pelanggan. Owner melihat semua cabang; admin melihat saldo global tetapi hanya baris transaksi cabangnya. Status publik menampilkan saldo bertanda dan target saat program aktif, termasuk penjelasan saldo negatif yang sah. Detail transaksi, halaman status, resi 58 mm, dan isi notifikasi yang relevan menggunakan nominal serta snapshot promo/hadiah tersimpan.

## Batas bukti

QA browser memakai Chrome dan database uji terpisah. Printer thermal fisik, pengiriman email/WA provider nyata, restore backup fisik, dan deploy produksi belum menjadi bagian bukti M4; lihat [audit M2](../audits/m2-verification.md) dan [audit M3](../audits/m3-verification.md).
