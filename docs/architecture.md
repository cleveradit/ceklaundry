# Architecture Map — CekLaundry

**Status:** rancangan; belum diverifikasi terhadap kode. Spesifikasi lengkap ada di [architecture.md](initiate-file/architecture.md), dengan kebutuhan di [user-stories.md](initiate-file/user-stories.md) dan [nfr.md](initiate-file/nfr.md).

## Ringkasan

Satu monolit Laravel dirancang melayani beberapa bisnis laundry dalam satu MySQL. Panel developer, owner, dan admin memakai Inertia + React; halaman publik pengecekan resi memakai Blade. Isolasi tenant dan cabang ditegakkan di lapisan data dan server.

## Komponen yang direncanakan

| Komponen | Tanggung jawab | Lokasi target |
|---|---|---|
| Routes, middleware, controller, policy | Akses publik dan panel, otorisasi, tenant, masa aktif | `routes/`, `app/Http/`, `app/Policies/` |
| Service layer | Harga, transaksi, pembayaran, stempel, sinkronisasi, laporan, demo | `app/Services/` |
| Model dan migrasi | Skema, scope tenant, relasi | `app/Models/`, `database/migrations/` |
| Panel | UI developer/owner/admin | `resources/js/` |
| Halaman publik dan resi | Cek status, penyamaran data, cetak | `resources/views/` |
| Jobs dan scheduler | Notifikasi email/WA, pengingat, pembersihan demo | `app/Jobs/`, `app/Console/Commands/` |

Lokasi di tabel adalah target rancangan Laravel, belum direktori yang sudah ada.

## Alur utama yang direncanakan

Admin/owner membuat transaksi pada tenant dan cabang yang sah → service menghitung harga dan menyimpan item serta snapshot → pembayaran dicatat secara tambah-saja → status cucian maju sesuai state machine → pelanggan melihat status melalui kode resi global → notifikasi dikirim asinkron ketika memenuhi aturan. Detail transisi, masa aktif, dan batas akses berada di [spesifikasi arsitektur](initiate-file/architecture.md).

## Integrasi dan operasi yang direncanakan

MySQL 8.4 untuk data dan queue; SMTP global/per bisnis untuk email; adapter Fonnte/Wablas/WABA untuk WA bila diaktifkan; cron untuk scheduler; Docker Compose dan reverse proxy HTTPS untuk deployment. Belum ada integrasi yang terpasang.
