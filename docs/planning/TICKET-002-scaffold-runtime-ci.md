# Implementation Plan: TICKET-002 (Scaffold, runtime, dan CI dasar)

**Ticket:** `TICKET-002`  
**Status:** `READY`  
**Target Audience:** AI Developer Agents  
**Depends On:** `TICKET-001`  
**Tahap:** Urutan 1 — M1, pekerjaan 1–2 dan CI awal

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M1](../plan.md#5-m1--fondasi-dan-tenant), [arsitektur](../initiate-file/architecture.md) bagian 1 dan 9 |
| Keterlacakan | Prasyarat US-101–US-109; PLH-01–PLH-04, KIN-02, UX-05, SEC-04, LOK-01, LOK-03 |
| Stack | PHP 8.4/Laravel 12, MySQL 8.4, Inertia React TypeScript strict, Tailwind, shadcn/ui, Vite; tanpa SSR |
| Runtime | Docker Compose app/web/db/worker/cron; queue/session/cache database; tanpa Redis |
| Dependensi | Pilih versi kompatibel dari dokumentasi resmi saat implementasi, termasuk paket QR SVG server; commit lockfile Composer/npm |
| Otorisasi | Pengguna menyetujui dan meminta implementasi seluruh TICKET-001–010 pada 28 September 2026. |

## 2. Objective

Menyediakan aplikasi dasar yang bisa dijalankan dari checkout bersih, dengan panel dan halaman publik terpisah. CI sejak awal menjalankan pemeriksaan kualitas dan build menggunakan versi runtime proyek.

## 3. Non-Negotiable Technical Contract

1. `composer.json`, `composer.lock`, `package.json`, `package-lock.json`, `artisan`: scaffold Laravel 12 tanpa menimpa `docs/` dan instruksi repo. Tetapkan scripts npm `lint`, `typecheck`, `build` serta Pint melalui Composer.
2. `compose.yaml`, `docker/php/Dockerfile`, `docker/web/default.conf`, `docker/cron/crontab`: lima layanan target; PHP 8.4 dan MySQL 8.4, persistent volume DB/storage, healthcheck, restart layanan, cron setiap menit. Node/npm berada pada build stage/tooling yang terdokumentasi.
3. `config/app.php`, `config/database.php`, `config/queue.php`, `config/session.php`, `config/cache.php`: WIB (`Asia/Jakarta`, DB session `+07:00`), InnoDB/utf8mb4; queue satu koneksi DB domain, `after_commit=false`, `retry_after=180`; worker timeout120 dan tries0.
4. `resources/js/app.tsx`, `resources/views/app.blade.php`, `resources/views/public/home.blade.php`, `vite.config.ts`, `tsconfig.json`: panel React strict tanpa SSR, halaman publik tanpa bundle React. Scaffold publik belum mengklaim pencarian resi bekerja.
5. `.env.example`, `.gitignore`, `.dockerignore`: rahasia hanya lokal/CI; konfigurasi minimum dan port sesuai TICKET-001, tanpa password contoh yang siap pakai.
6. `.github/workflows/ci.yml`, `phpunit.xml`, `tests/Feature/Foundation/RuntimeSmokeTest.php`: Pint/ESLint/TypeScript, smoke test MySQL 8.4, build Vite dan image; tidak melakukan deploy. Tabel infrastruktur bawaan yang diperlukan boleh dibuat di sini, lalu diselaraskan TICKET-003 tanpa migrasi ganda.

## 4. Scope of Changes

### A. Scaffold dan pemisahan frontend

1. Inisialisasi aplikasi, entry panel dan Blade publik; gunakan bahasa Indonesia untuk shell UI dan error.
2. Hilangkan signup/remember-me bawaan bila ikut scaffold; autentikasi final di TICKET-005.

### B. Runtime dan tooling

1. Siapkan proses web, worker, cron, build asset dan penyimpanan persisten dengan konfigurasi yang dapat direproduksi.
2. Sediakan tahap Docker `frontend` untuk perintah tooling berikut, dengan working directory `/app` dan dependensi lockfile; hasil build tersedia pada image aplikasi/web.
3. Dokumentasikan setup, APP_KEY, migrasi infrastruktur, start/stop, worker, scheduler, log aman dan build di `docs/development.md`.

### C. CI sejak awal

1. Pastikan kegagalan pemeriksaan menghentikan pipeline; hasil build tidak diberi label bukti aturan bisnis M1.
2. Perbarui `docs/ai-context.md` hanya sesuai file/perintah yang benar-benar sudah tersedia.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Checkout baru | Lockfile + konfigurasi lokal + DB kosong | Build dan lima layanan dapat dijalankan; smoke test berhasil | `[ ]` |
| Batas versi host | Host tanpa Node/PHP 8.4 | Build/runtime container tetap memakai versi target | `[ ]` |
| Publik tanpa JS | Halaman shell publik, JS dimatikan | Blade terbaca; tidak mengunduh bundle panel | `[ ]` |
| Persistensi | Restart container tanpa menghapus volume | Data uji dan storage tetap ada | `[ ]` |
| DB belum siap | Start layanan bersamaan | Healthcheck/readiness menangani ketergantungan; error aman | `[ ]` |
| Mutu kode gagal | Kesalahan tipe/lint pada branch uji sementara | CI gagal; sesudah diperbaiki seluruh gate lulus | `[ ]` |
| Rahasia | Git diff dan image build context | Tidak memuat .env/kredensial nyata | `[ ]` |

## 6. Verification Commands

Perintah target setelah scaffold; pastikan implementasi menyediakan kontrak ini, lalu rekam hasil aktual:

```bash
rtk proxy docker compose config --quiet
rtk proxy docker compose up -d --build
rtk proxy docker compose ps
rtk proxy docker compose exec -T app php artisan migrate --force
rtk proxy docker compose exec -T app ./vendor/bin/pint --test
rtk proxy docker compose exec -T app php artisan test --filter=RuntimeSmokeTest
rtk proxy docker build --target frontend -t ceklaundry-frontend -f docker/php/Dockerfile .
rtk proxy docker run --rm ceklaundry-frontend npm run lint
rtk proxy docker run --rm ceklaundry-frontend npm run typecheck
rtk proxy docker run --rm ceklaundry-frontend npm run build
rtk git diff --check
```

Ekspektasi: runtime sehat, pemeriksaan lulus, build reproducible; periksa scheduler/worker dengan job smoke aman dan browser publik. Jangan menyalin keluaran konfigurasi berisi rahasia ke laporan.

## 7. Out of Scope

1. Auth lengkap, CRUD tenant/cabang/layanan, dan aturan operasional.
2. Pencarian resi, transaksi, demo, PWA, notifikasi domain dan deploy.
3. Klaim M1 selesai dari smoke test.

## 8. Completion Checklist

- [ ] Lingkup diotorisasi dan dependensi selesai sebelum eksekusi.
- [ ] Kontrak runtime/build/CI diimplementasikan dan seluruh kasus diverifikasi.
- [ ] Perintah aktual dan versi terpilih tersimpan dalam dokumentasi/lockfile.
- [ ] Tidak ada rahasia atau perubahan di luar lingkup.
- [ ] Sesi dan dokumentasi diperbarui; DONE diarsipkan sesuai workflow.
