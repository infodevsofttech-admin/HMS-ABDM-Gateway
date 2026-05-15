<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'API Documentation'; ?>

<?= $this->section('content') ?>

<?php
$hospital   = $hospital ?? null;
$credential = $credential ?? null;
$baseUrl    = $base_url ?? 'https://abdm-bridge.e-atria.in';
$hfrId      = is_object($hospital) ? ($hospital->hfr_id ?? 'YOUR_HFR_ID') : 'YOUR_HFR_ID';
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-book-open"></i> API Documentation</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item active">API Docs</li>
        </ol></nav>
    </div>
</div>

<div class="hp-content">

    <?php if ($credential === null): ?>
    <div class="alert alert-warning" style="font-size:13px;">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        No active HMS API credential found. Contact your administrator to generate an API key before integrating.
    </div>
    <?php endif; ?>

    <!-- Quick Reference -->
    <div class="row">
        <div class="col-md-12">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-bolt"></i> Quick Reference</div>
                <div class="hp-card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div style="background:#f8f9fa;border-radius:6px;padding:14px 16px;margin-bottom:12px;">
                                <div style="font-size:11px;font-weight:700;color:#6c757d;text-transform:uppercase;margin-bottom:6px;">Base URL</div>
                                <code style="font-size:13px;word-break:break-all;"><?= esc($baseUrl) ?></code>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="background:#f8f9fa;border-radius:6px;padding:14px 16px;margin-bottom:12px;">
                                <div style="font-size:11px;font-weight:700;color:#6c757d;text-transform:uppercase;margin-bottom:6px;">Authentication</div>
                                <code style="font-size:13px;">Authorization: Bearer {api-key}</code>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div style="background:#f8f9fa;border-radius:6px;padding:14px 16px;margin-bottom:12px;">
                                <div style="font-size:11px;font-weight:700;color:#6c757d;text-transform:uppercase;margin-bottom:6px;">Your HFR ID</div>
                                <code style="font-size:13px;"><?= esc($hfrId) ?></code>
                            </div>
                        </div>
                    </div>
                    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:12px 16px;font-size:13px;">
                        <i class="fas fa-key mr-1" style="color:#856404;"></i>
                        Your API key is available on the <a href="/portal/profile">Profile page</a> (masked). Contact admin to receive the full key by email.
                        All requests must include <code>Content-Type: application/json</code> and <code>Authorization: Bearer {your-api-key}</code>.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Endpoint Index -->
    <div class="row">
        <div class="col-md-12">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-list"></i> Available Endpoints</div>
                <div class="hp-card-body" style="padding:0;">
                    <table class="hp-tbl" style="font-size:13px;">
                        <thead>
                            <tr style="background:#f0f4f8;">
                                <th style="padding:10px 16px;width:8%;">Method</th>
                                <th style="padding:10px 16px;width:38%;">Endpoint</th>
                                <th style="padding:10px 16px;">Description</th>
                                <th style="padding:10px 16px;width:12%;">Auth</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">GET</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/health</td>
                            <td style="padding:10px 16px;">Gateway health check</td>
                            <td style="padding:10px 16px;"><span style="color:#6c757d;font-size:12px;">Public</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">GET</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/gateway/status</td>
                            <td style="padding:10px 16px;">Gateway + ABDM connectivity status</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/validate</td>
                            <td style="padding:10px 16px;">Validate an ABHA ID or ABHA Address</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr style="background:#fff8e1;">
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/aadhaar/generate-otp</td>
                            <td style="padding:10px 16px;"><strong>Create ABHA</strong> — Step 1: OTP to Aadhaar-linked mobile (new patient)</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr style="background:#fff8e1;">
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/aadhaar/verify-otp</td>
                            <td style="padding:10px 16px;"><strong>Create ABHA</strong> — Step 2: Verify OTP and create/retrieve ABHA number</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr style="background:#e8f4fd;">
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/mobile/generate-otp</td>
                            <td style="padding:10px 16px;"><strong>Link ABHA</strong> — Step 1: OTP to mobile (existing patient with ABHA)</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr style="background:#e8f4fd;">
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/mobile/verify-otp</td>
                            <td style="padding:10px 16px;"><strong>Link ABHA</strong> — Step 2: Verify OTP and get full ABHA profile</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/consent/request</td>
                            <td style="padding:10px 16px;">Request ABDM data consent from patient</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/bundle/push</td>
                            <td style="padding:10px 16px;">Push FHIR health document bundle</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">GET</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/snomed/search?term=</td>
                            <td style="padding:10px 16px;">Search SNOMED CT clinical terms</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr style="background:#e8f4fd;">
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v1/bridge</td>
                            <td style="padding:10px 16px;"><strong>Unified Bridge</strong> — event-based dispatcher (recommended for HMS)</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bridge API (Recommended) -->
    <div class="row">
        <div class="col-md-12">
            <div class="hp-card">
                <div class="hp-card-head" style="background:#e8f4fd;"><i class="fas fa-project-diagram"></i> Unified Bridge API — <code style="font-size:13px;">POST /api/v1/bridge</code> <span style="font-size:12px;font-weight:400;">(Recommended for HMS integration)</span></div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#495057;margin-bottom:16px;">
                        Send a single <code>event_type</code> with a <code>payload</code> object. The bridge routes it to the correct ABDM service automatically. This is the preferred integration point for your HMS.
                    </p>

                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:16px 20px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;margin-bottom:20px;">
<span style="color:#6a9955;">// Request structure</span><br>
POST <?= esc($baseUrl) ?>/api/v1/bridge<br>
Authorization: Bearer {api-key}<br>
Content-Type: application/json<br><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"event_type"</span>: <span style="color:#ce9178;">"abdm.abha.validate"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"payload"</span>: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"abha_id"</span>: <span style="color:#ce9178;">"14-1234-5678-9012"</span><br>
&nbsp;&nbsp;}<br>
}
                    </div>

                    <div style="font-weight:700;font-size:13px;margin-bottom:10px;">Supported Event Types</div>
                    <table class="hp-tbl" style="font-size:12px;">
                        <thead>
                            <tr style="background:#f0f4f8;">
                                <th style="padding:9px 14px;">event_type</th>
                                <th style="padding:9px 14px;">Description</th>
                                <th style="padding:9px 14px;">Required payload fields</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.abha.validate</td>
                            <td style="padding:9px 14px;">Validate ABHA ID</td>
                            <td style="padding:9px 14px;font-family:monospace;">abha_id <em>or</em> abha_address</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.consent.requested</td>
                            <td style="padding:9px 14px;">Request patient consent</td>
                            <td style="padding:9px 14px;font-family:monospace;">abha_id, purpose_code, hi_types[]</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.fhir.share.requested</td>
                            <td style="padding:9px 14px;">Push any FHIR document</td>
                            <td style="padding:9px 14px;font-family:monospace;">consent_handle, hi_type, bundle{}</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.opd.prescription.share.requested</td>
                            <td style="padding:9px 14px;">Share OPD prescription bundle</td>
                            <td style="padding:9px 14px;font-family:monospace;">consent_handle, bundle{}</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.ipd.admission.share.requested</td>
                            <td style="padding:9px 14px;">Share IPD admission summary</td>
                            <td style="padding:9px 14px;font-family:monospace;">consent_handle, bundle{}</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.ipd.discharge.share.requested</td>
                            <td style="padding:9px 14px;">Share IPD discharge summary</td>
                            <td style="padding:9px 14px;font-family:monospace;">consent_handle, bundle{}</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.diagnosis.report.share.requested</td>
                            <td style="padding:9px 14px;">Share diagnostic report</td>
                            <td style="padding:9px 14px;font-family:monospace;">consent_handle, bundle{}</td>
                        </tr>
                        <tr>
                            <td style="padding:9px 14px;font-family:monospace;">abdm.scan_share.lookup</td>
                            <td style="padding:9px 14px;">SNOMED CT term lookup</td>
                            <td style="padding:9px 14px;font-family:monospace;">term, return_limit (optional)</td>
                        </tr>
                        </tbody>
                    </table>

                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:16px 20px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;margin-top:16px;">
<span style="color:#6a9955;">// Response structure (all endpoints)</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"event_type"</span>: <span style="color:#ce9178;">"abdm.abha.validate"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"request_id"</span>: <span style="color:#ce9178;">"REQ-20260515120000-abc12345"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"dispatch"</span>: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"http_code"</span>: <span style="color:#b5cea8;">200</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"response"</span>: { <span style="color:#6a9955;">/* ABDM response data */</span> }<br>
&nbsp;&nbsp;}<br>
}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ABHA Integration Flows -->
    <div class="row">
        <div class="col-md-12">
            <h5 style="font-weight:700;color:#343a40;margin:24px 0 14px;border-bottom:2px solid #dee2e6;padding-bottom:8px;">ABHA Integration Flows</h5>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
                <div style="background:#fff8e1;border:2px solid #ffc107;border-radius:10px;padding:16px 20px;">
                    <div style="font-size:13px;font-weight:700;color:#856404;margin-bottom:6px;">🆕 Flow 1 — Create ABHA (Aadhaar OTP)</div>
                    <div style="font-size:12px;color:#856404;line-height:1.6;">Patient <strong>does not have ABHA</strong>.<br>Requires Aadhaar number. OTP sent to Aadhaar-linked mobile.</div>
                </div>
                <div style="background:#e8f4fd;border:2px solid #17a2b8;border-radius:10px;padding:16px 20px;">
                    <div style="font-size:13px;font-weight:700;color:#0c5460;margin-bottom:6px;">🔗 Flow 2 — Link Existing ABHA (Mobile OTP)</div>
                    <div style="font-size:12px;color:#0c5460;line-height:1.6;">Patient <strong>already has ABHA</strong>.<br>Requires mobile registered with ABDM. Returns full profile.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flow 1: Create ABHA -->
    <div class="row">
        <div class="col-md-12">
            <div class="hp-card" style="border-top:4px solid #ffc107;">
                <div class="hp-card-head" style="background:#fff8e1;">🆕 Flow 1: Create ABHA via Aadhaar OTP &nbsp;<span style="font-size:11px;font-weight:400;color:#856404;">2 steps · Patient must have Aadhaar</span></div>
                <div class="hp-card-body">
                    <div style="margin-bottom:20px;">
                        <div style="font-size:13px;font-weight:700;margin-bottom:6px;"><span style="background:#ffc107;color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;margin-right:6px;">1</span> Generate OTP &mdash; <code>POST /api/v3/abha/aadhaar/generate-otp</code></div>
                        <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Send patient's Aadhaar number (plain, 12 digits). Gateway encrypts it. ABDM sends OTP to Aadhaar-linked mobile. Save the <code>txnId</code> from response.</p>
                        <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
<span style="color:#6a9955;">// Request</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"aadhaar"</span>: <span style="color:#ce9178;">"999941057058"</span>&nbsp;&nbsp;<span style="color:#6a9955;">// plain 12-digit Aadhaar</span><br>
}<br><br>
<span style="color:#6a9955;">// Response</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"data"</span>: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"c82482fa-675b-4612-af13-15d39d5369ed"</span>,&nbsp;&nbsp;<span style="color:#6a9955;">// ← save this!</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"message"</span>: <span style="color:#ce9178;">"OTP sent to Aadhaar registered mobile number ending with ******8717"</span><br>
&nbsp;&nbsp;}<br>
}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:700;margin-bottom:6px;"><span style="background:#ffc107;color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;margin-right:6px;">2</span> Verify OTP &mdash; <code>POST /api/v3/abha/aadhaar/verify-otp</code></div>
                        <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Submit OTP entered by patient + txnId from Step 1. On success, returns ABHA number and tokens. Store <code>ABHANumber</code> in HMS patient record.</p>
                        <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
<span style="color:#6a9955;">// Request</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"c82482fa-675b-4612-af13-15d39d5369ed"</span>,&nbsp;<span style="color:#6a9955;">// from Step 1</span><br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"otp"</span>: <span style="color:#ce9178;">"123456"</span>,&nbsp;&nbsp;<span style="color:#6a9955;">// 6-digit OTP, gateway encrypts it</span><br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"mobile"</span>: <span style="color:#ce9178;">"9876543210"</span>&nbsp;&nbsp;<span style="color:#6a9955;">// optional</span><br>
}<br><br>
<span style="color:#6a9955;">// Response — new ABHA</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"ABHAProfile"</span>: { <span style="color:#9cdcfe;">"ABHANumber"</span>: <span style="color:#ce9178;">"91-5101-6530-5101"</span>, <span style="color:#9cdcfe;">"name"</span>: <span style="color:#ce9178;">"..."</span> }, <span style="color:#9cdcfe;">"tokens"</span>: {<span style="color:#6a9955;">...}</span> } }<br><br>
<span style="color:#6a9955;">// Response — ABHA already exists</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"message"</span>: <span style="color:#ce9178;">"This account already exist"</span>, <span style="color:#9cdcfe;">"tokens"</span>: { <span style="color:#9cdcfe;">"token"</span>: <span style="color:#ce9178;">"eyJ..."</span> } } }
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Flow 2: Link ABHA -->
    <div class="row">
        <div class="col-md-12">
            <div class="hp-card" style="border-top:4px solid #17a2b8;">
                <div class="hp-card-head" style="background:#e8f4fd;">🔗 Flow 2: Link Existing ABHA via Mobile OTP &nbsp;<span style="font-size:11px;font-weight:400;color:#0c5460;">2 steps · Patient must have existing ABHA</span></div>
                <div class="hp-card-body">
                    <div style="margin-bottom:20px;">
                        <div style="font-size:13px;font-weight:700;margin-bottom:6px;"><span style="background:#17a2b8;color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;margin-right:6px;">1</span> Generate OTP &mdash; <code>POST /api/v3/abha/mobile/generate-otp</code></div>
                        <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Send patient's mobile number (plain, 10 digits). Gateway encrypts it. ABDM sends OTP if mobile is registered with ABDM. Save the <code>txnId</code> from response.</p>
                        <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
<span style="color:#6a9955;">// Request</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"mobile"</span>: <span style="color:#ce9178;">"9999999999"</span>&nbsp;&nbsp;<span style="color:#6a9955;">// plain 10-digit mobile</span><br>
}<br><br>
<span style="color:#6a9955;">// Response</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"data"</span>: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"1f491656-812e-4261-8f8b-8eb2f562ba3b"</span>,&nbsp;&nbsp;<span style="color:#6a9955;">// ← save this!</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"message"</span>: <span style="color:#ce9178;">"OTP sent to mobile number ending with ******9999"</span><br>
&nbsp;&nbsp;}<br>
}
                        </div>
                    </div>
                    <div>
                        <div style="font-size:13px;font-weight:700;margin-bottom:6px;"><span style="background:#17a2b8;color:#fff;width:20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;margin-right:6px;">2</span> Verify OTP &mdash; <code>POST /api/v3/abha/mobile/verify-otp</code></div>
                        <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Submit OTP and txnId. The gateway verifies OTP with ABDM and automatically fetches the full ABHA profile in a single response. Store <code>ABHANumber</code> in HMS patient record.</p>
                        <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
<span style="color:#6a9955;">// Request</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"1f491656-812e-4261-8f8b-8eb2f562ba3b"</span>,&nbsp;<span style="color:#6a9955;">// from Step 1</span><br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"otp"</span>: <span style="color:#ce9178;">"654321"</span>&nbsp;&nbsp;<span style="color:#6a9955;">// 6-digit OTP, gateway encrypts it</span><br>
}<br><br>
<span style="color:#6a9955;">// Response</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"data"</span>: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"authResult"</span>: <span style="color:#ce9178;">"success"</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"token"</span>: <span style="color:#ce9178;">"eyJhbGci..."</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"profile"</span>: {<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"ABHANumber"</span>: <span style="color:#ce9178;">"91-5101-6530-5101"</span>,&nbsp;&nbsp;<span style="color:#6a9955;">// ← store in HMS</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"name"</span>: <span style="color:#ce9178;">"MEERA BISHT"</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"gender"</span>: <span style="color:#ce9178;">"F"</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"dob"</span>: <span style="color:#ce9178;">"01-01-1990"</span>,<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="color:#9cdcfe;">"mobile"</span>: <span style="color:#ce9178;">"9999999999"</span><br>
&nbsp;&nbsp;&nbsp;&nbsp;}<br>
&nbsp;&nbsp;}<br>
}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Other Individual Endpoints -->
    <div class="row">
        <div class="col-md-6">

            <!-- Health Check -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">GET</span>/api/v3/health</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Public. No auth required.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
{ <span style="color:#9cdcfe;">"status"</span>: <span style="color:#ce9178;">"ok"</span>, <span style="color:#9cdcfe;">"mode"</span>: <span style="color:#ce9178;">"live"</span> }
                    </div>
                </div>
            </div>

            <!-- ABHA Validate -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/abha/validate</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Check if an ABHA ID or ABHA Address is valid and active.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
{ <span style="color:#9cdcfe;">"abha_id"</span>: <span style="color:#ce9178;">"91-5101-6530-5101"</span> }<br>
<span style="color:#6a9955;">// Response</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"status"</span>: <span style="color:#ce9178;">"VALID"</span> } }
                    </div>
                </div>
            </div>

            <!-- Consent -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/consent/request</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Request ABDM consent from patient. Patient approves via ABHA app.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"patient_abha"</span>: <span style="color:#ce9178;">"91-5101-6530-5101"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"purpose"</span>: <span style="color:#ce9178;">"TREATMENT"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"hi_types"</span>: [<span style="color:#ce9178;">"OPConsultation"</span>]<br>
}
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-6">

            <!-- Bundle Push -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/bundle/push</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Push FHIR R4 Bundle. Requires <code>consent_id</code> from consent request.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"consent_id"</span>: <span style="color:#ce9178;">"CONS-20260515-abc123"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"hi_type"</span>: <span style="color:#ce9178;">"OPConsultation"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"fhir_bundle"</span>: { <span style="color:#6a9955;">/* FHIR R4 Bundle */</span> }<br>
}<br>
<span style="color:#6a9955;">// hi_type: OPConsultation | DiagnosticReport | DischargeSummary</span><br>
<span style="color:#6a9955;">// ImmunizationRecord | HealthDocumentRecord | Prescription</span>
                    </div>
                </div>
            </div>

            <!-- SNOMED Search -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">GET</span>/api/v3/snomed/search</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Search SNOMED CT clinical terms for diagnosis/procedure codes.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;">
GET /api/v3/snomed/search?term=fever<br><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: [{ <span style="color:#9cdcfe;">"code"</span>: <span style="color:#ce9178;">"386661006"</span>, <span style="color:#9cdcfe;">"term"</span>: <span style="color:#ce9178;">"Fever"</span> }] }
                    </div>
                </div>
            </div>

        </div>
    </div>


    <div class="row">
        <div class="col-md-12">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-exclamation-circle"></i> Error Reference</div>
                <div class="hp-card-body" style="padding:0;">
                    <table class="hp-tbl" style="font-size:13px;">
                        <thead>
                            <tr style="background:#f0f4f8;">
                                <th style="padding:10px 16px;width:12%;">HTTP Code</th>
                                <th style="padding:10px 16px;width:20%;">ok value</th>
                                <th style="padding:10px 16px;">Meaning</th>
                                <th style="padding:10px 16px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td style="padding:10px 16px;font-family:monospace;">200</td>
                            <td style="padding:10px 16px;"><span style="color:#28a745;font-weight:700;">ok: 1</span></td>
                            <td style="padding:10px 16px;">Success</td>
                            <td style="padding:10px 16px;">Process the <code>data</code> or <code>dispatch</code> object</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;font-family:monospace;">400</td>
                            <td style="padding:10px 16px;"><span style="color:#dc3545;font-weight:700;">ok: 0</span></td>
                            <td style="padding:10px 16px;">Bad request — missing or invalid fields</td>
                            <td style="padding:10px 16px;">Check request body fields</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;font-family:monospace;">403</td>
                            <td style="padding:10px 16px;"><span style="color:#dc3545;font-weight:700;">ok: 0</span></td>
                            <td style="padding:10px 16px;">Unauthorized — invalid or missing API key</td>
                            <td style="padding:10px 16px;">Verify <code>Authorization: Bearer</code> header is set correctly</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;font-family:monospace;">422</td>
                            <td style="padding:10px 16px;"><span style="color:#dc3545;font-weight:700;">ok: 0</span></td>
                            <td style="padding:10px 16px;">Unsupported <code>event_type</code> in bridge</td>
                            <td style="padding:10px 16px;">Check the event_type value against the table above</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;font-family:monospace;">500</td>
                            <td style="padding:10px 16px;"><span style="color:#dc3545;font-weight:700;">ok: 0</span></td>
                            <td style="padding:10px 16px;">Gateway or ABDM upstream error</td>
                            <td style="padding:10px 16px;">Log the <code>request_id</code> and contact admin</td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;font-family:monospace;">202</td>
                            <td style="padding:10px 16px;"><code>status: ACCEPTED</code></td>
                            <td style="padding:10px 16px;">Scan &amp; Share patient arrival received</td>
                            <td style="padding:10px 16px;">Token is queued — check OPD Queue in portal</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Test Mode Note -->
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-info" style="font-size:13px;">
                <i class="fas fa-flask mr-2"></i>
                <strong>Sandbox / Test Mode:</strong> When the gateway is in <strong>test mode</strong>, all API calls return realistic mock responses without touching ABDM systems. The response will include <code>"mode": "test"</code>. Use this for your HMS development and QA phase. Contact admin to switch to live mode when ready.
            </div>
        </div>
    </div>

</div><!-- /.hp-content -->

<?= $this->endSection() ?>
