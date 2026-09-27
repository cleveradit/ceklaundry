# Implementation Plan: [TICKET-ID] ([Short Title])

**Ticket:** `[TICKET-ID]`  
**Status:** `DRAFT` | `REVIEW` | `READY` | `DONE`  
**Target Audience:** AI Developer Agents  
**Depends On:** `[Ticket ID or None]`

---

## 1. Business Decision Snapshot

Isi keputusan yang dibutuhkan sebelum implementasi. Rujuk ID `US-…` dan NFR terkait; tandai sumber keputusan bila `prd.md` belum tersedia.

| Item | Approved Value |
|---|---|
| [Decision 1] | `[FILL]` |
| [Decision 2] | `[FILL]` |
| [Decision 3] | `[FILL or NULL]` |

Rules:

1. If any required `[FILL]` is missing, status MUST stay `DRAFT`.
2. Agent tidak menulis kode produksi untuk lingkup yang masih `DRAFT` atau `REVIEW`.
3. Gunakan `REVIEW` hanya bila keputusan/persetujuan pengguna masih diperlukan.
4. Gunakan `READY` ketika pengguna telah mengotorisasi lingkup pekerjaan, termasuk lewat permintaan implementasi yang jelas. Jangan minta persetujuan ulang.

---

## 2. Objective

Describe the expected business outcome in 2-4 sentences.

---

## 3. Non-Negotiable Technical Contract

List fixed technical targets. Do not use ambiguous alternatives.

1. File: `[absolute or repo path]`
   - Change: `[exact requirement]`
2. File: `[absolute or repo path]`
   - Method signature: ``[exact signature]``
   - Return shape: `[exact array/object contract]`
3. File: `[absolute or repo path]`
   - Integration point: `[exact location/flow]`

Rules:

1. No "or relevant file/service" wording.
2. No scope expansion without explicit approval.

---

## 4. Scope of Changes

### A. [Area A]

1. [Step]
2. [Step]

### B. [Area B]

1. [Step]
2. [Step]

### C. [Area C]

1. [Step]
2. [Step]

---

## 5. Acceptance Test Matrix

Define concrete input-output scenarios.

| Case | Input | Expected Result | Status |
|---|---|---|---|
| [Case 1] | [Input] | [Pass/Fail behavior] | `[ ]` |
| [Case 2] | [Input] | [Pass/Fail behavior] | `[ ]` |
| [Boundary Case] | [Input] | [Boundary behavior] | `[ ]` |

Notes:

1. Include at least one boundary case.
2. Include at least one negative/failure case.

---

## 6. Verification Commands

Run and record results:

1. `[command 1]`
2. `[command 2]`
3. `[command 3]`

Expected:

1. [Expected outcome 1]
2. [Expected outcome 2]

---

## 7. Out of Scope

1. [Explicitly excluded work]
2. [Explicitly excluded work]
3. [Explicitly excluded work]

---

## 8. Completion Checklist

- [ ] Section 1 fully filled and status set correctly.
- [ ] All Non-Negotiable Technical Contract items implemented.
- [ ] Acceptance Test Matrix completed.
- [ ] Verification commands executed successfully.
- [ ] No out-of-scope changes introduced.
