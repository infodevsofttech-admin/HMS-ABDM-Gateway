<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ABHA OTP Flow - M1 Module</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; }
        .card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); max-width: 560px; }
        .stack { display: grid; gap: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; margin-top: 14px; }
        label:first-child { margin-top: 0; }
        input, select, button { width: 100%; font: inherit; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; }
        button { background: #2563eb; color: #fff; border: 0; cursor: pointer; font-weight: 600; margin-top: 16px; }
        button:hover { background: #1d4ed8; }
        button.secondary { background: #6b7280; }
        button.secondary:hover { background: #4b5563; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px; border-radius: 8px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; }
        .mono { white-space: pre-wrap; background: #0b1020; color: #cbd5e1; border-radius: 8px; padding: 12px; font-family: Consolas, Monaco, monospace; font-size: 13px; overflow-x: auto; }
        .steps { display: flex; gap: 8px; margin-bottom: 20px; }
        .step { padding: 6px 14px; border-radius: 999px; background: #e2e8f0; font-size: 13px; font-weight: 700; color: #475569; }
        .step.active { background: #2563eb; color: #fff; }
        .step.done { background: #d1fae5; color: #065f46; }
        .note { color: #475569; font-size: 13px; margin-bottom: 14px; }
        a { color: #1d4ed8; text-decoration: none; }
        .radio-row { display: flex; gap: 24px; margin-top: 4px; }
        .radio-row label { font-weight: normal; display: flex; align-items: center; gap: 6px; margin-top: 0; }
        .radio-row input { width: auto; }
    </style>
</head>
<body>
    <?php
    $step          = (int) ($step ?? 1);
    $txnId         = (string) ($txnId ?? '');
    $otpType       = (string) ($otpType ?? 'aadhaar');
    $otpInput      = (string) ($otpInput ?? '');
    $resultProfile = $resultProfile ?? null;
    ?>

    <p><a href="/admin/m1">← Back to M1 Suite</a></p>
    <h1>ABHA OTP Guided Flow (M1)</h1>

    <div class="steps">
        <div class="step <?= $step === 1 ? 'active' : ($step > 1 ? 'done' : '') ?>">1 · Enter ID</div>
        <div class="step <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>">2 · Enter OTP</div>
        <div class="step <?= $step === 3 ? 'active' : '' ?>">3 · Verified</div>
    </div>

    <div class="stack">
        <?php if (!empty($message)): ?>
            <div class="ok"><?= esc((string) $message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="err"><?= esc((string) $error) ?></div>
        <?php endif; ?>

        <?php if ($step <= 1): ?>
        <!-- ── Step 1: Choose type and enter Aadhaar / mobile ── -->
        <div class="card">
            <p class="note">Select the OTP method, enter the Aadhaar number or mobile number, and click <strong>Send OTP</strong>. An OTP will be dispatched via ABDM.</p>
            <form method="post" action="/admin/m1/otp-generate">
                <?= csrf_field() ?>

                <label>OTP Type</label>
                <div class="radio-row">
                    <label><input type="radio" name="otp_type" value="aadhaar" <?= $otpType !== 'mobile' ? 'checked' : '' ?>> Aadhaar</label>
                    <label><input type="radio" name="otp_type" value="mobile"  <?= $otpType === 'mobile'  ? 'checked' : '' ?>> Mobile</label>
                </div>

                <label for="otp_input" id="input_label">Aadhaar Number</label>
                <input type="text" id="otp_input" name="otp_input" maxlength="12"
                       placeholder="999941057058" value="<?= esc($otpInput) ?>" required />

                <label for="mode">Mode</label>
                <select id="mode" name="mode">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>

                <button type="submit">Send OTP</button>
            </form>
        </div>

        <?php elseif ($step === 2): ?>
        <!-- ── Step 2: Enter the OTP ── -->
        <div class="card">
            <p class="note">OTP has been sent for <strong><?= esc($otpType === 'mobile' ? 'Mobile' : 'Aadhaar') ?></strong>
               <code><?= esc($otpInput ?: '—') ?></code>.
               Enter the 6-digit OTP below.</p>
            <form method="post" action="/admin/m1/otp-verify">
                <?= csrf_field() ?>
                <input type="hidden" name="otp_type" value="<?= esc($otpType) ?>">
                <input type="hidden" name="otp_input" value="<?= esc($otpInput) ?>">

                <label for="txn_id">Transaction ID (txnId)</label>
                <input type="text" id="txn_id" name="txn_id"
                       value="<?= esc($txnId) ?>"
                       placeholder="auto-filled from OTP response" />

                <?php if ($otpType === 'aadhaar'): ?>
                <label for="mobile">Primary Mobile Number <span style="font-weight:400;color:#6b7280;">(for ABHA communication)</span></label>
                <input type="tel" id="mobile" name="mobile" maxlength="10" placeholder="10-digit mobile number" required />
                <?php endif; ?>

                <label for="otp">OTP</label>
                <input type="text" id="otp" name="otp" maxlength="6" placeholder="123456" required autofocus />

                <label for="mode">Mode</label>
                <select id="mode" name="mode">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>

                <button type="submit">Verify OTP</button>
            </form>
            <form method="get" action="/admin/m1/otp-flow" style="margin-top:10px;">
                <button type="submit" class="secondary">← Start over</button>
            </form>
        </div>

        <?php elseif ($step === 3): ?>
        <!-- ── Step 3: Success ── -->
        <div class="card">
            <?php if (!empty($resultProfile) && is_array($resultProfile)): ?>
                <h2>Verified ABHA Profile</h2>
                <div class="mono"><?= esc(json_encode($resultProfile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></div>
            <?php else: ?>
                <p>OTP verified successfully. No profile data returned (test mode or empty response).</p>
            <?php endif; ?>
            <p style="margin-top:14px;">
                <a href="/admin/m1/abha-profiles">View all saved ABHA profiles</a> &nbsp;|&nbsp;
                <a href="/admin/m1/otp-flow">Run another validation</a>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Update label when OTP type radio changes
        const radios = document.querySelectorAll('input[name="otp_type"]');
        const inputLabel = document.getElementById('input_label');
        const otpInput = document.getElementById('otp_input');
        if (radios && inputLabel && otpInput) {
            radios.forEach(r => r.addEventListener('change', () => {
                if (r.value === 'mobile') {
                    inputLabel.textContent = 'Mobile Number';
                    otpInput.placeholder = '9999999999';
                    otpInput.maxLength = 10;
                } else {
                    inputLabel.textContent = 'Aadhaar Number';
                    otpInput.placeholder = '999941057058';
                    otpInput.maxLength = 12;
                }
            }));
        }
    </script>
</body>
</html>
