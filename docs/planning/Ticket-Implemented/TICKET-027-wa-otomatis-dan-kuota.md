# Implementation Plan: TICKET-027 (Adapter WA otomatis dan kuota bulanan)

**Ticket:** `TICKET-027`

**Status:** `DONE`

**Hasil:** implementasi dan verifikasi dicatat pada [audit M3](../../audits/m3-verification.md). Matriks di bawah adalah rencana pengujian awal; audit mencatat tingkat bukti aktual dan batas staging per AC.

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-021`, `TICKET-022`, `TICKET-024`, `TICKET-025`, `TICKET-026`

**Tahap:** M3 — WhatsApp API

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | US-305 AC2–4, US-307 AC1–10, PRD 5.G/9.2, arsitektur 6.3 |
| Keterlacakan | US-305 AC2–4, US-306 AC1–2, US-307 AC1–10, US-308 AC3–4/10–13; AND-03/04/11/21/27/28, SEC-03, ISO-05 |
| Adapter | Interface `WhatsAppProvider`; Fonnte, Wablas, WABA. Accepted hanya dari bukti respons penyedia; HTTP 200 dengan body error dan HTTP5xx ambigu tidak dianggap berhasil. |
| Kuota | Slot per bisnis/bulan WIB untuk tertunda/diproses/berhasil/perlu_pemeriksaan; satu slot/key; sebelum attempt pindah bucket bulan baru; setelah attempt bucket immutable. |
| Default | WA otomatis global off; email dan `wa.me` manual tetap berfungsi bila credential/provider tidak ada. |
| Otorisasi | Pengguna menyetujui implementasi seluruh TICKET-021–029 pada 29 September 2026. |

## 2. Objective

Bisnis yang mengaktifkan WA dapat mengirim ready dan pengingat otomatis melalui provider pilihannya dengan batas otorisasi biaya yang konsisten. Kegagalan WA tidak mengulang email atau membatalkan transaksi.

## 3. Non-Negotiable Technical Contract

1. `app/Services/WhatsApp/WhatsAppProvider.php`, `FonnteProvider.php`, `WablasProvider.php`, `WabaProvider.php`: input tujuan/payload/credential tenant; keluaran accepted/definitely_rejected/unknown beserta safe code; TLS, timeout dan tanpa redirect lintas host.
2. `app/Services/WaQuotaService.php`, `app/Services/NotificationDispatcher.php`: reservasi slot di bawah root lock, `wa_quota_month` WIB, hitung occupied, limit 0/null, pindah bulan sebelum attempt, stop retry lintas bulan setelah attempt.
3. `app/Jobs/SendNotification.php`: pilih client/config tenant per job, freeze provider/options/payload pada attempt pertama, tanpa fallback vendor pada unknown/provider berubah; gunakan guard recipient TICKET-026 sebelum keputusan kuota/retry.
4. `tests/Contract/WhatsAppProviderTest.php`, `tests/Integration/WaQuotaConcurrencyTest.php`, `tests/Feature/Notification/WaAutomaticTest.php`: fixture respons resmi provider, dua proses MySQL, lifecycle dan isolasi tenant.

## 4. Scope of Changes

1. WABA memakai template approved dua event dengan lima parameter urut nama cabang, resi, total, sisa, URL; Wablas URL HTTPS host yang diizinkan; Fonnte endpoint tetap. Verifikasi mapping endpoint/body/respons pada implementasi melalui contract tests.
2. Kanal ready dan reminder WA dibuat hanya bila global enabled, credential lengkap dan switch event on. Kuota penuh menghasilkan `dilewati_batas`, sedangkan email berjalan sendiri.
3. Retry definite rejection memakai key/slot sama; unknown memegang slot asal. Provider berubah sesudah attempt tidak fallback; recipient berubah lebih dulu diproses menurut TICKET-026.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Normal | Provider accepted, ready WA on, email ada | Satu WA dan satu email independen; log per kanal benar | `[ ]` |
| Kuota race | Limit100, 99 occupied, dua reservasi paralel | Satu slot baru, satu skipped; email keduanya tetap eligible | `[ ]` |
| Bulan | Pending sebelum attempt melintas WIB midnight; accepted sesudah midnight; retry bulan depan | Pending pindah/check; accepted bucket asal; retry lama dilewati | `[ ]` |
| Provider | HTTP200 body error, 5xx/timeout, WABA template ditolak | Tidak false success; unknown tanpa resend/fallback; rejection pasti retry terbatas | `[ ]` |
| Konfigurasi | WA off, token hilang, bisnis A/B bergantian | Tanpa WA saat off; konfigurasi tenant tidak terbawa; email/manual tetap | `[ ]` |
| Restore/recipient | Hold atau recipient berubah sebelum retry | Nol call/slot baru; review sesuai aturan, callback lama gagal CAS | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=WhatsAppProviderTest`

`rtk proxy docker compose exec -T app php artisan test --filter=WaQuotaConcurrencyTest`

`rtk proxy docker compose exec -T app php artisan test --filter=WaAutomaticTest`

`rtk git diff --check`

## 7. Out of Scope

1. Penagihan atau rekonsiliasi biaya aktual vendor; kuota hanya otorisasi aplikasi.
2. Aktivasi WA otomatis tanpa credential dan switch lengkap.

## 8. Completion Checklist

- [x] Implementasi, pengujian QA/CI, dan batas bukti dicatat pada [audit M3](../../audits/m3-verification.md).
