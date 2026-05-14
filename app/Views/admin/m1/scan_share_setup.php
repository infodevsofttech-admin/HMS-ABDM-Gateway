<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Scan &amp; Share Setup - M1 Module</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; max-width: 700px; }
        .card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h2 { margin-top: 0; font-size: 16px; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px; }
        label { display: block; font-weight: 600; margin-top: 12px; margin-bottom: 4px; font-size: 13px; }
        label span { font-weight: 400; color: #6b7280; }
        input[type=text], input[type=url] { width: 100%; font: inherit; padding: 9px 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 13px; }
        button { padding: 9px 20px; background: #2563eb; color: #fff; border: 0; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; margin-top: 14px; }
        button:hover { background: #1d4ed8; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .mono { white-space: pre-wrap; background: #0b1020; color: #cbd5e1; border-radius: 8px; padding: 12px; font-family: Consolas, Monaco, monospace; font-size: 12px; margin-top: 12px; overflow-x: auto; }
        .note { color: #475569; font-size: 13px; margin-bottom: 14px; line-height: 1.5; }
        .step-num { display: inline-block; background: #2563eb; color: #fff; border-radius: 50%; width: 22px; height: 22px; text-align: center; line-height: 22px; font-size: 12px; font-weight: 700; margin-right: 6px; }
        a { color: #2563eb; text-decoration: none; }
        code { background: #f3f4f6; padding: 1px 5px; border-radius: 4px; font-size: 12px; }
    </style>
</head>
<body>
    <p><a href="/admin/m1">← Back to M1 Suite</a> &nbsp;|&nbsp; <a href="/admin/m1/scan-share">View Token Queue →</a></p>
    <h1>Scan &amp; Share — Setup</h1>

    <?php if (!empty($message)): ?>
        <div class="ok"><?= esc((string) $message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="err"><?= esc((string) $error) ?></div>
    <?php endif; ?>

    <?php if (!empty($result) && is_array($result)): ?>
    <div class="card">
        <h2>Last API Result</h2>
        <div class="mono"><?= esc(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2><span class="step-num">1</span> How Scan &amp; Share Works</h2>
        <p class="note">
            1. Your hospital gets a <strong>facility QR code</strong> from HFR (Health Facility Registry).<br>
            2. Display the QR at your OPD / registration counter.<br>
            3. Patient opens their <strong>ABHA app</strong> → scans the QR.<br>
            4. ABDM calls our server at <code>POST /api/v3/hip/patient/share</code> with the patient profile.<br>
            5. Our server assigns a token number and acknowledges ABDM with it.<br>
            6. The token queue updates in real-time at <a href="/admin/m1/scan-share">Scan &amp; Share Queue</a>.
        </p>
        <p class="note" style="background:#fef9c3;border-radius:6px;padding:10px;border:1px solid #fde68a;">
            ⚠ <strong>One-time setup required</strong>: Register this server as a HIP bridge with ABDM <em>before</em> patients can scan.
            Complete Steps 2 and 3 below once (sandbox), then again for production.
        </p>
    </div>

    <div class="card">
        <h2><span class="step-num">2</span> Register Bridge URL</h2>
        <p class="note">
            Tell ABDM where to send callbacks. Set the URL to your server's base URL
            (e.g. <code>https://abdm-bridge.e-atria.in</code>).
            ABDM will call <code>{bridgeUrl}/api/v3/hip/patient/share</code>.
        </p>
        <form method="post" action="/admin/m1/scan-share-setup">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="bridge_url">
            <label for="bridge_url">Bridge URL <span>(your server base URL, no trailing slash)</span></label>
            <input type="url" id="bridge_url" name="bridge_url"
                   value="https://abdm-bridge.e-atria.in"
                   placeholder="https://abdm-bridge.e-atria.in" required>
            <button type="submit">Update Bridge URL</button>
        </form>
    </div>

    <div class="card">
        <h2><span class="step-num">3</span> Register as HIP</h2>
        <p class="note">
            Register this bridge as a Health Information Provider (HIP) under your facility.
            The <strong>Facility ID</strong> and <strong>Facility Name</strong> come from HFR registration.
            The <strong>Bridge ID</strong> (Client ID) is auto-filled from your ABDM credentials.
        </p>
        <form method="post" action="/admin/m1/scan-share-setup">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="register_hip">
            <label for="facility_id">Facility ID <span>(from HFR, e.g. IN0710XXXXXX)</span></label>
            <input type="text" id="facility_id" name="facility_id" placeholder="IN0710XXXXXX" required>
            <label for="facility_name">Facility Name</label>
            <input type="text" id="facility_name" name="facility_name" placeholder="e.g. City Hospital" required>
            <label for="hip_name">HIP Name <span>(display name for this integration)</span></label>
            <input type="text" id="hip_name" name="hip_name" placeholder="e.g. City Hospital HIP" required>
            <button type="submit">Register HIP</button>
        </form>
    </div>

    <div class="card">
        <h2><span class="step-num">4</span> Test the Callback</h2>
        <p class="note">
            After setup, use this <code>curl</code> command to simulate an ABDM callback to your server:
        </p>
        <div class="mono">curl -X POST https://abdm-bridge.e-atria.in/api/v3/hip/patient/share \
  -H "Content-Type: application/json" \
  -H "REQUEST-ID: test-req-001" \
  -d '{
  "intent": "PROFILE_SHARE",
  "metaData": {
    "hipId": "YOUR_HIP_ID",
    "context": "1",
    "hprId": "testhpr@hpr.abdm"
  },
  "profile": {
    "patient": {
      "abhaNumber": "91-5101-6530-5101",
      "abhaAddress": "devender.singh@sbx",
      "name": "Devender Singh",
      "gender": "M",
      "dayOfBirth": "12",
      "monthOfBirth": "04",
      "yearOfBirth": "1985",
      "phoneNumber": "9876543210"
    }
  }
}'</div>
        <p class="note" style="margin-top:10px;">
            Then check the <a href="/admin/m1/scan-share">Token Queue</a> — a token should appear.
        </p>
    </div>
</body>
</html>
