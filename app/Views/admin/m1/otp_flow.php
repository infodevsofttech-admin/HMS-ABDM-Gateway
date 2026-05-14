<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>ABHA OTP Flow - M1 Module</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; max-width: 600px; }
        .card { background: #fff; border-radius: 10px; padding: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        .stack { display: grid; gap: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; margin-top: 14px; font-size: 13px; }
        label:first-child { margin-top: 0; }
        label span { font-weight: 400; color: #6b7280; }
        input, select, textarea { width: 100%; font: inherit; padding: 9px 10px; border: 1px solid #d1d5db; border-radius: 6px; box-sizing: border-box; font-size: 13px; }
        button { background: #2563eb; color: #fff; border: 0; cursor: pointer; font-weight: 600; margin-top: 14px; padding: 10px 20px; border-radius: 6px; font-size: 13px; }
        button:hover { background: #1d4ed8; }
        button.secondary { background: #6b7280; }
        button.secondary:hover { background: #4b5563; }
        button.skip { background: #e5e7eb; color: #374151; }
        button.skip:hover { background: #d1d5db; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px; border-radius: 8px; font-size: 13px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 13px; }
        .steps { display: flex; gap: 0; margin-bottom: 20px; }
        .step { flex: 1; padding: 7px 0; text-align: center; font-size: 11px; font-weight: 700; background: #e2e8f0; color: #64748b; border-right: 2px solid #f8fafc; }
        .step:first-child { border-radius: 6px 0 0 6px; }
        .step:last-child  { border-radius: 0 6px 6px 0; border-right: 0; }
        .step.active { background: #2563eb; color: #fff; }
        .step.done   { background: #d1fae5; color: #065f46; }
        .note { color: #475569; font-size: 13px; margin-bottom: 14px; }
        a { color: #2563eb; text-decoration: none; }
        .radio-row { display: flex; gap: 24px; margin-top: 4px; }
        .radio-row label { font-weight: normal; display: flex; align-items: center; gap: 6px; margin-top: 0; }
        .radio-row input { width: auto; }
        /* Address selection */
        .addr-chips { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 16px; }
        .addr-chip  { padding: 7px 14px; border: 2px solid #d1d5db; border-radius: 999px; cursor: pointer; font-size: 13px; background: #fff; transition: border-color .15s; }
        .addr-chip:hover, .addr-chip.selected { border-color: #2563eb; background: #eff6ff; color: #1d4ed8; font-weight: 600; }
        /* Profile card */
        .profile-table { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
        .profile-table td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
        .profile-table td:first-child { color: #6b7280; font-weight: 600; width: 38%; }
        .abha-num { font-size: 20px; font-weight: 700; color: #1d4ed8; letter-spacing: .04em; text-align: center; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-active   { background: #d1fae5; color: #065f46; }
        .badge-inactive { background: #fef9c3; color: #713f12; }
        .photo-wrap { text-align: center; margin-bottom: 14px; }
        .photo-wrap img { width: 72px; height: 72px; border-radius: 8px; object-fit: cover; border: 1px solid #e5e7eb; }
    </style>
</head>
<body>
    <?php
    $step          = (int) ($step ?? 1);
    $txnId         = (string) ($txnId ?? '');
    $otpType       = (string) ($otpType ?? 'aadhaar');
    $otpInput      = (string) ($otpInput ?? '');
    $resultProfile = $resultProfile ?? null;
    $suggestions   = $suggestions ?? [];
    $enrolTxnId    = $enrolTxnId ?? '';
    $xToken        = $xToken ?? '';
    $abhaNumber    = $abhaNumber ?? '';
    ?>

    <p><a href="/admin/m1">← Back to M1 Suite</a></p>
    <h1>Create ABHA (OTP Flow)</h1>

    <div class="steps">
        <div class="step <?= $step === 1 ? 'active' : ($step > 1 ? 'done' : '') ?>">1 · Enter ID</div>
        <div class="step <?= $step === 2 ? 'active' : ($step > 2 ? 'done' : '') ?>">2 · Verify OTP</div>
        <div class="step <?= $step === 3 ? 'active' : ($step > 3 ? 'done' : '') ?>">3 · ABHA Address</div>
        <div class="step <?= $step === 4 ? 'active' : '' ?>">4 · Done</div>
    </div>

    <div class="stack">
        <?php if (!empty($message)): ?>
            <div class="ok"><?= esc((string) $message) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="err"><?= esc((string) $error) ?></div>
        <?php endif; ?>

        <?php if ($step <= 1): ?>
        <!-- ── Step 1 ── -->
        <div class="card">
            <p class="note">Select the OTP method, enter the Aadhaar or mobile number, and click <strong>Send OTP</strong>.</p>
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
                <button type="submit">Send OTP →</button>
            </form>
        </div>

        <?php elseif ($step === 2): ?>
        <!-- ── Step 2 ── -->
        <div class="card">
            <p class="note">OTP sent for <strong><?= esc($otpType === 'mobile' ? 'Mobile' : 'Aadhaar') ?></strong>
               <code><?= esc($otpInput ?: '—') ?></code>. Enter the 6-digit OTP below.</p>
            <form method="post" action="/admin/m1/otp-verify">
                <?= csrf_field() ?>
                <input type="hidden" name="otp_type"  value="<?= esc($otpType) ?>">
                <input type="hidden" name="otp_input" value="<?= esc($otpInput) ?>">
                <label for="txn_id">Transaction ID (txnId)</label>
                <input type="text" id="txn_id" name="txn_id" value="<?= esc($txnId) ?>"
                       placeholder="auto-filled from OTP response" />
                <?php if ($otpType === 'aadhaar'): ?>
                <label for="mobile">Primary Mobile Number <span>(for ABHA communication)</span></label>
                <input type="tel" id="mobile" name="mobile" maxlength="10" placeholder="10-digit mobile number" required />
                <?php endif; ?>
                <label for="otp">OTP</label>
                <input type="text" id="otp" name="otp" maxlength="6" placeholder="123456" required autofocus />
                <label for="mode">Mode</label>
                <select id="mode" name="mode">
                    <option value="sandbox">Sandbox</option>
                    <option value="live">Live</option>
                </select>
                <button type="submit">Verify OTP →</button>
            </form>
            <form method="get" action="/admin/m1/otp-flow" style="margin-top:10px;">
                <button type="submit" class="secondary">← Start over</button>
            </form>
        </div>

        <?php elseif ($step === 3): ?>
        <!-- ── Step 3: ABHA Address Selection ── -->
        <div class="card">
            <p class="note">
                ABHA <strong><?= esc($abhaNumber) ?></strong> created.
                <?php if (count($suggestions) > 0): ?>
                    Choose a preferred ABHA address or type a custom one.
                <?php else: ?>
                    Enter a preferred ABHA address (e.g. <em>firstname.lastname</em>) or skip to use the default.
                <?php endif; ?>
            </p>
            <form method="post" action="/admin/m1/otp-address-set" id="addr-form">
                <?= csrf_field() ?>
                <input type="hidden" name="enrol_txn_id" value="<?= esc($enrolTxnId) ?>">
                <input type="hidden" name="x_token"      value="<?= esc($xToken) ?>">
                <input type="hidden" name="abha_number"  value="<?= esc($abhaNumber) ?>">

                <?php if (count($suggestions) > 0): ?>
                <label>Suggested ABHA Addresses</label>
                <div class="addr-chips" id="chip-list">
                    <?php foreach ($suggestions as $s): ?>
                        <span class="addr-chip" onclick="selectChip(this, '<?= esc($s) ?>')"><?= esc($s) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <label for="abha_address">ABHA Address <span>(choose above or type custom; leave blank to keep default)</span></label>
                <input type="text" id="abha_address" name="abha_address"
                       placeholder="e.g. devender.singh@sbx" autocomplete="off">

                <div style="display:flex;gap:10px;margin-top:14px;">
                    <button type="submit">Set Address &amp; Continue →</button>
                    <button type="submit" name="skip_address" value="1" class="skip">Skip →</button>
                </div>
            </form>
        </div>

        <?php elseif ($step === 4): ?>
        <!-- ── Step 4: Done – Profile + ABHA Card ── -->
        <div class="card">
            <?php if (!empty($resultProfile) && is_array($resultProfile)):
                $p = $resultProfile;
                $num   = (string)($p['ABHANumber'] ?? $p['abhaNumber'] ?? $abhaNumber);
                $photo = (string)($p['photo'] ?? '');
                $status = strtoupper((string)($p['abhaStatus'] ?? $p['ABHA_Status'] ?? 'ACTIVE'));
                $fn  = trim(implode(' ', array_filter([(string)($p['firstName']??''), (string)($p['middleName']??''), (string)($p['lastName']??'')])));
                if ($fn === '') { $fn = (string)($p['name'] ?? $p['fullName'] ?? ''); }
                $phrRaw = $p['phrAddress'] ?? $p['abhaAddress'] ?? null;
                $phr    = is_array($phrRaw) ? ($phrRaw[0] ?? '') : (string)($phrRaw ?? '');
            ?>
            <?php if ($photo !== ''): ?>
            <div class="photo-wrap">
                <img src="data:image/jpeg;base64,<?= esc($photo) ?>" alt="ABHA photo">
            </div>
            <?php endif; ?>
            <div class="abha-num"><?= esc($num) ?></div>
            <div style="text-align:center;margin:6px 0 14px;">
                <span class="badge <?= $status === 'ACTIVE' ? 'badge-active' : 'badge-inactive' ?>"><?= esc($status) ?></span>
            </div>
            <table class="profile-table">
                <?php if ($fn !== ''): ?>
                <tr><td>Name</td><td><strong><?= esc($fn) ?></strong></td></tr><?php endif; ?>
                <?php if (!empty($p['gender'])): ?>
                <tr><td>Gender</td><td><?= $p['gender']==='M'?'Male':($p['gender']==='F'?'Female':esc((string)$p['gender'])) ?></td></tr><?php endif; ?>
                <?php if (!empty($p['dob'])): ?>
                <tr><td>Date of Birth</td><td><?= esc((string)$p['dob']) ?></td></tr><?php endif; ?>
                <?php if (!empty($p['mobile'])): ?>
                <tr><td>Mobile</td><td><?= esc((string)$p['mobile']) ?><?php if (!empty($p['mobileVerified'])): ?> <span style="font-size:11px;color:#059669;">✓ verified</span><?php endif; ?></td></tr><?php endif; ?>
                <?php if ($phr !== ''): ?>
                <tr><td>ABHA Address</td><td style="color:#7c3aed;font-size:12px;"><?= esc($phr) ?></td></tr><?php endif; ?>
                <?php if (!empty($p['address'])): ?>
                <tr><td>Address</td><td style="font-size:12px;"><?= esc((string)$p['address']) ?></td></tr><?php endif; ?>
            </table>
            <?php endif; ?>

            <?php if ($xToken !== ''): ?>
            <div style="margin-top:18px;">
                <p style="font-size:12px;color:#6b7280;margin-bottom:6px;">ABHA Card</p>
                <img src="/admin/m1/abha-card?x_token=<?= urlencode($xToken) ?>"
                     alt="ABHA Card"
                     style="max-width:100%;border-radius:8px;border:1px solid #e5e7eb;display:block;"
                     onerror="this.replaceWith(document.createTextNode('Card not available — token may have expired.'))">
                <a href="/admin/m1/abha-card?x_token=<?= urlencode($xToken) ?>" download="abha-card"
                   style="display:inline-block;margin-top:6px;font-size:12px;color:#2563eb;">⬇ Save card image</a>
            </div>
            <?php endif; ?>

            <p style="margin-top:20px;font-size:13px;">
                <a href="/admin/m1/abha-profiles">View Patient Master →</a>
                &nbsp;|&nbsp;
                <a href="/admin/m1/otp-flow">Create another ABHA</a>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Step 1: update label when OTP type changes
        const radios = document.querySelectorAll('input[name="otp_type"]');
        const inputLabel = document.getElementById('input_label');
        const otpInput   = document.getElementById('otp_input');
        if (radios && inputLabel && otpInput) {
            radios.forEach(r => r.addEventListener('change', () => {
                if (r.value === 'mobile') {
                    inputLabel.textContent = 'Mobile Number';
                    otpInput.placeholder   = '9999999999';
                    otpInput.maxLength     = 10;
                } else {
                    inputLabel.textContent = 'Aadhaar Number';
                    otpInput.placeholder   = '999941057058';
                    otpInput.maxLength     = 12;
                }
            }));
        }
        // Step 3: chip selection fills the text input
        function selectChip(el, addr) {
            document.querySelectorAll('.addr-chip').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            document.getElementById('abha_address').value = addr;
        }
    </script>
</body>
</html>
