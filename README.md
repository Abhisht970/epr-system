# EPR System

EPR (Empanelled Partner Registration) System for skill-development training partners. Built on **Laravel 13 + MySQL 8** with embedded compliance & audit controls at every workflow step.

This repository contains the database foundation: migrations, Eloquent models, traits, services, and tests that bake the six critical control families directly into the data layer. API and UI are deliberately deferred to subsequent PRs.

---

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate

# Create the MySQL database (one-time)
mysql -uroot -e "CREATE DATABASE epr_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -uroot -e "CREATE USER 'epr'@'localhost' IDENTIFIED BY 'epr_dev_password';"
mysql -uroot -e "GRANT ALL ON epr_system.* TO 'epr'@'localhost'; FLUSH PRIVILEGES;"

php artisan migrate:fresh --seed
php artisan test
```

Default admin (created by `AdminUserSeeder`):

| Mobile        | Password    | Role  |
|---------------|-------------|-------|
| `9999999999`  | `admin@1234`| admin |

A demo scheme `PMKVY-DEMO` is created with eligibility rules (age 18-35), four payout milestones (registration / training_complete / certification / placement), and one course `IT-ITeS Customer Service`.

---

## Embedded controls — where each lives in code

| # | Control family | Implementation |
|---|----------------|----------------|
| 1 | **Role-Based Access Control (RBAC)** | `spatie/laravel-permission`. Roles: `admin`, `vendor`, `trainer`, `candidate`, `finance`, `inspector`. Permissions seeded by [`RoleSeeder`](database/seeders/RoleSeeder.php). User model uses `HasRoles`. |
| 2 | **Audit Trail (everywhere)** | [`App\Models\Concerns\Auditable`](app/Models/Concerns/Auditable.php) trait + [`AuditLogObserver`](app/Observers/AuditLogObserver.php). Every `created` / `updated` / `deleted` event on any auditable model writes a row to `audit_logs` with actor, IP, URL, and JSON diff. Sensitive fields stripped via `auditExclude()` (e.g. `aadhaar_encrypted`, `account_number_encrypted`, `password`). |
| 3 | **Verification Workflow (standardised)** | [`HasVerificationStatus`](app/Models/Concerns/HasVerificationStatus.php) trait + polymorphic [`Verification`](app/Models/Verification.php) model. Allowed transitions enforced at runtime: `pending → verified | rejected | info_required`, `info_required → pending`. Rejection / info-required calls **require** non-empty remarks. |
| 4 | **Scheme Logic Engine** | [`App\Services\SchemeEngine`](app/Services/SchemeEngine.php). Declarative eligibility rules (`scheme_eligibility_rules` table) evaluated against candidate attributes. Payouts derived from `scheme_payment_milestones` × `scheme_course.payout_per_candidate`. `buildDraftInvoice()` automatically creates one `invoice_items` row per (candidate, milestone) pair, refusing duplicates. |
| 5 | **Data Validation & Duplication Control** | • Custom rules: [`AadhaarRule`](app/Rules/AadhaarRule.php) (Verhoeff checksum), [`PanRule`](app/Rules/PanRule.php), [`GstRule`](app/Rules/GstRule.php), [`MobileRule`](app/Rules/MobileRule.php). <br>• Unique indexes: `users.mobile`, `users.email`, `vendors.pan`, `vendors.gst`, `vendors.application_no`, `candidates.aadhaar_hash`, `candidates.application_id`, `invoices.invoice_no`, `payments.transaction_ref`, `trainers.tot_certificate_no`, `candidate_assessments.certification_no`. <br>• Composite unique on `invoice_items (invoice_id, candidate_id, milestone_id)` to block duplicate billing. |
| 6 | **Compliance Layer** | • Aadhaar handled exclusively via [`App\Support\AadhaarVault`](app/Support/AadhaarVault.php): AES-256 ciphertext + HMAC-SHA256 dedup hash + last-4 for masked display (`XXXX-XXXX-1234`). Plaintext **never** persisted. <br>• Bank account numbers in `vendor_kyc` encrypted at-rest. <br>• OTP codes stored only as bcrypt hashes (see [`OtpCode`](app/Models/OtpCode.php)). <br>• `Vendor` model boot guard: status cannot move to `active` until KYC is `verified`. <br>• Soft deletes preserve history on every domain entity. |

---

## Lifecycle invariants (enforced in code, not just docs)

- **Candidate status progression** — `App\Services\CandidateLifecycle::transition()` is the single entry point. It (a) validates against `Candidate::ALLOWED_TRANSITIONS`, (b) writes a `candidate_status_history` row inside the same DB transaction, and (c) bumps `status_changed_at`.
- **Vendor activation** — `Vendor::saving` boot listener throws if KYC isn't `verified` when status is set to `active`.
- **Aadhaar dedup** — set via the `aadhaar` mutator, which fills `aadhaar_encrypted`, `aadhaar_hash` (unique), and `aadhaar_last4`. Inserting a duplicate Aadhaar raises a `QueryException` (proven by [`CandidateAadhaarDedupTest`](tests/Feature/CandidateAadhaarDedupTest.php)).
- **Verification remarks** — `HasVerificationStatus::markRejected()` and `requestMoreInfo()` throw `InvalidArgumentException` on empty remarks (proven by [`VerificationStateMachineTest`](tests/Feature/VerificationStateMachineTest.php)).

---

## Module overview

```
Vendor lifecycle  →  vendors → vendor_centers → vendor_documents → vendor_kyc → vendor_inspections
Trainer           →  trainers → trainer_documents → trainer_assignments
Candidate         →  candidates → {candidate_documents, candidate_assessments, candidate_ojt,
                                   candidate_placements, candidate_status_history}
Scheme engine     →  schemes → scheme_eligibility_rules + scheme_payment_milestones
                  →  schemes ↔ courses (via scheme_course pivot, with per-candidate payout)
Finance           →  invoices → invoice_items → payments
System-wide       →  users / spatie permission tables / otp_codes / audit_logs / verifications
```

Full ERD: [`docs/erd.md`](docs/erd.md).

---

## Test suite

```bash
php artisan test
```

Run on a fresh checkout, this passes 27 tests across 6 files, covering:

- `AadhaarVaultTest` — encrypt/decrypt roundtrip, deterministic hash, masking, Verhoeff checksum.
- `RulesTest` — PAN / GST / Mobile / Aadhaar format rules.
- `CandidateAadhaarDedupTest` — unique-index enforcement, hidden serialization, ciphertext at-rest.
- `AuditLogTest` — created / updated / deleted events emit rows with diff + tags.
- `VerificationStateMachineTest` — legal/illegal transitions and mandatory remarks.
- `CandidateLifecycleTest` — full happy-path progression and skipping-stage rejection.
- `SchemeEngineTest` — eligibility evaluation and percentage / fixed-amount payout calculation.

---

## What's next

This PR is intentionally scoped to the database layer. Subsequent PRs will add:

1. **API layer** — Sanctum auth, OTP service, controllers, form requests, resource transformers, policies.
2. **Admin / vendor / trainer / candidate dashboards** — Blade + Livewire screens (the chosen frontend stack).
3. **Document upload pipeline** — virus scan + content-hash dedup + signed URLs.
4. **Reporting / MIS** — admin dashboard charts and exports.
5. **Notifications** — SMS/email/WhatsApp on key state transitions.
