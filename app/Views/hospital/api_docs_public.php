<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>API Documentation - ABDM Bridge Gateway</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f4f6fb;
            color: #212529;
        }
        /* ── Header ── */
        .doc-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 28px 40px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }
        .doc-header .brand { display: flex; align-items: center; gap: 14px; }
        .doc-header .brand img { height: 36px; }
        .doc-header .brand-text h1 { font-size: 20px; font-weight: 700; }
        .doc-header .brand-text p { font-size: 12px; opacity: .8; margin-top: 2px; }
        .doc-header .header-links a {
            color: rgba(255,255,255,.85);
            text-decoration: none;
            font-size: 13px;
            margin-left: 20px;
            border: 1px solid rgba(255,255,255,.4);
            padding: 6px 14px;
            border-radius: 20px;
            transition: background .2s;
        }
        .doc-header .header-links a:hover { background: rgba(255,255,255,.15); }
        /* ── Layout ── */
        .doc-body {
            max-width: 1100px;
            margin: 32px auto;
            padding: 0 24px 60px;
        }
        /* ── Quick ref bar ── */
        .quick-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 28px;
        }
        .quick-bar .qb-item {
            background: #fff;
            border-radius: 10px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
        }
        .qb-label { font-size: 10px; font-weight: 700; color: #6c757d; text-transform: uppercase; letter-spacing: .6px; margin-bottom: 7px; }
        .qb-value { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 13px; word-break: break-all; color: #1a1a2e; }
        /* ── Cards ── */
        .card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,.07);
            margin-bottom: 24px;
            overflow: hidden;
        }
        .card-head {
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            padding: 13px 20px;
            font-size: 14px;
            font-weight: 700;
            color: #343a40;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card-head.bridge { background: #e8f4fd; border-bottom-color: #bee5fb; }
        .card-body { padding: 20px; }
        /* ── Table ── */
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f0f4f8; padding: 10px 14px; text-align: left; font-weight: 600; color: #495057; }
        td { padding: 10px 14px; border-top: 1px solid #f0f0f0; vertical-align: top; }
        tr:hover td { background: #fafbff; }
        /* ── Code blocks ── */
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 8px;
            padding: 14px 18px;
            font-family: 'SFMono-Regular', Consolas, monospace;
            font-size: 12px;
            line-height: 1.75;
            overflow-x: auto;
            margin-top: 12px;
        }
        .cv { color: #ce9178; }  /* string value */
        .ck { color: #9cdcfe; }  /* key */
        .cn { color: #b5cea8; }  /* number */
        .cc { color: #6a9955; }  /* comment */
        /* ── Badges ── */
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; color: #fff; }
        .badge-get  { background: #28a745; }
        .badge-post { background: #007bff; }
        .badge-pub  { background: #6c757d; font-size: 10px; }
        .badge-auth { background: #17a2b8; font-size: 10px; }
        /* ── Two-column grid ── */
        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        @media (max-width: 720px) { .two-col { grid-template-columns: 1fr; } .doc-header { padding: 20px; } }
        /* ── Alert ── */
        .alert-info  { background: #e8f4fd; border-left: 4px solid #17a2b8; padding: 13px 18px; border-radius: 6px; font-size: 13px; color: #0c5460; margin-bottom: 24px; }
        .alert-warn  { background: #fff3cd; border-left: 4px solid #ffc107; padding: 13px 18px; border-radius: 6px; font-size: 13px; color: #856404; margin-bottom: 24px; }
        /* ── Endpoint desc ── */
        .ep-desc { font-size: 13px; color: #6c757d; margin-bottom: 10px; }
        /* ── Section label ── */
        .section-label { font-size: 18px; font-weight: 700; color: #343a40; margin: 32px 0 14px; display: flex; align-items: center; gap: 8px; }
        .section-label::after { content: ''; flex: 1; height: 1px; background: #dee2e6; }
        code { background: #f0f4f8; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-family: monospace; }
    </style>
</head>
<body>

<div class="doc-header">
    <div class="brand">
        <div class="brand-text">
            <h1>ABDM Bridge Gateway</h1>
            <p>API Reference — <?= esc($base_url) ?></p>
        </div>
    </div>
    <div class="header-links">
        <a href="/">Hospital Login</a>
        <a href="/admin">Admin Login</a>
    </div>
</div>

<div class="doc-body">

    <div class="alert-info">
        <strong>For HMS Developers:</strong> Use your gateway API key (available on the Hospital Portal → Profile page) as a Bearer token in all authenticated requests.
        Contact your hospital administrator if you do not have an API key.
    </div>

    <!-- Quick Reference -->
    <div class="quick-bar">
        <div class="qb-item">
            <div class="qb-label">Base URL</div>
            <div class="qb-value"><?= esc($base_url) ?></div>
        </div>
        <div class="qb-item">
            <div class="qb-label">Authentication</div>
            <div class="qb-value">Authorization: Bearer {api-key}</div>
        </div>
        <div class="qb-item">
            <div class="qb-label">Content Type</div>
            <div class="qb-value">Content-Type: application/json</div>
        </div>
        <div class="qb-item">
            <div class="qb-label">Response field</div>
            <div class="qb-value">"ok": 1 = success &nbsp;|&nbsp; "ok": 0 = error</div>
        </div>
    </div>

    <!-- Endpoint Index -->
    <div class="section-label">All Endpoints</div>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr>
                    <th style="width:9%;">Method</th>
                    <th style="width:40%;">Endpoint</th>
                    <th>Description</th>
                    <th style="width:11%;">Auth</th>
                </tr></thead>
                <tbody>
                <tr>
                    <td><span class="badge badge-get">GET</span></td>
                    <td><code>/api/v3/health</code></td>
                    <td>Gateway health check</td>
                    <td><span class="badge badge-pub">Public</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-get">GET</span></td>
                    <td><code>/api/v3/gateway/status</code></td>
                    <td>Gateway + ABDM upstream connectivity status</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/abha/validate</code></td>
                    <td>Validate an ABHA ID or ABHA Address</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr style="background:#fff8e1;">
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/abha/aadhaar/generate-otp</code></td>
                    <td><strong>Create ABHA</strong> — Step 1: Send OTP to Aadhaar-linked mobile (new patient, no ABHA yet)</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr style="background:#fff8e1;">
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/abha/aadhaar/verify-otp</code></td>
                    <td><strong>Create ABHA</strong> — Step 2: Verify OTP and create/retrieve ABHA number</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr style="background:#e8f4fd;">
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/abha/mobile/generate-otp</code></td>
                    <td><strong>Link ABHA</strong> — Step 1: Send OTP to mobile (existing patient who already has ABHA)</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr style="background:#e8f4fd;">
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/abha/mobile/verify-otp</code></td>
                    <td><strong>Link ABHA</strong> — Step 2: Verify OTP and get full ABHA profile</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/consent/request</code></td>
                    <td>Request ABDM data consent from patient</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v3/bundle/push</code></td>
                    <td>Push FHIR R4 health document bundle</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr>
                    <td><span class="badge badge-get">GET</span></td>
                    <td><code>/api/v3/snomed/search?term=</code></td>
                    <td>Search SNOMED CT clinical terms by keyword</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                <tr style="background:#e8f4fd;">
                    <td><span class="badge badge-post">POST</span></td>
                    <td><code>/api/v1/bridge</code></td>
                    <td><strong>Unified Bridge</strong> — event-based dispatcher (recommended for HMS)</td>
                    <td><span class="badge badge-auth">Bearer</span></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bridge API -->
    <div class="section-label">Unified Bridge API <span style="font-size:13px;font-weight:400;color:#6c757d;">(Recommended for HMS integration)</span></div>
    <div class="card">
        <div class="card-head bridge">POST <?= esc($base_url) ?>/api/v1/bridge</div>
        <div class="card-body">
            <p class="ep-desc">Send a single <code>event_type</code> with a <code>payload</code> object. The bridge routes it to the correct ABDM service automatically — no need to call individual endpoints.</p>

            <div class="code-block">
<span class="cc">// Request</span>
POST <?= esc($base_url) ?>/api/v1/bridge
Authorization: Bearer {api-key}
Content-Type: application/json

{
  <span class="ck">"event_type"</span>: <span class="cv">"abdm.abha.validate"</span>,
  <span class="ck">"payload"</span>: {
    <span class="ck">"abha_id"</span>: <span class="cv">"14-1234-5678-9012"</span>
  }
}</div>

            <div class="code-block" style="margin-top:10px;">
<span class="cc">// Response</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"event_type"</span>: <span class="cv">"abdm.abha.validate"</span>,
  <span class="ck">"request_id"</span>: <span class="cv">"REQ-20260515120000-abc12345"</span>,
  <span class="ck">"dispatch"</span>: {
    <span class="ck">"ok"</span>: <span class="cn">1</span>,
    <span class="ck">"http_code"</span>: <span class="cn">200</span>,
    <span class="ck">"response"</span>: { <span class="cc">/* ABDM response data */</span> }
  }
}</div>

            <div style="margin-top:20px;font-weight:700;font-size:13px;margin-bottom:10px;">Supported Event Types</div>
            <table>
                <thead><tr>
                    <th style="width:38%;">event_type</th>
                    <th>Description</th>
                    <th>Required payload fields</th>
                </tr></thead>
                <tbody>
                <tr><td><code>abdm.abha.validate</code></td><td>Validate ABHA ID</td><td><code>abha_id</code> or <code>abha_address</code></td></tr>
                <tr><td><code>abdm.consent.requested</code></td><td>Request patient consent</td><td><code>abha_id</code>, <code>purpose_code</code>, <code>hi_types[]</code></td></tr>
                <tr><td><code>abdm.fhir.share.requested</code></td><td>Push any FHIR document</td><td><code>consent_handle</code>, <code>hi_type</code>, <code>bundle{}</code></td></tr>
                <tr><td><code>abdm.opd.prescription.share.requested</code></td><td>Share OPD prescription bundle</td><td><code>consent_handle</code>, <code>bundle{}</code></td></tr>
                <tr><td><code>abdm.ipd.admission.share.requested</code></td><td>Share IPD admission summary</td><td><code>consent_handle</code>, <code>bundle{}</code></td></tr>
                <tr><td><code>abdm.ipd.discharge.share.requested</code></td><td>Share IPD discharge summary</td><td><code>consent_handle</code>, <code>bundle{}</code></td></tr>
                <tr><td><code>abdm.diagnosis.report.share.requested</code></td><td>Share diagnostic report</td><td><code>consent_handle</code>, <code>bundle{}</code></td></tr>
                <tr><td><code>abdm.scan_share.lookup</code></td><td>SNOMED CT term lookup</td><td><code>term</code>, <code>return_limit</code> (optional)</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Which Flow? -->
    <div class="section-label">ABHA Integration Flows</div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px;">
        <div style="background:#fff8e1;border:2px solid #ffc107;border-radius:10px;padding:18px 22px;">
            <div style="font-size:13px;font-weight:700;color:#856404;margin-bottom:6px;">🆕 Flow 1 — Create ABHA</div>
            <div style="font-size:12px;color:#856404;line-height:1.6;">
                Use when the patient <strong>does not have an ABHA number yet</strong>.<br>
                Requires the patient's <strong>Aadhaar number</strong>.<br>
                ABDM sends OTP to the Aadhaar-linked mobile.
            </div>
        </div>
        <div style="background:#e8f4fd;border:2px solid #17a2b8;border-radius:10px;padding:18px 22px;">
            <div style="font-size:13px;font-weight:700;color:#0c5460;margin-bottom:6px;">🔗 Flow 2 — Link Existing ABHA</div>
            <div style="font-size:12px;color:#0c5460;line-height:1.6;">
                Use when the patient <strong>already has an ABHA number</strong>.<br>
                Requires the patient's <strong>mobile number</strong> registered with ABDM.<br>
                Returns full ABHA profile after OTP verification.
            </div>
        </div>
    </div>

    <!-- Flow 1: Create ABHA via Aadhaar OTP -->
    <div class="card" style="border-top:4px solid #ffc107;">
        <div class="card-head" style="background:#fff8e1;border-bottom-color:#ffc107;">
            <span style="font-size:16px;">🆕</span> Flow 1: Create ABHA via Aadhaar OTP &nbsp;<span style="font-size:11px;font-weight:400;color:#856404;">2 steps · Patient must have Aadhaar</span>
        </div>
        <div class="card-body">

            <!-- Step 1 -->
            <div style="margin-bottom:24px;">
                <div style="font-size:13px;font-weight:700;color:#343a40;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#ffc107;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">1</span>
                    Generate OTP &nbsp;<code style="font-weight:400;">POST /api/v3/abha/aadhaar/generate-otp</code>
                </div>
                <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Send the patient's 12-digit Aadhaar number. ABDM sends an OTP to the Aadhaar-linked mobile. Save the <code>txnId</code> from the response — you'll need it in Step 2.</p>
                <div class="code-block">
<span class="cc">// Request</span>
POST /api/v3/abha/aadhaar/generate-otp
Authorization: Bearer {api-key}
Content-Type: application/json

{
  <span class="ck">"aadhaar"</span>: <span class="cv">"999941057058"</span>  <span class="cc">// plain 12-digit Aadhaar, gateway encrypts it</span>
}

<span class="cc">// Response (success)</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"txnId"</span>: <span class="cv">"c82482fa-675b-4612-af13-15d39d5369ed"</span>,  <span class="cc">// save this!</span>
    <span class="ck">"message"</span>: <span class="cv">"OTP sent to Aadhaar registered mobile number ending with ******8717"</span>
  },
  <span class="ck">"request_id"</span>: <span class="cv">"REQ-20260515-abc123"</span>
}</div>
            </div>

            <!-- Step 2 -->
            <div>
                <div style="font-size:13px;font-weight:700;color:#343a40;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#ffc107;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">2</span>
                    Verify OTP &nbsp;<code style="font-weight:400;">POST /api/v3/abha/aadhaar/verify-otp</code>
                </div>
                <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Submit the OTP entered by the patient along with the <code>txnId</code> from Step 1. On success, ABDM returns the patient's ABHA tokens and profile. Store the <code>ABHANumber</code> in your HMS patient record.</p>
                <div class="code-block">
<span class="cc">// Request</span>
{
  <span class="ck">"txnId"</span>: <span class="cv">"c82482fa-675b-4612-af13-15d39d5369ed"</span>,  <span class="cc">// from Step 1</span>
  <span class="ck">"otp"</span>: <span class="cv">"123456"</span>,       <span class="cc">// 6-digit OTP entered by patient, gateway encrypts it</span>
  <span class="ck">"mobile"</span>: <span class="cv">"9876543210"</span>  <span class="cc">// optional: mobile for ABHA communication</span>
}

<span class="cc">// Response — New ABHA created</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"txnId"</span>: <span class="cv">"c82482fa-..."</span>,
    <span class="ck">"ABHAProfile"</span>: {
      <span class="ck">"ABHANumber"</span>: <span class="cv">"91-5101-6530-5101"</span>,  <span class="cc">// ← store this in HMS</span>
      <span class="ck">"name"</span>: <span class="cv">"MEERA BISHT"</span>,
      <span class="ck">"gender"</span>: <span class="cv">"F"</span>,
      <span class="ck">"dob"</span>: <span class="cv">"01-01-1990"</span>,
      <span class="ck">"mobile"</span>: <span class="cv">"9876543210"</span>
    },
    <span class="ck">"tokens"</span>: {
      <span class="ck">"token"</span>: <span class="cv">"eyJhbGci..."</span>,
      <span class="ck">"expiresIn"</span>: <span class="cn">1800</span>,
      <span class="ck">"refreshToken"</span>: <span class="cv">"eyJhbGci..."</span>
    }
  }
}

<span class="cc">// Response — ABHA already exists (returning patient)</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"message"</span>: <span class="cv">"This account already exist"</span>,
    <span class="ck">"txnId"</span>: <span class="cv">"c82482fa-..."</span>,
    <span class="ck">"tokens"</span>: {
      <span class="ck">"token"</span>: <span class="cv">"eyJhbGci..."</span>,  <span class="cc">// JWT — decode to get ABHANumber</span>
      <span class="ck">"expiresIn"</span>: <span class="cn">1800</span>
    }
  }
}</div>
            </div>
        </div>
    </div>

    <!-- Flow 2: Link ABHA via Mobile OTP -->
    <div class="card" style="border-top:4px solid #17a2b8;">
        <div class="card-head" style="background:#e8f4fd;border-bottom-color:#17a2b8;">
            <span style="font-size:16px;">🔗</span> Flow 2: Link Existing ABHA via Mobile OTP &nbsp;<span style="font-size:11px;font-weight:400;color:#0c5460;">2 steps · Patient must have existing ABHA</span>
        </div>
        <div class="card-body">

            <!-- Step 1 -->
            <div style="margin-bottom:24px;">
                <div style="font-size:13px;font-weight:700;color:#343a40;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#17a2b8;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">1</span>
                    Generate OTP &nbsp;<code style="font-weight:400;">POST /api/v3/abha/mobile/generate-otp</code>
                </div>
                <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Send the patient's 10-digit mobile number. ABDM sends an OTP to that mobile if it is registered with ABDM/ABHA. Save the <code>txnId</code> from the response.</p>
                <div class="code-block">
<span class="cc">// Request</span>
POST /api/v3/abha/mobile/generate-otp
Authorization: Bearer {api-key}
Content-Type: application/json

{
  <span class="ck">"mobile"</span>: <span class="cv">"9999999999"</span>  <span class="cc">// plain 10-digit mobile, gateway encrypts it</span>
}

<span class="cc">// Response (success)</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"txnId"</span>: <span class="cv">"1f491656-812e-4261-8f8b-8eb2f562ba3b"</span>,  <span class="cc">// save this!</span>
    <span class="ck">"message"</span>: <span class="cv">"OTP sent to mobile number ending with ******9999"</span>
  }
}</div>
            </div>

            <!-- Step 2 -->
            <div>
                <div style="font-size:13px;font-weight:700;color:#343a40;margin-bottom:8px;display:flex;align-items:center;gap:8px;">
                    <span style="background:#17a2b8;color:#fff;width:22px;height:22px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;flex-shrink:0;">2</span>
                    Verify OTP &nbsp;<code style="font-weight:400;">POST /api/v3/abha/mobile/verify-otp</code>
                </div>
                <p style="font-size:12px;color:#6c757d;margin-bottom:8px;">Submit the OTP and <code>txnId</code> from Step 1. The gateway verifies the OTP with ABDM, then automatically fetches the full ABHA profile and returns it in a single response. Store the <code>ABHANumber</code> in your HMS patient record.</p>
                <div class="code-block">
<span class="cc">// Request</span>
{
  <span class="ck">"txnId"</span>: <span class="cv">"1f491656-812e-4261-8f8b-8eb2f562ba3b"</span>,  <span class="cc">// from Step 1</span>
  <span class="ck">"otp"</span>: <span class="cv">"654321"</span>  <span class="cc">// 6-digit OTP entered by patient, gateway encrypts it</span>
}

<span class="cc">// Response (success)</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"authResult"</span>: <span class="cv">"success"</span>,
    <span class="ck">"txnId"</span>: <span class="cv">"1f491656-..."</span>,
    <span class="ck">"token"</span>: <span class="cv">"eyJhbGci..."</span>,
    <span class="ck">"profile"</span>: {
      <span class="ck">"ABHANumber"</span>: <span class="cv">"91-5101-6530-5101"</span>,  <span class="cc">// ← store this in HMS</span>
      <span class="ck">"name"</span>: <span class="cv">"MEERA BISHT"</span>,
      <span class="ck">"gender"</span>: <span class="cv">"F"</span>,
      <span class="ck">"dob"</span>: <span class="cv">"01-01-1990"</span>,
      <span class="ck">"mobile"</span>: <span class="cv">"9999999999"</span>,
      <span class="ck">"email"</span>: <span class="cv">"patient@email.com"</span>
    }
  },
  <span class="ck">"request_id"</span>: <span class="cv">"REQ-20260515-xyz789"</span>
}</div>
            </div>
        </div>
    </div>

    <!-- Other Endpoints -->
    <div class="section-label">Other Endpoints</div>
    <div class="two-col">
        <div>
            <!-- Health -->
            <div class="card">
                <div class="card-head"><span class="badge badge-get" style="margin-right:6px;">GET</span> /api/v3/health</div>
                <div class="card-body">
                    <p class="ep-desc">Public. No auth required. Verify gateway is reachable.</p>
                    <div class="code-block">curl <?= esc($base_url) ?>/api/v3/health

<span class="cc">// Response</span>
{ <span class="ck">"status"</span>: <span class="cv">"ok"</span>, <span class="ck">"mode"</span>: <span class="cv">"live"</span> }</div>
                </div>
            </div>

            <!-- ABHA Validate -->
            <div class="card">
                <div class="card-head"><span class="badge badge-post" style="margin-right:6px;">POST</span> /api/v3/abha/validate</div>
                <div class="card-body">
                    <p class="ep-desc">Check whether an ABHA ID or ABHA Address is valid and active in ABDM.</p>
                    <div class="code-block">
{ <span class="ck">"abha_id"</span>: <span class="cv">"91-5101-6530-5101"</span> }
<span class="cc">// or</span>
{ <span class="ck">"abha_address"</span>: <span class="cv">"patient@abdm"</span> }

<span class="cc">// Response</span>
{ <span class="ck">"ok"</span>: <span class="cn">1</span>, <span class="ck">"data"</span>: { <span class="ck">"status"</span>: <span class="cv">"VALID"</span> } }</div>
                </div>
            </div>

            <!-- Consent -->
            <div class="card">
                <div class="card-head"><span class="badge badge-post" style="margin-right:6px;">POST</span> /api/v3/consent/request</div>
                <div class="card-body">
                    <p class="ep-desc">Request ABDM data consent from patient. Patient receives notification in their ABHA app.</p>
                    <div class="code-block">
{
  <span class="ck">"patient_abha"</span>: <span class="cv">"91-5101-6530-5101"</span>,
  <span class="ck">"purpose"</span>: <span class="cv">"TREATMENT"</span>,
  <span class="ck">"hi_types"</span>: [<span class="cv">"OPConsultation"</span>, <span class="cv">"DiagnosticReport"</span>]
}

<span class="cc">// Response</span>
{ <span class="ck">"ok"</span>: <span class="cn">1</span>, <span class="ck">"consent_id"</span>: <span class="cv">"CONS-..."</span> }</div>
                </div>
            </div>
        </div>
        <div>
            <!-- Bundle Push -->
            <div class="card">
                <div class="card-head"><span class="badge badge-post" style="margin-right:6px;">POST</span> /api/v3/bundle/push</div>
                <div class="card-body">
                    <p class="ep-desc">Push a FHIR R4 Bundle to ABDM. Requires a <code>consent_id</code> from a prior consent request.</p>
                    <div class="code-block">
{
  <span class="ck">"consent_id"</span>: <span class="cv">"CONS-20260515-abc123"</span>,
  <span class="ck">"hi_type"</span>: <span class="cv">"OPConsultation"</span>,
  <span class="ck">"fhir_bundle"</span>: { <span class="cc">/* FHIR R4 Bundle */</span> }
}

<span class="cc">// hi_type values:</span>
<span class="cc">// OPConsultation | DiagnosticReport | DischargeSummary</span>
<span class="cc">// ImmunizationRecord | HealthDocumentRecord | Prescription</span></div>
                </div>
            </div>

            <!-- SNOMED -->
            <div class="card">
                <div class="card-head"><span class="badge badge-get" style="margin-right:6px;">GET</span> /api/v3/snomed/search</div>
                <div class="card-body">
                    <p class="ep-desc">Search SNOMED CT clinical terms by keyword. Use for diagnosis/procedure code lookup.</p>
                    <div class="code-block">GET /api/v3/snomed/search?term=fever&amp;return_limit=10

<span class="cc">// Response</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: [
    { <span class="ck">"code"</span>: <span class="cv">"386661006"</span>, <span class="ck">"term"</span>: <span class="cv">"Fever"</span> }
  ]
}</div>
                </div>
            </div>

            <!-- Gateway Status -->
            <div class="card">
                <div class="card-head"><span class="badge badge-get" style="margin-right:6px;">GET</span> /api/v3/gateway/status</div>
                <div class="card-body">
                    <p class="ep-desc">Check live connectivity of gateway, database, ABDM, and SNOMED. Requires auth.</p>
                    <div class="code-block">
<span class="cc">// Response</span>
{
  <span class="ck">"ok"</span>: <span class="cn">1</span>,
  <span class="ck">"data"</span>: {
    <span class="ck">"gateway"</span>: <span class="cv">"ok"</span>,
    <span class="ck">"database"</span>: <span class="cv">"ok"</span>,
    <span class="ck">"abdm_m3"</span>: <span class="cv">"ok"</span>
  }
}</div>
                </div>
            </div>
        </div>
    </div>


    <!-- Error Reference -->
    <div class="section-label">Error Reference</div>
    <div class="card">
        <div class="card-body" style="padding:0;">
            <table>
                <thead><tr>
                    <th style="width:11%;">HTTP Code</th>
                    <th style="width:18%;">ok value</th>
                    <th>Meaning</th>
                    <th>Action</th>
                </tr></thead>
                <tbody>
                <tr><td><code>200</code></td><td style="color:#28a745;font-weight:700;">ok: 1</td><td>Success</td><td>Process the <code>data</code> or <code>dispatch</code> object</td></tr>
                <tr><td><code>400</code></td><td style="color:#dc3545;font-weight:700;">ok: 0</td><td>Bad request — missing or invalid fields</td><td>Check request body fields match the spec above</td></tr>
                <tr><td><code>403</code></td><td style="color:#dc3545;font-weight:700;">ok: 0</td><td>Unauthorized — invalid or missing API key</td><td>Verify <code>Authorization: Bearer &lt;key&gt;</code> header is set correctly</td></tr>
                <tr><td><code>422</code></td><td style="color:#dc3545;font-weight:700;">ok: 0</td><td>Unsupported <code>event_type</code> (bridge only)</td><td>Check event_type value against the table above</td></tr>
                <tr><td><code>500</code></td><td style="color:#dc3545;font-weight:700;">ok: 0</td><td>Gateway or ABDM upstream error</td><td>Log the <code>request_id</code> and contact admin</td></tr>
                <tr><td><code>202</code></td><td><code>status: ACCEPTED</code></td><td>Patient Scan &amp; Share received</td><td>Token queued — check OPD Queue in hospital portal</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Test Mode -->
    <div class="alert-info">
        <strong>Sandbox / Test Mode:</strong> When the gateway is in <strong>test mode</strong>, all API calls return realistic mock responses without touching ABDM systems.
        The response will include <code>"mode": "test"</code>. Use this during HMS development and QA.
        Contact the gateway administrator to switch to live mode when ready for production.
    </div>

    <div style="text-align:center;font-size:12px;color:#adb5bd;margin-top:40px;">
        ABDM Bridge Gateway &copy; <?= date('Y') ?> &nbsp;|&nbsp; <a href="/" style="color:#adb5bd;">Hospital Login</a>
    </div>

</div><!-- /.doc-body -->
</body>
</html>
