# CekLaundry — Documentation Hub

CekLaundry dirancang sebagai aplikasi pengelolaan laundry multi-bisnis: operasional cabang, status cucian publik, pembayaran, notifikasi, promo/loyalti, laporan, demo, dan PWA. **Status saat ini: M1–M3 diimplementasikan dan diverifikasi pada runtime lokal/CI; M4–M6 belum tersedia.** Pengiriman provider nyata dan restore backup fisik memerlukan verifikasi staging.

**Stack rancangan:** PHP 8.4 · Laravel 12 · MySQL 8.4 · Inertia/React/TypeScript · Blade · Docker Compose.

## Mulai dari sini

1. [AI Context & Mandat](ai-context.md) — aturan kerja agent dan keadaan repo.
2. [Architecture Map](architecture.md) — ringkasan rancangan; sumber rinci: [arsitektur](initiate-file/architecture.md).
3. [Data Model Reference](data-model.md) — ringkasan model; sumber rinci: [skema database](initiate-file/database-schema.md).
4. [User Stories](initiate-file/user-stories.md) — kebutuhan M1–M6 dan kriteria penerimaan.
5. [NFR](initiate-file/nfr.md) — keamanan, isolasi tenant, kinerja, operasi, dan kualitas.
6. [Feature Docs Index](features/index.md) — fitur yang benar-benar diimplementasikan dan diverifikasi.

- [Pengembangan lokal](development.md) — lingkungan, konfigurasi dan perintah terverifikasi.

## Proses & Perencanaan

- [Backlog](backlog.md) — ide/bug/blocker baru di luar inventaris user stories.
- [Planning](planning/index.md) — ticket implementasi aktif dan urutannya.
- [Current Session](planning/current-session.md) — status pekerjaan terakhir.
- [Decision Log](decision-log.md) — keputusan dengan konsekuensi yang tidak langsung terlihat.

## Spesifikasi dan audit

[PRD sumber produk](initiate-file/prd.md) tersedia bersama keempat dokumen turunannya. [Final System Audit](audits/final-system-audit.md) mencatat keputusan, skenario adversarial, traceability, dan validasi dokumentasi. Bukti runtime dan batas consumer masa depan ada di [verifikasi M1](audits/m1-verification.md), [verifikasi M2](audits/m2-verification.md), dan [verifikasi M3](audits/m3-verification.md).
