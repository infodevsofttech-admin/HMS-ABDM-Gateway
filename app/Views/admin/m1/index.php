<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>M1 Test Suite - ABDM Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; }
        .card { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .stack { display: grid; gap: 16px; }
        a { color: #1d4ed8; text-decoration: none; font-weight: 600; }
        ul { margin: 0; padding: 0 0 0 18px; }
        li { margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>M1 Test Suite</h1>
    <div class="stack">
        <div class="card">
            <h2>ABHA APIs</h2>
            <ul>
                <li><a href="/admin/m1/otp-flow">ABHA OTP Guided Flow (Aadhaar &amp; Mobile)</a> — <span style="font-size:12px;color:#6b7280;">Create new ABHA</span></li>
                <li><a href="/admin/m1/verify-flow">ABHA Verification Flow</a> — <span style="font-size:12px;color:#6b7280;">Verify existing ABHA holders (ABHA Number / Mobile OTP)</span></li>
                <li><a href="/admin/m1/abha-validate">ABHA Validate by ID (quick lookup)</a></li>
                <li><a href="/admin/m1/abha-profiles">Saved ABHA Profiles (Patient Master)</a></li>
            </ul>
        </div>
        <div class="card">
            <h2>Test Logs & Export</h2>
            <ul>
                <li><a href="/admin/m1-module">Raw M1 Test Console (all endpoints)</a></li>
                <li><a href="/admin/m1-module/export?format=csv">Export M1 Test Logs (CSV)</a></li>
                <li><a href="/admin/m1-module/export?format=json">Export M1 Test Logs (JSON)</a></li>
            </ul>
        </div>
        <?php if (!empty($profiles)): ?>
        <div class="card">
            <h2>Recent Verified Profiles</h2>
            <ul>
                <?php foreach ($profiles as $profile): ?>
                    <li><?= esc((string) ($profile->abha_number ?? '')) ?> - <?= esc((string) ($profile->full_name ?? '')) ?> - <?= esc((string) ($profile->last_verified_at ?? '')) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
