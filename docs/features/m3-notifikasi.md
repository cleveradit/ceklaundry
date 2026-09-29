# M3 — Notifikasi

**Status:** Live pada runtime lokal/CI; hasil uji dan batasnya ada di [audit M3](../audits/m3-verification.md). Live berarti tersedia pada runtime lokal/CI, bukan sudah dideploy produksi atau diterima penyedia eksternal.

## Pengaturan

Developer mengatur SMTP dan integrasi WhatsApp per bisnis di `/dev/businesses/{id}/notifications`. Credential disimpan terenkripsi dan tidak dikirim kembali ke browser. Owner mengatur saklar email/WA per peristiwa, hari pengingat, dan batas WA bulanan di `/owner/settings/notifications`. WA otomatis awalnya mati. Email dan tautan `wa.me` manual dapat dipakai terpisah dari saklar WA otomatis.

## Email transaksi

Pada resi publik `/t/{kode}`, pelanggan dapat meminta verifikasi alamat email transaksi melalui form tanpa JavaScript. Tautan bertanda tangan membuka halaman konfirmasi; hanya POST konfirmasi yang menyimpan email transaksi. Alamat ini merupakan snapshot transaksi dan tidak mengubah master pelanggan. Link yang kedaluwarsa, tergantikan, atau menjadi tidak berlaku karena status/merge ditolak. Form disembunyikan ketika resi hanya baca.

Saat status menjadi `SIAP_DIAMBIL`, sistem mereservasi notifikasi untuk kanal yang aktif. Perubahan status tetap berhasil bila antrean atau provider gagal. Pengingat diproses terjadwal menurut N/M/K hari, dengan cursor per transaksi. Transaksi historis sebelum cutoff restore tidak diulang. Perubahan nomor pelanggan dan merge diperiksa ulang sebelum WA dikirim.

## Pengiriman dan riwayat

Log dan job dibuat atomik dengan transaksi bisnis. Worker memeriksa status, penerima, saklar, cutoff, dan kuota lagi sebelum panggilan luar. Respons pasti gagal dapat dicoba kembali terbatas; hasil yang ambigu ditandai `perlu_pemeriksaan` agar tidak terkirim ulang secara buta. Kuota WA memakai slot bulan WIB; email mempunyai jalur sendiri. Detail transaksi menampilkan riwayat per kanal dan status, termasuk pengiriman manual. Operator dapat mengirim email manual dengan kunci idempoten atau membuka `wa.me` manual; pembukaan tautan dicatat sebagai `dibuka_manual`, bukan bukti WhatsApp diterima.

## Operasi dan batas bukti

[Runbook pengembangan](../development.md#pemulihan-seluruh-outbound) menjelaskan restore hold, rekonsiliasi, dan cutoff saat pemulihan. Audit M3 memisahkan uji MySQL, browser, dan respons adapter HTTP fake dari kiriman nyata. Credential provider produksi, perangkat pelanggan, latihan restore backup fisik, dan integrasi demo/PWA M6 belum menjadi bukti penerimaan M3.
