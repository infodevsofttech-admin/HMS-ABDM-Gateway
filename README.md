# HMS-ABDM Gateway (CI4)

A **CodeIgniter 4** middleware server that connects multiple offline Hospital Management System (HMS) instances to **ABDM** (Ayushman Bharat Digital Mission) sandbox / production APIs.

---

## Architecture Overview

```
HMS Instance  ──POST──▶  HMS-ABDM Gateway (CI4)  ──▶  ABDM APIs (HFR / HPR / ABHA / HIU)
                                │
                         SyncQueue (DB)   ← offline / retry support
                         AuditLog  (DB)   ← compliance trail
                         MasterIds (DB)   ← HMS ID ↔ ABDM ID mapping
```

---

## REST API Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| `POST` | `/sync/hospital` | Register hospital in HFR |
| `POST` | `/sync/doctor` | Register doctor in HPR |
| `POST` | `/sync/patient` | Create ABHA ID for patient |
| `POST` | `/sync/records/opd` | Push OPD encounters + prescriptions |
| `POST` | `/sync/records/ipd` | Push IPD admissions + discharge summaries |
| `POST` | `/sync/records/lab` | Push pathology/lab results |
| `POST` | `/sync/records/radiology` | Push imaging/radiology reports |
| `POST` | `/sync/records/pharmacy` | Push pharmacy dispensing records |

All endpoints accept a **JSON body** and return JSON.  
On ABDM connectivity failure the request is automatically **queued** for retry (HTTP 202 response).

---

## Project Structure

```
app/
├── Config/
│   ├── AbdmConfig.php          # ABDM-specific config (base URL, credentials, endpoints)
│   └── Routes.php              # All API route definitions
├── Controllers/
│   ├── ApiController.php       # Shared base: JSON helpers, audit logging, queue fallback
│   ├── Hospital.php            # POST /sync/hospital → HFR registration
│   ├── Doctor.php              # POST /sync/doctor  → HPR registration
│   ├── Patient.php             # POST /sync/patient → ABHA ID creation
│   └── Records.php             # POST /sync/records/* → health-record sync
├── Services/
│   ├── AbdmApiService.php      # Bearer-token management + ABDM HTTP calls
│   ├── FhirMappingService.php  # HMS schema → ABDM FHIR R4 JSON bundles
│   └── SyncQueueService.php    # Queue processing + exponential-back-off retry
├── Models/
│   ├── SyncQueueModel.php      # Pending / retry sync requests
│   ├── AuditLogModel.php       # Compliance audit trail
│   └── MasterIdModel.php       # HFR ID / HPR ID / ABHA ID mappings
└── Database/
    └── Migrations/
        ├── ..._CreateSyncQueueTable.php
        ├── ..._CreateAuditLogsTable.php
        └── ..._CreateMasterIdsTable.php
```

---

## Prerequisites

| Requirement | Version |
|-------------|---------|
| PHP | ≥ 8.1 |
| Composer | ≥ 2.x |
| MySQL / MariaDB | ≥ 5.7 / 10.3 |
| ABDM sandbox account | — |

PHP extensions required: `curl`, `intl`, `mbstring`, `json`, `mysqlnd`

---

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/infodevsofttech-admin/HMS-ABDM-Gateway.git
cd HMS-ABDM-Gateway

# 2. Install PHP dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env and fill in:
#   - database.*
#   - ABDM_CLIENT_ID
#   - ABDM_CLIENT_SECRET
#   - ABDM_BASE_URL (sandbox: https://dev.abdm.gov.in)

# 4. Create the database and run migrations
php spark db:create hms_abdm_gateway   # optional if DB already exists
php spark migrate

# 5. (Development) Start the built-in server
php spark serve
```

---

## Configuration

Edit `.env` (copied from `.env.example`):

```ini
# CodeIgniter environment
CI_ENVIRONMENT = development

# Application base URL
app.baseURL = 'http://localhost:8080'

# Database
database.default.hostname = localhost
database.default.database = hms_abdm_gateway
database.default.username = root
database.default.password = secret
database.default.DBDriver = MySQLi

# ABDM API
ABDM_ENVIRONMENT   = sandbox
ABDM_BASE_URL      = https://dev.abdm.gov.in
ABDM_CLIENT_ID     = your-abdm-client-id
ABDM_CLIENT_SECRET = your-abdm-client-secret
```

---

## Sample Requests

### Register Hospital (HFR)
```bash
curl -X POST http://localhost:8080/sync/hospital \
  -H "Content-Type: application/json" \
  -d '{
    "hms_id": "HOSP-001",
    "name": "City General Hospital",
    "address": "123 Main Street",
    "city": "Mumbai",
    "state": "Maharashtra",
    "phone": "022-12345678",
    "facility_type": "HOSPITAL",
    "ownership_type": "PRIVATE"
  }'
```

### Create ABHA ID (Patient)
```bash
curl -X POST http://localhost:8080/sync/patient \
  -H "Content-Type: application/json" \
  -d '{
    "hms_id": "PAT-001",
    "first_name": "Priya",
    "last_name": "Patel",
    "gender": "female",
    "dob": "1990-03-22",
    "phone": "9876543210",
    "aadhaar": "XXXX-XXXX-1234"
  }'
```

### Push OPD Record
```bash
curl -X POST http://localhost:8080/sync/records/opd \
  -H "Content-Type: application/json" \
  -d '{
    "hms_id": "HOSP-001",
    "patient_hms_id": "PAT-001",
    "encounter_id": "ENC-100",
    "visit_date": "2024-06-15",
    "prescriptions": [
      { "drug_name": "Paracetamol", "dosage": "500mg", "frequency": "TDS" }
    ]
  }'
```

---

## Offline / Retry Mechanism

When an ABDM API call fails (network error, timeout, etc.) the gateway:

1. **Enqueues** the request in the `sync_queue` table (status = `pending`).  
2. Returns HTTP **202 Accepted** with a `queue_id` to the calling HMS.  
3. A background **cron job** (or CI4 spark command) retries failed records with **exponential back-off** (60 s, 120 s, 240 s … up to 1 h) for up to 3 attempts.

Run the queue processor:
```bash
# via PHP CLI / cron
php spark abdm:process-queue
```

---

## Running Tests

```bash
# All tests (unit + integration)
php vendor/bin/phpunit --no-coverage

# Unit tests only
php vendor/bin/phpunit tests/unit --no-coverage
```

---

## Compliance & Audit Logging

Every inbound request and outbound ABDM API call is written to the `audit_logs` table, including:

- `hms_id` — which HMS facility triggered the event  
- `action` — e.g. `register_hospital`, `push_opd`  
- `request_payload` / `response_payload` — full JSON  
- `status_code`, `ip_address`, `user_agent`, `created_at`

---

## License

MIT

