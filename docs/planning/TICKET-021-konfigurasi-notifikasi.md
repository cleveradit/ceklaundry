# Implementation Plan: TICKET-021 (Konfigurasi notifikasi per bisnis)

**Ticket:** `TICKET-021`

**Status:** `READY`

**Target Audience:** AI Developer Agents

**Depends On:** `TICKET-020`

**Tahap:** M3 — konfigurasi

## 1. Business Decision Snapshot

| Item | Approved Value |
|---|---|
| Sumber | [Plan M3](../plan.md#7-m3--notifikasi), [PRD 9](../initiate-file/prd.md#9-aturan-notifikasi--pengingat), US-305/306, arsitektur 6.3, skema 2.1–2.2 |
| Keterlacakan | US-305 AC1–4; US-306 AC1–2; SEC-03, ISO-01/05, AND-11/20/21/27, UX-01/04, LOK-01 |
| Pemisahan peran | Developer mengatur SMTP, sender, provider, credential dan aktivasi teknis WA per bisnis; owner mengatur pengingat N/M/K, saklar WA per peristiwa dan batas bulanan. Default WA otomatis nonaktif. |
| Nilai | N/M/K 1–255 meski pengingat mati; WA limit null tanpa batas dan 0 menutup slot; batas tidak dapat diturunkan di bawah occupied bulan WIB berjalan. |
| Credential | `wa_token`, `wa_config`, `smtp_config` pada `businesses` memakai encrypted cast pada TEXT; browser hanya menerima indikator terkonfigurasi dan input pengganti. |
| Otorisasi | Penyusunan tiket diminta pengguna; implementasi M3 belum diminta. |

## 2. Objective

Developer dan owner dapat mengatur bagian notifikasi sesuai haknya tanpa membuka rahasia ke pihak lain. Konfigurasi menjadi sumber tunggal yang dibaca worker secara segar untuk setiap tenant.

## 3. Non-Negotiable Technical Contract

1. `app/Http/Controllers/Developer/BusinessController.php`, `app/Models/Business.php`, `resources/js/Pages/Management.tsx`: form konfigurasi teknis per bisnis; validasi provider/SMTP lengkap; rahasia tidak pernah diserialkan kembali.
2. `app/Http/Controllers/Owner/NotificationSettingController.php`, `resources/js/Pages/Owner/NotificationSettings.tsx`, `routes/web.php`: owner hanya mengubah `business_settings` perilaku, bukan token/provider; pembacaan kuota per bucket WIB.
3. `app/Services/NotificationSettingsService.php`: semua perubahan di bawah business root lock; perubahan saklar/lifecycle menutup pending yang tak lagi sah melalui `PendingNotificationInvalidator` dalam commit yang sama.
4. `tests/Feature/Notification/NotificationSettingsTest.php`: hak akses, validasi rentang, secret masking, dua tenant, batas occupied, off→on cepat.

## 4. Scope of Changes

1. Lengkapi UI dan validasi terhadap kolom M1 yang sudah ada; migrasi baru hanya jika ditemukan selisih kontrak skema nyata.
2. SMTP kustom harus lengkap atau null; sender name/address berpasangan; provider WABA/Wablas menolak konfigurasi kurang seperti arsitektur 6.3. Jangan melakukan test-send saat save.
3. Hitung WA berhasil menurut `wa_quota_month`, tampilkan occupied tertunda/diproses/perlu_pemeriksaan terpisah; pembacaan owner hanya bisnisnya.

## 5. Acceptance Test Matrix

| Case | Input | Expected Result | Status |
|---|---|---|---|
| Konfigurasi sah | Developer set SMTP bisnis A, owner set N=2/M=2/K=3 | Tersimpan, tenant B tetap global/default, owner tidak melihat credential | `[ ]` |
| Batas | N/M/K 0 atau 256; limit 0/null; turunkan limit di bawah occupied | Nilai rentang ditolak; 0/null sah; penurunan yang melanggar ditolak atomik | `[ ]` |
| Rahasia | GET developer/owner sesudah token disimpan | Hanya indikator, tidak ada ciphertext/plaintext/token pada HTML, JSON, log | `[ ]` |
| Aktivasi gagal | WABA tanpa template/phone ID atau SMTP parsial | Validasi menolak; WA tidak aktif | `[ ]` |
| Siklus saklar | Pengingat off→on sebelum worker berjalan | Pending lama terminal; tidak terkirim saat on kembali | `[ ]` |

## 6. Verification Commands

`rtk proxy docker compose exec -T app php artisan test --filter=NotificationSettingsTest`

`rtk proxy docker compose exec -T app ./vendor/bin/pint --test`

`rtk git diff --check`

## 7. Out of Scope

1. Panggilan SMTP/WA dan reservasi notifikasi.
2. Laporan biaya provider atau test-send langsung.

## 8. Completion Checklist

- [ ] Status READY setelah pengguna meminta implementasi.
- [ ] Kontrak dan seluruh kasus matriks lulus di MySQL QA.
- [ ] Tidak ada credential dalam respons atau log.
