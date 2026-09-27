# Implementation Plan: TICKET-001 (Persiapan implementasi)

**Ticket:** `TICKET-001`
**Status:** `DONE`
**Target Audience:** AI Developer Agents
**Depends On:** Tidak ada
**Tahap:** Urutan 0 — Persiapan

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan tahap 0](../../plan.md#4-tahap-0--persiapan), [konteks proyek](../../ai-context.md), [audit final](../../audits/final-system-audit.md) |
| Keterlacakan | Prasyarat US-101–US-109; NFR PLH-01, PLH-03, PLH-04, LOK-03, SEC-01 |
| Target tetap | PHP 8.4, Laravel 12, MySQL 8.4, panel Inertia/React/TypeScript strict; publik Blade |
| Jalur lingkungan yang direncanakan | Checkout dan perintah pengembangan di WSL Ubuntu; runtime PHP/Composer dan build Node/npm dalam Docker, tanpa mensyaratkan PHP/Node host |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

Versi dependensi selain yang ditetapkan spesifikasi dipilih berdasarkan kompatibilitas dan dikunci pada TICKET-002. Hasil pemeriksaan lingkungan lama di plan bukan bukti kondisi kini.

## 2. Objective

Menghasilkan jalur setup yang dapat dipakai untuk memulai M1 serta daftar prasyarat yang terverifikasi. Selesaikan hambatan lingkungan sebelum scaffold, tanpa mengubah spesifikasi produk.

## 3. Non-Negotiable Technical Contract

1. `docs/development.md`: catat tanggal, shell/path WSL, hasil versi Docker/Compose, akses daemon, port lokal, kapasitas disk, dan jalur PHP/Composer/Node/npm berbasis container. Bedakan hasil aktual dengan rencana.
2. `docs/planning/index.md`: urutan TICKET-001 sampai TICKET-010 mengikuti dependensi; tidak ada nomor bertabrakan dengan arsip.
3. `docs/planning/current-session.md`: simpan hasil pemeriksaan, blocker yang nyata, dan perintah/langkah berikutnya.
4. `docs/development.md`: tetapkan konfigurasi lokal pada `.env` yang diabaikan Git; `.env.example` hanya nama variabel/nilai nonrahasia. APP_KEY dan password dibuat lokal, bukan contoh kredensial yang dapat digunakan.

## 4. Scope of Changes

### A. Orientasi dan keselamatan pekerjaan

1. Baca hierarki spesifikasi, audit, indeks aktif dan arsip; periksa status Git sebelum bekerja.
2. Pertahankan perubahan pengguna; catat konflik jika menyentuh target scaffold.

### B. Pemeriksaan lingkungan

1. Verifikasi WSL, Docker Engine/Compose, akses image registry, port yang akan digunakan, dan ruang penyimpanan.
2. Dokumentasikan layanan `app`, `web`, `db`, `worker`, `cron`; Node adalah tahap build/tooling, bukan layanan produksi tambahan yang wajib.
3. Pilih port web lokal yang kosong; database tidak perlu dipublikasikan ke host. Jangan menghentikan layanan pengguna untuk mengambil port.
4. Tentukan cara injeksi rahasia lokal/CI dan input rahasia bootstrap developer tanpa argumen CLI yang masuk history.

### C. Handoff

1. Pastikan TICKET-002 memiliki lingkup, dependensi dan kriteria yang dapat diuji.
2. Catat hasil di dokumentasi dan perbarui tautan hub `docs/index.md` saat `docs/development.md` dibuat.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Jalur utama | Checkout WSL dan daemon Docker tersedia | Runtime PHP 8.4 dan build frontend container dapat disiapkan; port tercatat | `[x]` |
| Batas lingkungan host | PHP host berbeda versi atau Node host tidak ada | Jalur container tetap dapat digunakan; tidak mengklaim alat host sebagai prasyarat | `[x]` |
| Port terpakai | Port web pilihan sudah dipakai | Pilih port kosong dan catat, tanpa menghentikan proses pengguna | `[x]` |
| Gagal akses Docker | Daemon/registry tidak dapat diakses | Blocker dan langkah pemulihan tercatat; tahap 0 belum DONE | `[x]` |
| Perubahan existing | Working tree berisi pekerjaan pengguna | Tidak ditimpa/direset oleh persiapan | `[x]` |
| Rahasia | Contoh konfigurasi dan dokumentasi diperiksa | Tidak ada password, APP_KEY, token atau kredensial asli | `[x]` |

## 6. Verification Commands

Jalankan di WSL dari root repo dengan prefix `rtk` sesuai instruksi lingkungan. Perintah berikut adalah rencana eksekusi, belum dijalankan oleh pembuatan tiket ini:

```bash
rtk git status --short
rtk proxy docker version
rtk proxy docker compose version
rtk proxy docker info
rtk proxy ss -ltn
rtk proxy df -h .
rtk proxy python3 docs/audits/validate-final-specs.py
rtk git diff --check
```

Hasil yang diharapkan: akses Docker dan port teridentifikasi, validator dokumentasi lulus, prasyarat TICKET-002 tercatat. Jika RTK tidak tersedia di WSL, gunakan RTK host untuk mem-proxy perintah WSL; jangan menganggapnya blocker produk.

## 7. Out of Scope

1. Scaffold aplikasi, instalasi dependensi aplikasi, migrasi, dan UI M1.
2. Deploy VPS, domain, notifikasi nyata, serta perubahan konfigurasi global mesin yang tidak diperlukan.
3. Menganggap tahap 0 selesai hanya karena tiket sudah ditulis.

## 8. Completion Checklist

- [x] Lingkup eksekusi telah diotorisasi; status READY sebelum eksekusi.
- [x] Pemeriksaan lingkungan dan pengelolaan rahasia terdokumentasi dengan bukti.
- [x] Matriks penerimaan dan verifikasi selesai; tidak ada blocker fondasi tersisa.
- [x] Dokumentasi, indeks dan sesi diperbarui tanpa perubahan di luar lingkup.
- [x] DONE hanya setelah pekerjaan diverifikasi; arsipkan sesuai workflow.

## Hasil implementasi dan verifikasi

Selesai pada 28 September 2026 sesuai otorisasi pengguna. Bukti rinci, matriks per AC dan batas integrasi M2–M6 ada di [audit M1](../../audits/m1-verification.md); perintah aktual di [development](../../development.md).

CI final: [36350950049](https://github.com/cleveradit/ceklaundry/actions/runs/36350950049), 52 tes/306 assertions, quality/build/MySQL/browser lulus. Status checklist berlaku untuk lingkup M1; consumer masa depan tidak diklaim lulus E2E. Kasus kegagalan diuji melalui fault/guard dan setup bersih.
