# CekLaundry — Documentation Hub

CekLaundry dirancang sebagai aplikasi pengelolaan laundry multi-bisnis: operasional cabang, status cucian publik, pembayaran, notifikasi, promo/loyalti, laporan, demo, dan PWA. **Status saat ini: spesifikasi tersedia, aplikasi belum dibuat.**

**Stack rancangan:** PHP 8.4 · Laravel 12 · MySQL 8.4 · Inertia/React/TypeScript · Blade · Docker Compose.

## Mulai dari sini

1. [AI Context & Mandat](ai-context.md) — aturan kerja agent dan keadaan repo.
2. [Architecture Map](architecture.md) — ringkasan rancangan; sumber rinci: [arsitektur](initiate-file/architecture.md).
3. [Data Model Reference](data-model.md) — ringkasan model; sumber rinci: [skema database](initiate-file/database-schema.md).
4. [User Stories](initiate-file/user-stories.md) — kebutuhan M1–M6 dan kriteria penerimaan.
5. [NFR](initiate-file/nfr.md) — keamanan, isolasi tenant, kinerja, operasi, dan kualitas.
6. [Feature Docs Index](features/index.md) — fitur yang benar-benar selesai; masih kosong.

## Proses & Perencanaan

- [Backlog](backlog.md) — ide/bug/blocker baru di luar inventaris user stories.
- [Planning](planning/index.md) — ticket implementasi aktif dan urutannya.
- [Current Session](planning/current-session.md) — status pekerjaan terakhir.
- [Decision Log](decision-log.md) — keputusan dengan konsekuensi yang tidak langsung terlihat.

## Kesenjangan sumber

Spesifikasi di `docs/initiate-file/` menyatakan `prd.md` sebagai dokumen induk, tetapi file itu belum ada dalam repository. Gunakan spesifikasi yang tersedia untuk langkah yang jelas; tandai ketergantungan terhadap PRD saat keputusan belum bisa diturunkan darinya.
