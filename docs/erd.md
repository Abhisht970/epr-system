# EPR System — Entity Relationship Diagram

```mermaid
erDiagram
    USERS ||--o{ AUDIT_LOGS : "actor of"
    USERS ||--o| VENDORS : "owns"
    USERS ||--o| TRAINERS : "is"
    USERS ||--o{ MODEL_HAS_ROLES : "has"
    ROLES ||--o{ MODEL_HAS_ROLES : "assigned to"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "grants"
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "in"

    OTP_CODES }o--|| USERS : "issued for"

    VENDORS ||--o{ VENDOR_CENTERS : has
    VENDORS ||--o{ VENDOR_DOCUMENTS : has
    VENDORS ||--|| VENDOR_KYC : has
    VENDORS ||--o{ VENDOR_INSPECTIONS : undergoes
    VENDORS ||--o{ CANDIDATES : enrolls
    VENDORS ||--o{ INVOICES : raises
    VENDORS ||--o{ TRAINER_ASSIGNMENTS : hosts

    TRAINERS ||--o{ TRAINER_DOCUMENTS : has
    TRAINERS ||--o{ TRAINER_ASSIGNMENTS : assigned
    TRAINER_ASSIGNMENTS }o--|| VENDOR_CENTERS : at
    TRAINER_ASSIGNMENTS }o--|| SCHEMES : under
    TRAINER_ASSIGNMENTS }o--|| COURSES : teaching

    SCHEMES ||--o{ SCHEME_ELIGIBILITY_RULES : has
    SCHEMES ||--o{ SCHEME_PAYMENT_MILESTONES : has
    SCHEMES ||--o{ SCHEME_COURSE : "funds"
    COURSES ||--o{ SCHEME_COURSE : "in"

    CANDIDATES }o--|| VENDORS : "enrolled at"
    CANDIDATES }o--|| VENDOR_CENTERS : "trains at"
    CANDIDATES }o--|| SCHEMES : "under"
    CANDIDATES }o--|| COURSES : "taking"
    CANDIDATES ||--o{ CANDIDATE_DOCUMENTS : has
    CANDIDATES ||--o{ CANDIDATE_ASSESSMENTS : has
    CANDIDATES ||--o{ CANDIDATE_OJT : has
    CANDIDATES ||--o{ CANDIDATE_PLACEMENTS : has
    CANDIDATES ||--o{ CANDIDATE_STATUS_HISTORY : "transitions"

    INVOICES ||--o{ INVOICE_ITEMS : "lines"
    INVOICES ||--o{ PAYMENTS : "settled by"
    INVOICE_ITEMS }o--|| CANDIDATES : "billed for"
    INVOICE_ITEMS }o--|| SCHEME_PAYMENT_MILESTONES : "milestone"

    VERIFICATIONS }o--|| USERS : "submitted by"
    VERIFICATIONS }o--|| USERS : "reviewed by"
    AUDIT_LOGS }o--|| USERS : "actor"

    USERS {
        bigint id PK
        string name
        string email
        string mobile UK
        timestamp mobile_verified_at
        string password
        enum status
        timestamp last_login_at
        string last_login_ip
    }

    OTP_CODES {
        bigint id PK
        string identifier
        enum channel
        enum purpose
        string code_hash
        smallint attempts
        smallint max_attempts
        timestamp expires_at
        timestamp consumed_at
    }

    AUDIT_LOGS {
        bigint id PK
        bigint user_id FK
        string event
        string auditable_type
        bigint auditable_id
        json old_values
        json new_values
        json tags
        string url
        string ip_address
    }

    VERIFICATIONS {
        bigint id PK
        string verifiable_type
        bigint verifiable_id
        enum status
        string stage
        bigint submitted_by FK
        bigint reviewed_by FK
        text remarks
    }

    SCHEMES {
        bigint id PK
        string code UK
        string name
        string sponsor
        date start_date
        date end_date
        enum status
        json default_rules
    }

    SCHEME_ELIGIBILITY_RULES {
        bigint id PK
        bigint scheme_id FK
        string rule_key
        enum operator
        json value
        boolean is_required
    }

    SCHEME_PAYMENT_MILESTONES {
        bigint id PK
        bigint scheme_id FK
        string milestone_key
        string trigger_event
        decimal percentage
        decimal fixed_amount
        enum payable_to
    }

    COURSES {
        bigint id PK
        string code UK
        string name
        string sector
        string qp_code
        tinyint nsqf_level
        smallint duration_hours
    }

    SCHEME_COURSE {
        bigint id PK
        bigint scheme_id FK
        bigint course_id FK
        decimal payout_per_candidate
        smallint max_batch_size
    }

    VENDORS {
        bigint id PK
        bigint user_id FK
        string application_no UK
        string org_name
        enum org_type
        string pan UK
        string gst UK
        text registered_address
        enum status
    }

    VENDOR_CENTERS {
        bigint id PK
        bigint vendor_id FK
        string center_code UK
        string name
        string district
        string state
        string pincode
    }

    VENDOR_DOCUMENTS {
        bigint id PK
        bigint vendor_id FK
        enum document_type
        string file_path
        string hash
        enum status
    }

    VENDOR_KYC {
        bigint id PK
        bigint vendor_id FK,UK
        string bank_name
        string account_holder
        text account_number_encrypted
        string account_number_hash
        string ifsc
        enum status
    }

    VENDOR_INSPECTIONS {
        bigint id PK
        bigint vendor_id FK
        bigint vendor_center_id FK
        bigint scheme_id FK
        bigint inspector_id FK
        enum inspection_type
        date inspection_date
        decimal score
        enum status
        enum recommendation
    }

    TRAINERS {
        bigint id PK
        bigint user_id FK
        string full_name
        string qualification
        string tot_certificate_no UK
        date tot_valid_until
        json sectors
        enum status
    }

    TRAINER_DOCUMENTS {
        bigint id PK
        bigint trainer_id FK
        enum document_type
        string file_path
        enum status
    }

    TRAINER_ASSIGNMENTS {
        bigint id PK
        bigint trainer_id FK
        bigint vendor_id FK
        bigint vendor_center_id FK
        bigint scheme_id FK
        bigint course_id FK
        string batch_no
        date start_date
        date end_date
        enum status
    }

    CANDIDATES {
        bigint id PK
        string application_id UK
        text aadhaar_encrypted
        string aadhaar_hash UK
        string aadhaar_last4
        string full_name
        date dob
        enum gender
        string mobile
        bigint vendor_id FK
        bigint vendor_center_id FK
        bigint scheme_id FK
        bigint course_id FK
        enum status
    }

    CANDIDATE_DOCUMENTS {
        bigint id PK
        bigint candidate_id FK
        enum document_type
        string file_path
        enum status
    }

    CANDIDATE_ASSESSMENTS {
        bigint id PK
        bigint candidate_id FK
        bigint scheme_id FK
        bigint course_id FK
        date assessment_date
        decimal score
        enum result
        string certification_no UK
    }

    CANDIDATE_OJT {
        bigint id PK
        bigint candidate_id FK
        string employer_name
        date ojt_start
        date ojt_end
        enum status
    }

    CANDIDATE_PLACEMENTS {
        bigint id PK
        bigint candidate_id FK
        string employer_name
        string designation
        decimal monthly_salary
        date joining_date
        enum status
    }

    CANDIDATE_STATUS_HISTORY {
        bigint id PK
        bigint candidate_id FK
        string from_status
        string to_status
        bigint changed_by FK
        text reason
        timestamp changed_at
    }

    INVOICES {
        bigint id PK
        string invoice_no UK
        bigint vendor_id FK
        bigint scheme_id FK
        date period_start
        date period_end
        decimal gross_amount
        decimal net_amount
        enum status
    }

    INVOICE_ITEMS {
        bigint id PK
        bigint invoice_id FK
        bigint candidate_id FK
        bigint milestone_id FK
        decimal unit_price
        decimal amount
    }

    PAYMENTS {
        bigint id PK
        bigint invoice_id FK
        string transaction_ref UK
        decimal amount
        enum mode
        string utr_no
        enum status
        timestamp processed_at
    }

    ROLES {
        bigint id PK
        string name
        string guard_name
    }

    PERMISSIONS {
        bigint id PK
        string name
        string guard_name
    }

    MODEL_HAS_ROLES {
        bigint role_id FK
        string model_type
        bigint model_id
    }

    ROLE_HAS_PERMISSIONS {
        bigint role_id FK
        bigint permission_id FK
    }
```

## Lifecycle status enums

| Model | States |
|-------|--------|
| `vendors.status` | `draft → submitted → pending_verification → inspection_scheduled → inspection_completed → approved → kyc_pending → active` (or `rejected`, `inactive`, `suspended`) |
| `vendor_centers.status` | `draft → pending_verification → active` (or `inactive`, `rejected`) |
| `trainers.status` | `draft → pending_verification → verified → active` (or `rejected`, `inactive`) |
| `candidates.status` | `registered → training → assessed → certified → placed` (or `dropout`) — enforced by `App\Services\CandidateLifecycle` |
| `verifications.status` | `pending → verified` / `rejected` / `info_required` — enforced by `HasVerificationStatus` trait |
| `invoices.status` | `draft → submitted → under_review → approved → paid` (or `rejected`) |
| `payments.status` | `pending → completed` / `failed` / `reversed` |
