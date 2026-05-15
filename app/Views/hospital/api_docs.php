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
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/aadhaar/generate-otp</td>
                            <td style="padding:10px 16px;">M1 — Send OTP to Aadhaar-linked mobile for ABHA creation</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/aadhaar/verify-otp</td>
                            <td style="padding:10px 16px;">M1 — Verify OTP and enrol/link ABHA via Aadhaar</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/mobile/generate-otp</td>
                            <td style="padding:10px 16px;">M1 — Send OTP for mobile-based ABHA flow</td>
                            <td style="padding:10px 16px;"><span class="hb hb-blue" style="font-size:10px;">Bearer</span></td>
                        </tr>
                        <tr>
                            <td style="padding:10px 16px;"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;font-weight:700;">POST</span></td>
                            <td style="padding:10px 16px;font-family:monospace;font-size:12px;">/api/v3/abha/mobile/verify-otp</td>
                            <td style="padding:10px 16px;">M1 — Verify mobile OTP and complete ABHA link</td>
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

    <!-- Individual Endpoints -->
    <div class="row">
        <div class="col-md-6">

            <!-- Health Check -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">GET</span>/api/v3/health</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Public. No authentication required. Use to verify gateway reachability.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
curl <?= esc($baseUrl) ?>/api/v3/health<br><br>
<span style="color:#6a9955;">// Response</span><br>
{ <span style="color:#9cdcfe;">"status"</span>: <span style="color:#ce9178;">"ok"</span>, <span style="color:#9cdcfe;">"mode"</span>: <span style="color:#ce9178;">"live"</span>, <span style="color:#9cdcfe;">"version"</span>: <span style="color:#ce9178;">"1.0.0"</span> }
                    </div>
                </div>
            </div>

            <!-- ABHA Validate -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/abha/validate</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Check whether an ABHA ID or ABHA Address is valid and active.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
<span style="color:#6a9955;">// Request body (one of)</span><br>
{ <span style="color:#9cdcfe;">"abha_id"</span>: <span style="color:#ce9178;">"14-1234-5678-9012"</span> }<br>
{ <span style="color:#9cdcfe;">"abha_address"</span>: <span style="color:#ce9178;">"patient@abdm"</span> }<br><br>
<span style="color:#6a9955;">// Success response</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"status"</span>: <span style="color:#ce9178;">"VALID"</span> }<br>
}
                    </div>
                </div>
            </div>

            <!-- ABHA Aadhaar OTP -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/abha/aadhaar/generate-otp</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">M1 — Trigger an OTP to the patient's Aadhaar-linked mobile to begin ABHA creation/linking.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
<span style="color:#6a9955;">// Request body &mdash; send plain 12-digit Aadhaar; gateway encrypts it</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"aadhaar"</span>: <span style="color:#ce9178;">"999941057058"</span><br>
}<br><br>
<span style="color:#6a9955;">// Response</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"..."</span> } }
                    </div>
                </div>
            </div>

            <!-- ABHA Aadhaar Verify OTP -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/abha/aadhaar/verify-otp</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">M1 — Submit the Aadhaar OTP and complete ABHA enrolment. Returns ABHA profile on success.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
<span style="color:#6a9955;">// Request body &mdash; send plain OTP; gateway encrypts it</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"&lt;txnId from generate-otp&gt;"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"otp"</span>: <span style="color:#ce9178;">"123456"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"mobile"</span>: <span style="color:#ce9178;">"9876543210"</span>&nbsp;&nbsp;<span style="color:#6a9955;">// optional</span><br>
}<br><br>
<span style="color:#6a9955;">// Success response</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"ABHAProfile"</span>: { <span style="color:#9cdcfe;">"ABHANumber"</span>: <span style="color:#ce9178;">"14-xxxx"</span> } } }
                    </div>
                </div>
            </div>

        </div>
        <div class="col-md-6">

            <!-- Mobile OTP -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/abha/mobile/generate-otp</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">M1 — Send OTP to patient's mobile number for ABHA mobile linking flow.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
<span style="color:#6a9955;">// Request body &mdash; send plain 10-digit mobile; gateway encrypts it</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"mobile"</span>: <span style="color:#ce9178;">"9999999999"</span><br>
}<br><br>
<span style="color:#6a9955;">// Response</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"data"</span>: { <span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"..."</span> } }
                    </div>
                </div>
            </div>

            <!-- Mobile Verify OTP -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/abha/mobile/verify-otp</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">M1 — Verify mobile OTP and complete ABHA link for the patient.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"txnId"</span>: <span style="color:#ce9178;">"&lt;txnId from generate-otp&gt;"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"otp"</span>: <span style="color:#ce9178;">"123456"</span><br>
}
                    </div>
                </div>
            </div>

            <!-- Consent Request -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/consent/request</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Initiate an ABDM consent request on behalf of the patient. Patient receives a notification in their ABHA app.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"patient_abha"</span>: <span style="color:#ce9178;">"14-1234-5678-9012"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"purpose"</span>: <span style="color:#ce9178;">"TREATMENT"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"hi_types"</span>: [<span style="color:#ce9178;">"OPConsultation"</span>, <span style="color:#ce9178;">"DiagnosticReport"</span>]<br>
}<br><br>
<span style="color:#6a9955;">// Response</span><br>
{ <span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>, <span style="color:#9cdcfe;">"consent_id"</span>: <span style="color:#ce9178;">"CONS-..."</span> }
                    </div>
                </div>
            </div>

            <!-- Bundle Push -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#007bff;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">POST</span>/api/v3/bundle/push</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Push a FHIR R4 Bundle to ABDM. Requires a valid <code>consent_id</code> from a prior consent request.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"consent_id"</span>: <span style="color:#ce9178;">"CONS-20260515-abc123"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"hi_type"</span>: <span style="color:#ce9178;">"OPConsultation"</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"fhir_bundle"</span>: { <span style="color:#6a9955;">/* FHIR R4 Bundle */</span> }<br>
}<br><br>
<span style="color:#6a9955;">// hi_type values:</span><br>
<span style="color:#6a9955;">// OPConsultation | DiagnosticReport | DischargeSummary</span><br>
<span style="color:#6a9955;">// ImmunizationRecord | HealthDocumentRecord | Prescription</span>
                    </div>
                </div>
            </div>

            <!-- SNOMED Search -->
            <div class="hp-card">
                <div class="hp-card-head"><span style="background:#28a745;color:#fff;padding:2px 7px;border-radius:4px;font-size:11px;margin-right:8px;">GET</span>/api/v3/snomed/search</div>
                <div class="hp-card-body">
                    <p style="font-size:13px;color:#6c757d;">Search SNOMED CT clinical terms by keyword. Use for diagnosis/procedure code lookup in forms.</p>
                    <div style="background:#1e1e1e;color:#d4d4d4;border-radius:8px;padding:14px 16px;font-family:monospace;font-size:12px;line-height:1.7;overflow-x:auto;">
GET /api/v3/snomed/search?term=fever&amp;return_limit=10<br><br>
<span style="color:#6a9955;">// Response</span><br>
{<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"ok"</span>: <span style="color:#b5cea8;">1</span>,<br>
&nbsp;&nbsp;<span style="color:#9cdcfe;">"data"</span>: [<br>
&nbsp;&nbsp;&nbsp;&nbsp;{ <span style="color:#9cdcfe;">"code"</span>: <span style="color:#ce9178;">"386661006"</span>, <span style="color:#9cdcfe;">"term"</span>: <span style="color:#ce9178;">"Fever"</span> }<br>
&nbsp;&nbsp;]<br>
}
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Error Reference -->
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
