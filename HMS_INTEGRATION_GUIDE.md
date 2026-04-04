# HMS-ABDM Gateway — Integration Guide for Local HMS

**Version:** 1.0  
**Audience:** Developers and system administrators of the local HMS (`devsofttech_hms_ci4`)  
**Gateway Repository:** https://github.com/infodevsofttech-admin/HMS-ABDM-Gateway

---

## Overview

The **HMS-ABDM Gateway** is a middleware server that sits between your local Hospital Management System (HMS) and the ABDM (Ayushman Bharat Digital Mission) APIs. Instead of calling ABDM directly—which requires live sandbox/production credentials and internet connectivity—your HMS calls the **gateway** over your local network or intranet. The gateway handles:

- ABDM authentication (client credentials, token refresh)
- FHIR R4 conversion of your HMS records
- Offline queuing with automatic retry when ABDM is unreachable
- Compliance audit logging

```
Local HMS (devsofttech_hms_ci4)
         │
         │  POST /sync/* + X-API-Key header
         ▼
  HMS-ABDM Gateway  ──────────►  ABDM APIs (HFR / HPR / ABHA / Health Records)
         │                           (sandbox or production)
         └── offline queue ──────────► retry on next cron run
```

> **While waiting for ABDM Sandbox credentials:** You can register your hospital in the gateway admin panel and integrate the connector library immediately. All requests will be automatically queued and pushed to ABDM once credentials are configured.

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Step 1 — Obtain Your API Key from the Gateway Admin Panel](#2-step-1--obtain-your-api-key-from-the-gateway-admin-panel)
3. [Step 2 — Configure the Local HMS](#3-step-2--configure-the-local-hms)
4. [Step 3 — Install the Connector Library](#4-step-3--install-the-connector-library)
5. [API Reference](#5-api-reference)
   - [Register Hospital (HFR)](#51-register-hospital-hfr)
   - [Register Doctor (HPR)](#52-register-doctor-hpr)
   - [Create Patient ABHA ID](#53-create-patient-abha-id)
   - [Push OPD Record](#54-push-opd-record)
   - [Push IPD Record](#55-push-ipd-record)
   - [Push Lab Report](#56-push-lab-report)
   - [Push Radiology Report](#57-push-radiology-report)
   - [Push Pharmacy Record](#58-push-pharmacy-record)
6. [Response Handling](#6-response-handling)
7. [Integration Examples for devsofttech_hms_ci4](#7-integration-examples-for-devsofttech_hms_ci4)
8. [Error Codes Reference](#8-error-codes-reference)
9. [Testing the Connection](#9-testing-the-connection)
10. [Troubleshooting](#10-troubleshooting)

---

## 1. Prerequisites

| Requirement | Details |
|-------------|---------|
| Gateway server running | Deployed on CentOS 7 / Docker / cloud (see gateway README) |
| PHP 7.4+ on HMS server | The connector uses only built-in cURL extension |
| `curl` PHP extension | Run `php -m \| grep curl` to verify |
| Network access to gateway | HMS server must be able to reach the gateway URL on port 80/443 |

---

## 2. Step 1 — Obtain Your API Key from the Gateway Admin Panel

Each HMS facility must be registered in the gateway to receive a unique API key. This key authenticates every sync request.

### 2.1 Login to the Admin Panel

Open the gateway admin panel in a browser:

```
http://<gateway-server>/admin/login
```

Enter the `GATEWAY_ADMIN_TOKEN` (set by the gateway administrator in their `.env` file).

### 2.2 Register Your Hospital

1. Click **+ Register New Hospital**
2. Fill in the registration form:

| Field | Example | Notes |
|-------|---------|-------|
| **Hospital Name** | `City General Hospital` | Display name |
| **HMS ID** | `HOSP-001` | **Must match** the `hms_id` you will send in every sync request |
| **State** | `Maharashtra` | Optional |
| **District** | `Pune` | Optional |
| **Contact Email** | `admin@cityhospital.in` | Optional |
| **Contact Phone** | `9876543210` | Optional |

3. Click **Register & Generate API Key**

### 2.3 Copy Your API Key

After registration, a **64-character API key** is displayed. **Copy it immediately — it is shown only once.**

```
API Key: a3f9d2c1e4b78f0a1c3d2e5b9a7f4c8d6e2b0a1d3c5e7f9a3f9d2c1e4b78f0a
```

> **Security Note:** Store this key securely. Treat it like a password. If it is compromised, use the gateway admin panel to regenerate a new key immediately.

---

## 3. Step 2 — Configure the Local HMS

Add two entries to the local HMS `.env` file:

```ini
# HMS-ABDM Gateway Configuration
# Replace with your actual gateway URL and API key

ABDM_GATEWAY_URL     = http://192.168.1.100          # Your gateway server IP/hostname
ABDM_GATEWAY_API_KEY = a3f9d2c1e4b78f0a1c3d...       # Key copied from the admin panel
```

**Examples for different deployment scenarios:**

```ini
# Local network (gateway on same LAN)
ABDM_GATEWAY_URL = http://192.168.1.100

# Same server (gateway and HMS on same machine)
ABDM_GATEWAY_URL = http://localhost

# Cloud-hosted gateway
ABDM_GATEWAY_URL = https://hms-abdm-gateway.yourcompany.in
```

---

## 4. Step 3 — Install the Connector Library

The connector is a single PHP file that handles all HTTP communication with the gateway.

### Option A — Copy directly (recommended)

```bash
# From the gateway project directory:
cp app/Libraries/HmsGatewayConnector.php \
   /path/to/devsofttech_hms_ci4/app/Libraries/

# Or download directly:
wget https://raw.githubusercontent.com/infodevsofttech-admin/HMS-ABDM-Gateway/copilot/hms-abdm-gateway-implementation/app/Libraries/HmsGatewayConnector.php \
   -O /path/to/devsofttech_hms_ci4/app/Libraries/HmsGatewayConnector.php
```

### Option B — Manual placement

Download `app/Libraries/HmsGatewayConnector.php` from the gateway repository and place it at:

```
devsofttech_hms_ci4/
└── app/
    └── Libraries/
        └── HmsGatewayConnector.php   ← place here
```

### Verify Installation

```php
// Quick test in any HMS controller:
$gw = new \App\Libraries\HmsGatewayConnector();
// No exception = connector is configured correctly
```

---

## 5. API Reference

All endpoints accept `Content-Type: application/json` and return JSON. Every request must include:

```
X-API-Key: <your-64-char-api-key>
```

The connector adds this header automatically. The examples below show both the **connector method** and the **raw curl** equivalent.

---

### 5.1 Register Hospital (HFR)

Registers the facility in ABDM's Health Facility Registry. Call this once when setting up ABDM integration.

**Endpoint:** `POST /sync/hospital`

**Required fields:** `hms_id`, `name`, `state`

#### Request Payload

```json
{
  "hms_id":        "HOSP-001",
  "name":          "City General Hospital",
  "address":       "123 MG Road",
  "city":          "Pune",
  "state":         "Maharashtra",
  "pincode":       "411001",
  "phone":         "020-12345678",
  "facility_type": "HOSPITAL",
  "ownership_type":"PRIVATE"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Your internal hospital ID — must match what you registered in the admin panel |
| `name` | string | ✅ | Hospital name |
| `state` | string | ✅ | State name (e.g. `Maharashtra`) |
| `address` | string | — | Full street address |
| `city` | string | — | City name |
| `pincode` | string | — | PIN code |
| `phone` | string | — | Contact number |
| `facility_type` | string | — | `HOSPITAL`, `CLINIC`, `NURSING_HOME`, `PHC` (default: `HOSPITAL`) |
| `ownership_type` | string | — | `PRIVATE`, `GOVERNMENT`, `TRUST` (default: `PRIVATE`) |

#### PHP — Using Connector

```php
$gw    = new \App\Libraries\HmsGatewayConnector();
$result = $gw->syncHospital([
    'hms_id'        => 'HOSP-001',
    'name'          => 'City General Hospital',
    'address'       => '123 MG Road',
    'city'          => 'Pune',
    'state'         => 'Maharashtra',
    'pincode'       => '411001',
    'phone'         => '020-12345678',
    'facility_type' => 'HOSPITAL',
    'ownership_type'=> 'PRIVATE',
]);
```

#### cURL Equivalent

```bash
curl -X POST http://gateway-server/sync/hospital \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "hms_id": "HOSP-001",
    "name": "City General Hospital",
    "state": "Maharashtra"
  }'
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "hms_id": "HOSP-001",
    "hfr_id": "HFR-MH-12345",
    "message": "Hospital registered successfully in HFR."
  }
}
```

#### Queued Response (202 — ABDM unreachable)

```json
{
  "success": true,
  "status_code": 202,
  "data": {
    "hms_id": "HOSP-001",
    "queue_id": 1,
    "message": "Hospital registration queued for retry due to ABDM connectivity issue."
  }
}
```

---

### 5.2 Register Doctor (HPR)

Registers a doctor or health professional in ABDM's Health Professional Registry.

**Endpoint:** `POST /sync/doctor`

**Required fields:** `hms_id`, `first_name`, `last_name`, `registration_number`

#### Request Payload

```json
{
  "hms_id":              "DOC-101",
  "first_name":          "Priya",
  "last_name":           "Sharma",
  "gender":              "female",
  "dob":                 "1980-05-15",
  "qualification":       "MBBS, MD",
  "registration_number": "MH-12345",
  "council":             "Maharashtra Medical Council",
  "speciality":          "General Medicine"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Doctor's internal HMS ID |
| `first_name` | string | ✅ | First name |
| `last_name` | string | ✅ | Last name |
| `registration_number` | string | ✅ | Medical council registration number |
| `gender` | string | — | `male` / `female` / `other` |
| `dob` | string | — | Date of birth `YYYY-MM-DD` |
| `qualification` | string | — | Degrees (e.g. `MBBS, MD`) |
| `council` | string | — | Issuing medical council |
| `speciality` | string | — | Medical speciality |

#### PHP — Using Connector

```php
$gw     = new \App\Libraries\HmsGatewayConnector();
$result = $gw->syncDoctor([
    'hms_id'              => 'DOC-101',
    'first_name'          => 'Priya',
    'last_name'           => 'Sharma',
    'gender'              => 'female',
    'dob'                 => '1980-05-15',
    'qualification'       => 'MBBS, MD',
    'registration_number' => 'MH-12345',
    'council'             => 'Maharashtra Medical Council',
    'speciality'          => 'General Medicine',
]);
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "hms_id": "DOC-101",
    "hpr_id": "HPR-9876543210",
    "message": "Doctor registered successfully in HPR."
  }
}
```

---

### 5.3 Create Patient ABHA ID

Creates an Ayushman Bharat Health Account (ABHA) ID for a patient.

**Endpoint:** `POST /sync/patient`

**Required fields:** `hms_id`, `first_name`, `last_name`, `dob`, `gender`

#### Request Payload

```json
{
  "hms_id":     "PAT-501",
  "first_name": "Ramesh",
  "last_name":  "Kumar",
  "gender":     "male",
  "dob":        "1985-06-15",
  "phone":      "9876543210",
  "aadhaar":    "1234-5678-9012",
  "address":    "45 Park Road",
  "city":       "Mumbai",
  "state":      "Maharashtra"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Patient's internal HMS ID |
| `first_name` | string | ✅ | First name |
| `last_name` | string | ✅ | Last name |
| `dob` | string | ✅ | Date of birth `YYYY-MM-DD` |
| `gender` | string | ✅ | `male` / `female` / `other` |
| `phone` | string | — | Mobile number (for OTP) |
| `aadhaar` | string | — | Aadhaar number (for Aadhaar-based ABHA creation) |
| `address` | string | — | Street address |
| `city` | string | — | City |
| `state` | string | — | State |

#### PHP — Using Connector

```php
$gw     = new \App\Libraries\HmsGatewayConnector();
$result = $gw->syncPatient([
    'hms_id'     => 'PAT-501',
    'first_name' => 'Ramesh',
    'last_name'  => 'Kumar',
    'gender'     => 'male',
    'dob'        => '1985-06-15',
    'phone'      => '9876543210',
    'aadhaar'    => '1234-5678-9012',
    'state'      => 'Maharashtra',
]);
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "hms_id":  "PAT-501",
    "abha_id": "91-1234-5678-9012",
    "message": "ABHA ID created successfully."
  }
}
```

---

### 5.4 Push OPD Record

Pushes an outpatient visit with diagnoses and prescriptions.

**Endpoint:** `POST /sync/records/opd`

**Required fields:** `hms_id`, `patient_hms_id`, `visit_date`

#### Request Payload

```json
{
  "hms_id":          "HOSP-001",
  "patient_hms_id":  "PAT-501",
  "doctor_hms_id":   "DOC-101",
  "visit_date":      "2024-06-01",
  "chief_complaint": "Fever and headache for 3 days",
  "diagnosis": [
    { "code": "A92.9", "display": "Dengue fever" }
  ],
  "prescription": [
    {
      "drug":     "Paracetamol 500mg",
      "dose":     "1 tablet",
      "frequency":"TDS",
      "duration": "5 days"
    }
  ],
  "vitals": {
    "temperature": "102",
    "bp":          "120/80",
    "pulse":       "92",
    "weight":      "70"
  }
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Facility HMS ID |
| `patient_hms_id` | string | ✅ | Patient's internal HMS ID |
| `visit_date` | string | ✅ | Visit date `YYYY-MM-DD` |
| `doctor_hms_id` | string | — | Attending doctor's HMS ID |
| `chief_complaint` | string | — | Patient's complaint |
| `diagnosis` | array | — | Array of `{code, display}` objects (ICD-10) |
| `prescription` | array | — | Array of `{drug, dose, frequency, duration}` objects |
| `vitals` | object | — | `{temperature, bp, pulse, weight, height, spo2}` |

#### PHP — Using Connector

```php
$gw     = new \App\Libraries\HmsGatewayConnector();
$result = $gw->syncRecord('opd', [
    'hms_id'          => 'HOSP-001',
    'patient_hms_id'  => 'PAT-501',
    'doctor_hms_id'   => 'DOC-101',
    'visit_date'      => '2024-06-01',
    'chief_complaint' => 'Fever and headache',
    'diagnosis'       => [
        ['code' => 'A92.9', 'display' => 'Dengue fever'],
    ],
    'prescription'    => [
        ['drug' => 'Paracetamol 500mg', 'dose' => '1 tablet', 'frequency' => 'TDS', 'duration' => '5 days'],
    ],
]);
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "hms_id":   "HOSP-001",
    "message":  "OPD record pushed successfully to ABDM.",
    "abdm_ref": "TXN-20240601-ABCD1234"
  }
}
```

---

### 5.5 Push IPD Record

Pushes an inpatient admission with procedures and discharge summary.

**Endpoint:** `POST /sync/records/ipd`

**Required fields:** `hms_id`, `patient_hms_id`, `admission_date`

#### Request Payload

```json
{
  "hms_id":           "HOSP-001",
  "patient_hms_id":   "PAT-501",
  "doctor_hms_id":    "DOC-101",
  "admission_date":   "2024-06-01",
  "discharge_date":   "2024-06-07",
  "ward":             "General Ward",
  "bed_number":       "G-12",
  "admission_reason": "Dengue fever with thrombocytopenia",
  "diagnosis": [
    { "code": "A97.1", "display": "Severe dengue" }
  ],
  "procedures": [
    { "code": "99213", "display": "IV fluid administration" }
  ],
  "discharge_summary": "Patient recovered, discharged with advice",
  "discharge_condition": "stable"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Facility HMS ID |
| `patient_hms_id` | string | ✅ | Patient's internal HMS ID |
| `admission_date` | string | ✅ | Admission date `YYYY-MM-DD` |
| `discharge_date` | string | — | Discharge date `YYYY-MM-DD` |
| `ward` | string | — | Ward name |
| `bed_number` | string | — | Bed number |
| `admission_reason` | string | — | Reason for admission |
| `diagnosis` | array | — | Array of `{code, display}` |
| `procedures` | array | — | Array of `{code, display}` |
| `discharge_summary` | string | — | Discharge notes |
| `discharge_condition` | string | — | `stable`, `improved`, `critical` |

#### PHP — Using Connector

```php
$result = $gw->syncRecord('ipd', [
    'hms_id'           => 'HOSP-001',
    'patient_hms_id'   => 'PAT-501',
    'admission_date'   => '2024-06-01',
    'discharge_date'   => '2024-06-07',
    'admission_reason' => 'Dengue fever',
    'discharge_summary'=> 'Patient recovered',
]);
```

---

### 5.6 Push Lab Report

Pushes pathology/laboratory test results.

**Endpoint:** `POST /sync/records/lab`

**Required fields:** `hms_id`, `patient_hms_id`, `report_id`, `report_date`

#### Request Payload

```json
{
  "hms_id":         "HOSP-001",
  "patient_hms_id": "PAT-501",
  "report_id":      "LAB-2024-1001",
  "report_date":    "2024-06-02",
  "lab_name":       "City Hospital Pathology Lab",
  "doctor_hms_id":  "DOC-101",
  "tests": [
    {
      "name":     "Complete Blood Count",
      "code":     "58410-2",
      "results": [
        { "parameter": "Haemoglobin", "value": "11.2", "unit": "g/dL",  "range": "12-17", "flag": "L" },
        { "parameter": "Platelet Count","value": "80000", "unit": "/µL", "range": "150000-400000", "flag": "L" }
      ]
    }
  ]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Facility HMS ID |
| `patient_hms_id` | string | ✅ | Patient's internal HMS ID |
| `report_id` | string | ✅ | Lab's internal report number |
| `report_date` | string | ✅ | Report date `YYYY-MM-DD` |
| `lab_name` | string | — | Name of the laboratory |
| `tests` | array | — | Array of test objects with `name`, `code`, `results` |

#### PHP — Using Connector

```php
$result = $gw->syncRecord('lab', [
    'hms_id'         => 'HOSP-001',
    'patient_hms_id' => 'PAT-501',
    'report_id'      => 'LAB-2024-1001',
    'report_date'    => '2024-06-02',
    'tests'          => [
        [
            'name'    => 'Complete Blood Count',
            'results' => [
                ['parameter' => 'Haemoglobin', 'value' => '11.2', 'unit' => 'g/dL', 'flag' => 'L'],
            ],
        ],
    ],
]);
```

---

### 5.7 Push Radiology Report

Pushes imaging reports (X-ray, CT, MRI, Ultrasound, etc.).

**Endpoint:** `POST /sync/records/radiology`

**Required fields:** `hms_id`, `patient_hms_id`, `report_id`, `report_date`, `modality`

#### Request Payload

```json
{
  "hms_id":         "HOSP-001",
  "patient_hms_id": "PAT-501",
  "report_id":      "RAD-2024-501",
  "report_date":    "2024-06-03",
  "modality":       "X-Ray",
  "body_part":      "Chest",
  "radiologist":    "Dr. Anil Mehta",
  "findings":       "No active consolidation. Bilateral lung fields clear.",
  "impression":     "Normal chest X-ray",
  "image_url":      "https://pacs.hospital.in/rad-2024-501"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Facility HMS ID |
| `patient_hms_id` | string | ✅ | Patient's internal HMS ID |
| `report_id` | string | ✅ | Radiology report number |
| `report_date` | string | ✅ | Report date `YYYY-MM-DD` |
| `modality` | string | ✅ | `X-Ray`, `CT`, `MRI`, `Ultrasound`, `PET`, `Mammography` |
| `body_part` | string | — | Body part imaged |
| `radiologist` | string | — | Radiologist name |
| `findings` | string | — | Detailed radiological findings |
| `impression` | string | — | Summary/impression |
| `image_url` | string | — | URL to DICOM or image (PACS link) |

#### PHP — Using Connector

```php
$result = $gw->syncRecord('radiology', [
    'hms_id'         => 'HOSP-001',
    'patient_hms_id' => 'PAT-501',
    'report_id'      => 'RAD-2024-501',
    'report_date'    => '2024-06-03',
    'modality'       => 'X-Ray',
    'body_part'      => 'Chest',
    'findings'       => 'No active consolidation.',
    'impression'     => 'Normal chest X-ray',
]);
```

---

### 5.8 Push Pharmacy Record

Pushes prescription dispensing records from pharmacy.

**Endpoint:** `POST /sync/records/pharmacy`

**Required fields:** `hms_id`, `patient_hms_id`, `dispensed_date`

#### Request Payload

```json
{
  "hms_id":         "HOSP-001",
  "patient_hms_id": "PAT-501",
  "dispensed_date": "2024-06-01",
  "bill_number":    "PH-2024-8901",
  "pharmacist":     "Suresh Jain",
  "items": [
    {
      "drug":       "Paracetamol 500mg",
      "drug_code":  "12345",
      "quantity":   15,
      "unit":       "tablet",
      "dose":       "1 tablet",
      "frequency":  "TDS",
      "duration":   "5 days",
      "unit_price": 2.50,
      "total":      37.50
    },
    {
      "drug":       "ORS Sachet",
      "quantity":   10,
      "unit":       "sachet",
      "unit_price": 5.00,
      "total":      50.00
    }
  ]
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `hms_id` | string | ✅ | Facility HMS ID |
| `patient_hms_id` | string | ✅ | Patient's internal HMS ID |
| `dispensed_date` | string | ✅ | Dispense date `YYYY-MM-DD` |
| `bill_number` | string | — | Pharmacy bill/invoice number |
| `pharmacist` | string | — | Dispensing pharmacist name |
| `items` | array | — | Array of dispensed items |

#### PHP — Using Connector

```php
$result = $gw->syncRecord('pharmacy', [
    'hms_id'         => 'HOSP-001',
    'patient_hms_id' => 'PAT-501',
    'dispensed_date' => '2024-06-01',
    'bill_number'    => 'PH-2024-8901',
    'items'          => [
        ['drug' => 'Paracetamol 500mg', 'quantity' => 15, 'unit' => 'tablet', 'dose' => '1 tablet'],
    ],
]);
```

---

## 6. Response Handling

All gateway responses follow a consistent structure. The connector returns a PHP array with these keys:

### 6.1 Response Structure

```php
[
    'success'     => true | false,
    'status_code' => 200 | 202 | 400 | 401 | 422 | 500,
    'data'        => [...],    // present on success (200)
    'queue_id'    => 42,       // present on queued (202)
    'message'     => '...',    // present on error (4xx, 5xx) or queued (202)
    'errors'      => [...],    // present on validation error (422)
]
```

### 6.2 Recommended Handling Pattern

```php
$gw     = new \App\Libraries\HmsGatewayConnector();
$result = $gw->syncRecord('opd', $opdData);

switch ($result['status_code']) {
    case 200:
        // ✅ Record pushed to ABDM immediately
        $abdmRef = $result['data']['abdm_ref'] ?? null;
        // Store $abdmRef in your HMS database for reference
        break;

    case 202:
        // ⏳ ABDM unreachable — record queued for retry
        $queueId = $result['data']['queue_id'];
        // Optionally log: "Queued with ID $queueId — will retry automatically"
        break;

    case 401:
        // 🔑 API key invalid or missing — check ABDM_GATEWAY_API_KEY in .env
        log_message('error', 'ABDM Gateway: Invalid API key');
        break;

    case 422:
        // ⚠️ Missing required fields in your payload
        $errors = $result['errors'] ?? [];
        log_message('error', 'ABDM Gateway validation: ' . json_encode($errors));
        break;

    case 0:
        // 🔌 Cannot reach gateway — check network / ABDM_GATEWAY_URL
        log_message('error', 'Cannot reach ABDM Gateway: ' . $result['message']);
        break;

    default:
        // ❌ Unexpected error
        log_message('error', 'ABDM Gateway error ' . $result['status_code'] . ': ' . ($result['message'] ?? ''));
}
```

---

## 7. Integration Examples for devsofttech_hms_ci4

### 7.1 Service Class Pattern (Recommended)

Create a dedicated service class in the local HMS:

```php
<?php
// app/Services/AbdmSyncService.php in devsofttech_hms_ci4

namespace App\Services;

use App\Libraries\HmsGatewayConnector;

class AbdmSyncService
{
    private HmsGatewayConnector $gw;
    private string $hmsId;

    public function __construct()
    {
        $this->gw    = new HmsGatewayConnector();
        $this->hmsId = env('HMS_FACILITY_ID', 'HOSP-001');
    }

    /**
     * Sync a patient to ABDM after registration in the HMS.
     */
    public function syncNewPatient(array $patient): ?string
    {
        $result = $this->gw->syncPatient([
            'hms_id'     => 'PAT-' . $patient['id'],
            'first_name' => $patient['first_name'],
            'last_name'  => $patient['last_name'],
            'dob'        => $patient['dob'],
            'gender'     => $patient['gender'],
            'phone'      => $patient['mobile'],
            'aadhaar'    => $patient['aadhaar'] ?? '',
            'state'      => $patient['state'] ?? '',
        ]);

        if ($result['status_code'] === 200) {
            return $result['data']['abha_id'] ?? null;
        }

        // 202 = queued — no ABHA ID yet but will be created on retry
        return null;
    }

    /**
     * Sync an OPD visit after saving in the HMS.
     */
    public function syncOpdVisit(array $opd): void
    {
        $this->gw->syncRecord('opd', [
            'hms_id'          => $this->hmsId,
            'patient_hms_id'  => 'PAT-' . $opd['patient_id'],
            'doctor_hms_id'   => 'DOC-' . $opd['doctor_id'],
            'visit_date'      => $opd['visit_date'],
            'chief_complaint' => $opd['complaint'],
            'diagnosis'       => $opd['diagnosis'] ?? [],
            'prescription'    => $opd['prescription'] ?? [],
        ]);
    }
}
```

### 7.2 Hooking into Existing HMS Controllers

Add ABDM sync as a post-save step in existing HMS controllers — it will not block the HMS workflow even if the gateway is unreachable:

```php
<?php
// app/Controllers/Patient.php in devsofttech_hms_ci4

use App\Services\AbdmSyncService;

class Patient extends BaseController
{
    public function save()
    {
        // --- Existing HMS patient save logic ---
        $patientData = $this->request->getPost();
        $patientId   = $this->patientModel->insert($patientData);

        // --- Non-blocking ABDM sync ---
        try {
            $abdm   = new AbdmSyncService();
            $abhaId = $abdm->syncNewPatient(array_merge($patientData, ['id' => $patientId]));
            if ($abhaId) {
                $this->patientModel->update($patientId, ['abha_id' => $abhaId]);
            }
        } catch (\Exception $e) {
            // Log but do not fail the HMS save
            log_message('error', 'ABDM sync skipped: ' . $e->getMessage());
        }

        return redirect()->to('patients')->with('success', 'Patient saved.');
    }
}
```

### 7.3 Direct Connector Usage (without service class)

For simple, one-off calls:

```php
<?php
use App\Libraries\HmsGatewayConnector;

// In any HMS controller method:
$gw = new HmsGatewayConnector();

// Register hospital (typically done once at setup)
$gw->syncHospital([
    'hms_id' => 'HOSP-001',
    'name'   => 'City General Hospital',
    'state'  => 'Maharashtra',
]);

// Register a doctor
$gw->syncDoctor([
    'hms_id'              => 'DOC-' . $doctorId,
    'first_name'          => $doctor['first_name'],
    'last_name'           => $doctor['last_name'],
    'registration_number' => $doctor['reg_no'],
    'council'             => $doctor['council'],
    'speciality'          => $doctor['speciality'],
]);

// Push a lab report
$gw->syncRecord('lab', [
    'hms_id'         => 'HOSP-001',
    'patient_hms_id' => 'PAT-' . $patientId,
    'report_id'      => $report['lab_no'],
    'report_date'    => $report['date'],
    'tests'          => $report['test_results'],
]);
```

---

## 8. Error Codes Reference

| HTTP Code | Meaning | Action |
|-----------|---------|--------|
| `200` | Success — record pushed to ABDM | Store `abdm_ref` / `hfr_id` / `hpr_id` / `abha_id` from response |
| `202` | Queued — ABDM unreachable, will retry | Log `queue_id`; no action needed, gateway retries automatically |
| `400` | Bad request | Check your JSON payload structure |
| `401` | Invalid or missing API key | Verify `ABDM_GATEWAY_API_KEY` in `.env` matches what's in the gateway admin panel |
| `422` | Validation error — required field missing | Check `errors` array in response for missing field names |
| `500` | Gateway internal error | Contact gateway administrator |
| `0` | Network error — cannot reach gateway | Check `ABDM_GATEWAY_URL` in `.env` and network connectivity |

---

## 9. Testing the Connection

### 9.1 Verify connectivity from the HMS server

```bash
# Test network reach
curl -I http://GATEWAY_SERVER/

# Test API key (should return 422 with missing fields, not 401)
curl -X POST http://GATEWAY_SERVER/sync/hospital \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_64_CHAR_KEY" \
  -d '{}'

# Expected response (422 = key is valid, but fields are missing):
# {"success":false,"message":"Missing required fields: hms_id, name, state"}

# Wrong key test (should return 401):
curl -X POST http://GATEWAY_SERVER/sync/hospital \
  -H "Content-Type: application/json" \
  -H "X-API-Key: wrongkey" \
  -d '{}'
# Expected: {"success":false,"message":"Invalid or inactive API key."}
```

### 9.2 PHP connectivity test

Add this temporary test in any HMS controller (remove after confirming):

```php
public function testGateway()
{
    try {
        $gw     = new \App\Libraries\HmsGatewayConnector();
        $result = $gw->syncHospital([
            'hms_id' => 'TEST-001',
            'name'   => 'Test Hospital',
            'state'  => 'Maharashtra',
        ]);

        // 200 = ABDM live, 202 = queued (ABDM offline), both mean gateway is working
        if (in_array($result['status_code'], [200, 202])) {
            echo "✅ Gateway connected. Status: " . $result['status_code'];
        } else {
            echo "❌ Unexpected response: " . json_encode($result);
        }
    } catch (\Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage();
    }
}
```

---

## 10. Troubleshooting

### "Cannot reach ABDM Gateway" (status_code = 0)

- Verify `ABDM_GATEWAY_URL` in the HMS `.env` is correct
- Test with `curl http://GATEWAY_SERVER/` from the HMS server
- Check firewall rules: port 80 (or 443) must be open between the two servers
- If using HTTPS, ensure the SSL certificate is valid; or set `CURLOPT_SSL_VERIFYPEER => false` in the connector for self-signed certs (development only)

### "Invalid or inactive API key" (status_code = 401)

- Confirm `ABDM_GATEWAY_API_KEY` in the HMS `.env` matches the key shown in the gateway admin panel
- Check the facility is marked **Active** in the gateway dashboard
- If you regenerated the key in the admin panel, update `ABDM_GATEWAY_API_KEY` in the HMS `.env`

### "Missing required fields" (status_code = 422)

- Check the `errors` array in the response — it lists the missing field names
- Compare your payload against the field table in this document

### "ABDM_GATEWAY_URL is not configured" (PHP exception)

- Ensure the `.env` file in the local HMS has `ABDM_GATEWAY_URL` set
- Run `php spark env` in the HMS directory to confirm env vars are loaded

### Queue not being processed

- Queued records are retried when the gateway's cron job runs: `php spark sync:process-queue`
- Set up a cron on the gateway server: `*/5 * * * * cd /var/www/html/hms-abdm-gateway && php spark sync:process-queue`
- Check the gateway `writable/logs/` for error messages

---

## Support

- **Gateway Repository:** https://github.com/infodevsofttech-admin/HMS-ABDM-Gateway
- **Local HMS Repository:** https://github.com/infodevsofttech-admin/devsofttech_hms_ci4
- **Admin Panel:** `http://<gateway-server>/admin`
- **Connector File:** `app/Libraries/HmsGatewayConnector.php` in the gateway repository
