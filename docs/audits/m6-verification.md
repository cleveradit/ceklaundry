# Verifikasi M6 — Demo dan PWA

Status: implementasi diuji pada MySQL 8.4 dan Chrome 154.0.8037.92 lokal, 1 Oktober 2026. Kode bukti: **L** = suite PHP/MySQL lokal, **B** = browser Chromium lokal, **P** = pemeriksaan kode/build, **C** = CI remote, **S** = staging/perangkat fisik belum diuji. Implementasi belum dideploy ke staging atau produksi.

## Matriks 17 acceptance criteria

| AC | Bukti dan batas | Status |
|---|---|---|
| US-601.1 | `DemoFlowTest`: POST satu klik, owner langsung masuk; 2 cabang, 3 master/6 layanan, promo/stempel/DP, pelanggan dan berbagai status. Browser M6 menjalankan tombol halaman depan. | L/B |
| US-601.2 | Browser M6 membuat dua demo pada konteks terpisah; ID transaksi A ditolak pada B. | B |
| US-601.3 | `DemoFlowTest`: tepat 6 pelanggan/15 transaksi, 10 diambil, ledger stempel, 24+ log simulasi; `DemoFixtureSeeder` memakai layanan domain untuk harga/payment/status/promo. | L/P |
| US-602.1 | Banner dan tombol peran tampil pada panel owner/admin demo; browser menjalankan keduanya. | L/B |
| US-602.2 | `DemoFlowTest` menolak endpoint peran pada tenant biasa. | L |
| US-602.3 | Akun admin reserved pada Cabang Utama; browser memastikan halaman owner ditolak saat menjadi admin. Pembatasan cabang juga memakai tes isolasi M1–M5. | L/B |
| US-602.4 | Parameter arbitrary ditolak, akun/cabang reserved dilindungi, ID sesi berubah saat switch, dan assignment admin yang berubah langsung membatalkan sesi. | L |
| US-603.1 | Fixture ready/reminder membuat log terpisah `ditekan_demo`; `Http::fake`/`Mail::fake` membuktikan nol kiriman, tidak ada job dan kuota tidak dipakai. | L |
| US-603.2 | Tes reset, verifikasi email, email/WA manual dan status publik; pratinjau WA internal tanpa `wa.me`/`tel:`. | L/B |
| US-604.1 | `DemoFlowTest` melakukan time travel ke batas 7×24 jam, panel/status 410 sebelum purge; cron tiap menit dan command memproses backlog expired. Batas lima menit bergantung pada scheduler sehat. | L/P |
| US-604.2 | Tes serial dan empat worker MySQL paralel menghasilkan tiga berhasil, keempat 429; hari WIB berikutnya dapat membuat lagi. | L |
| US-604.3 | Dua worker purge menunggu lock bisnis yang sama, selesai idempoten; job dan cache tenant dibersihkan, tenant normal dan FK tetap utuh. | L |
| US-605.1 | Manifest, ikon dan metadata Apple diverifikasi melalui browser; instalasi dan splash pada perangkat Android/iOS fisik belum diuji. | B/P/S |
| US-606.1 | Browser memeriksa cache hanya halaman offline dan aset ber-hash; halaman dinamis tetap diambil dari jaringan. Pengukuran waktu muat ulang belum dilakukan. | B/P |
| US-606.2 | Hash bundle Vite dipakai sebagai versi service worker; build menghasilkan aset ber-hash. Simulasi deploy dua versi secara end-to-end belum dilakukan. | P |
| US-606.3 | Browser memutus jaringan dan mendapatkan halaman “Anda sedang offline”. | B |
| US-606.4 | Service worker tidak mengintersep POST/menyimpan HTML dinamis; browser memeriksa cache, tenant A→logout→demo baru/back, serta input belum disimpan ketika event update datang. | B/P |

## Enam kriteria selesai plan M6

| Kriteria | Bukti | Status |
|---|---|---|
| Dua prospek memiliki tenant terpisah | Browser konteks A/B dan 404 lintas tenant | B |
| Tidak ada email/WA nyata | Fake HTTP/Mail, log simulasi, nol queue; pemeriksaan jalur reset dan tautan | L/P |
| Expired langsung tidak dapat diakses | Time travel tepat pada `demo_expires_at`, panel/status 410 | L |
| Purge menyusul outage dan aman paralel | `run()` memilih seluruh expired; dua worker mengunci baris; tenant normal bertahan | L/P |
| PWA pada perangkat target dan cache tidak bocor | Cache/logout/tenant switch di Chromium viewport HP; Android/iOS fisik tertunda | B/S |
| Form belum disimpan tidak hilang diam-diam | Browser menahan reload saat input berubah, dialog konfirmasi dapat ditolak | B |

## Hasil gate dan batas

Suite backend sebelum dua kasus batas terakhir lulus **132 tes/1.187 assertion** (700,51 detik). `DemoFlowTest` pada image kode akhir lulus **7 tes/87 assertion**; `DemoConcurrencyTest` lulus **2 tes/25 assertion**. Pint lulus 218 file; lint, TypeScript, Vite build, validator spesifikasi, dan `git diff --check` lulus lokal. Browser M6 lulus pada Chrome 154 dengan viewport 390 × 844 dan database QA terpisah; dua demo, role switch, pratinjau WA, cache allowlist, offline, form kotor dan pergantian tenant diperiksa. Browser M1–M5 pada perubahan akhir dan CI remote masih menunggu hasil run setelah push.

Kasus PHP M6 yang direncanakan sebagai beberapa file pada TICKET-044–049 digabung ke `DemoFlowTest` dan `DemoConcurrencyTest` agar fixture berat tidak diulang per berkas. Nama file berubah, sedangkan cakupan fixture, rollback, otorisasi, outbound, expiry/purge dan interleaving MySQL diuji melalui dua suite tersebut serta regresi M1–M5.

Browser HP memakai viewport emulasi, bukan perangkat fisik. Uji pemasangan Android/iOS, splash OS, deploy dua versi SW secara end-to-end pada perangkat, provider nyata, restore backup dan cutover tetap perlu verifikasi tahap berikutnya. Klaim Live berlaku untuk runtime lokal/CI setelah gate remote lulus; produksi belum diverifikasi.
