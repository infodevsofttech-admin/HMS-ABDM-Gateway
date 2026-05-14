<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'ABHA Verification Flow'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-check-circle"></i> ABHA Verification <small>Verify existing ABHA holders</small></h3>
    </div>
</div>
<div class="clearfix"></div>

<div class="row"><div class="col-md-8 col-md-offset-2">
<style>
        body { font-family: Arial, sans-serif; margin: 24px; background: #f8fafc; color: #111827; max-width: 620px; }
        h1 { margin-bottom: 4px; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px; color: #374151; }
        label span { font-weight: 400; color: #6b7280; }
        input, select { width: 100%; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box; margin-bottom: 14px; }
        input:focus, select:focus { outline: none; border-color: #6366f1; }
        button { background: #4f46e5; color: #fff; border: none; padding: 10px 22px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button:hover { background: #4338ca; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .steps { display: flex; gap: 0; margin-bottom: 20px; }
        .step { flex: 1; padding: 8px 0; text-align: center; font-size: 12px; font-weight: 600; background: #e5e7eb; color: #6b7280; border-right: 2px solid #fff; }
        .step:first-child { border-radius: 6px 0 0 6px; }
        .step:last-child { border-radius: 0 6px 6px 0; border-right: 0; }
        .step.active { background: #4f46e5; color: #fff; }
        .step.done   { background: #d1fae5; color: #065f46; }
        .profile-card { margin-top: 16px; }
        .profile-card table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .profile-card td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; }
        .profile-card td:first-child { color: #6b7280; font-weight: 600; width: 40%; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-active  { background: #d1fae5; color: #065f46; }
        .badge-inactive{ background: #fef9c3; color: #713f12; }
        .badge-std     { background: #e0e7ff; color: #3730a3; }
        .abha-num { font-size: 18px; font-weight: 700; color: #1d4ed8; letter-spacing: .05em; }
        .photo-wrap { text-align: center; margin-bottom: 16px; }
        .photo-wrap img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; border: 2px solid #e5e7eb; }
        .abha-list label { font-weight: 400; display: flex; align-items: center; gap: 8px; padding: 8px; border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px; cursor: pointer; }
        .abha-list input[type=radio] { width: auto; margin: 0; }
        a { color: #4f46e5; text-decoration: none; font-size: 13px; }
        .method-opts { display: grid; gap: 8px; margin-bottom: 16px; }
        .method-opt { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; cursor: pointer; display: flex; align-items: flex-start; gap: 10px; }
        .method-opt input[type=radio] { width: auto; margin: 4px 0 0; }
        .method-opt .label { font-weight: 600; font-size: 13px; }
        .method-opt .desc  { font-size: 12px; color: #6b7280; }
        .dl-btn { display: inline-block; margin-top: 14px; background: #059669; color: #fff; padding: 9px 18px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; }
        .dl-btn:hover { background: #047857; }
</style>

<?php
    $step          = $step ?? '1';
    $txnId         = $txnId ?? '';
    $verifyMethod  = $verifyMethod ?? 'abha-abdm';
    $loginId       = $loginId ?? '';
    $abhaList      = $abhaList ?? [];
    $resultProfile = $resultProfile ?? null;
    $xToken        = $xToken ?? '';
    ?>



    <?php if (!empty($message)): ?>
        <div class="ok"><?= esc((string) $message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="err"><?= esc((string) $error) ?></div>
    <?php endif; ?>

    <!-- Step indicator -->
    <div class="steps">
        <div class="step <?= $step === '1' ? 'active' : ($step !== '1' ? 'done' : '') ?>">1 · Choose Method</div>
        <div class="step <?= ($step === '2' || $step === '2b') ? 'active' : ($step === '3' ? 'done' : '') ?>">2 · Verify OTP</div>
        <div class="step <?= $step === '3' ? 'active' : '' ?>">3 · Profile</div>
    </div>

    <!-- ==================== STEP 1 ==================== -->
    <?php if ($step === '1'): ?>
    <div class="card">
        <form method="post" action="/admin/m1/verify-otp-request">
            <?= csrf_field() ?>
            <p style="font-size:13px;color:#374151;margin-top:0;">Choose how the patient will verify their ABHA.</p>

            <div class="method-opts">
                <label class="method-opt">
                    <input type="radio" name="verify_method" value="abha-abdm" <?= $verifyMethod === 'abha-abdm' ? 'checked' : '' ?>>
                    <span>
                        <span class="label">ABHA Number + ABDM OTP</span><br>
                        <span class="desc">OTP sent to mobile registered with ABHA. Most common.</span>
                    </span>
                </label>
                <label class="method-opt">
                    <input type="radio" name="verify_method" value="abha-aadhaar" <?= $verifyMethod === 'abha-aadhaar' ? 'checked' : '' ?>>
                    <span>
                        <span class="label">ABHA Number + Aadhaar OTP</span><br>
                        <span class="desc">OTP sent to Aadhaar-linked mobile via UIDAI.</span>
                    </span>
                </label>
                <label class="method-opt">
                    <input type="radio" name="verify_method" value="mobile" <?= $verifyMethod === 'mobile' ? 'checked' : '' ?>>
                    <span>
                        <span class="label">Mobile Number OTP</span><br>
                        <span class="desc">Enter mobile number; select ABHA if multiple are linked.</span>
                    </span>
                </label>
            </div>

            <div id="field-abha" style="<?= $verifyMethod === 'mobile' ? 'display:none' : '' ?>">
                <label for="login_id_abha">ABHA Number <span>(with or without hyphens)</span></label>
                <input type="text" id="login_id_abha" name="login_id_abha"
                       value="<?= $verifyMethod !== 'mobile' ? esc($loginId) : '' ?>"
                       placeholder="91-XXXX-XXXX-XXXX" maxlength="20">
            </div>
            <div id="field-mobile" style="<?= $verifyMethod !== 'mobile' ? 'display:none' : '' ?>">
                <label for="login_id_mobile">Mobile Number</label>
                <input type="tel" id="login_id_mobile" name="login_id_mobile"
                       value="<?= $verifyMethod === 'mobile' ? esc($loginId) : '' ?>"
                       placeholder="10-digit mobile number" maxlength="10">
            </div>
            <!-- Single hidden field that gets populated by JS -->
            <input type="hidden" id="login_id" name="login_id" value="<?= esc($loginId) ?>">

            <button type="submit">Send OTP →</button>
        </form>
    </div>

    <script>
    (function(){
        var radios = document.querySelectorAll('input[name=verify_method]');
        var fieldAbha   = document.getElementById('field-abha');
        var fieldMobile = document.getElementById('field-mobile');
        var hiddenId    = document.getElementById('login_id');

        function update() {
            var val = document.querySelector('input[name=verify_method]:checked').value;
            fieldAbha.style.display   = (val !== 'mobile') ? '' : 'none';
            fieldMobile.style.display = (val === 'mobile') ? '' : 'none';
        }
        radios.forEach(function(r){ r.addEventListener('change', update); });

        document.querySelector('form').addEventListener('submit', function(){
            var val = document.querySelector('input[name=verify_method]:checked').value;
            if (val === 'mobile') {
                hiddenId.value = document.getElementById('login_id_mobile').value;
            } else {
                hiddenId.value = document.getElementById('login_id_abha').value;
            }
        });
    })();
    </script>

    <!-- ==================== STEP 2 ==================== -->
    <?php elseif ($step === '2'): ?>
    <div class="card">
        <p style="font-size:13px;color:#374151;margin-top:0;">
            OTP sent to the mobile linked with
            <strong><?= esc($loginId) ?></strong>.
            Enter the 6-digit OTP below.
        </p>
        <form method="post" action="/admin/m1/verify-otp-confirm">
            <?= csrf_field() ?>
            <input type="hidden" name="verify_method" value="<?= esc($verifyMethod) ?>">
            <input type="hidden" name="txn_id"        value="<?= esc($txnId) ?>">
            <input type="hidden" name="login_id"      value="<?= esc($loginId) ?>">
            <label for="otp">OTP</label>
            <input type="text" id="otp" name="otp" maxlength="6" placeholder="6-digit OTP" autocomplete="one-time-code" style="letter-spacing:.15em;font-size:18px;" autofocus>
            <button type="submit">Verify OTP →</button>
        </form>
        <p style="margin-top:14px;"><a href="/admin/m1/verify-flow">← Start over</a></p>
    </div>

    <!-- ==================== STEP 2b (mobile: multiple ABHA) ==================== -->
    <?php elseif ($step === '2b'): ?>
    <div class="card">
        <p style="font-size:13px;color:#374151;margin-top:0;">
            Multiple ABHA numbers are linked to <strong><?= esc($loginId) ?></strong>.
            Select the one to verify.
        </p>
        <form method="post" action="/admin/m1/verify-user-select">
            <?= csrf_field() ?>
            <input type="hidden" name="txn_id"   value="<?= esc($txnId) ?>">
            <input type="hidden" name="login_id" value="<?= esc($loginId) ?>">
            <div class="abha-list">
                <?php foreach ($abhaList as $item):
                    $num  = is_array($item) ? ($item['ABHANumber'] ?? $item['abhaNumber'] ?? (string)$item) : (string)$item;
                    $name = is_array($item) ? ($item['name'] ?? $item['fullName'] ?? '') : '';
                ?>
                <label>
                    <input type="radio" name="abha_number" value="<?= esc($num) ?>" required>
                    <span>
                        <strong><?= esc($num) ?></strong>
                        <?php if ($name !== ''): ?>&nbsp;— <?= esc($name) ?><?php endif; ?>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <button type="submit">Confirm Selection →</button>
        </form>
        <p style="margin-top:14px;"><a href="/admin/m1/verify-flow">← Start over</a></p>
    </div>

    <!-- ==================== STEP 3 (result) ==================== -->
    <?php elseif ($step === '3' && is_array($resultProfile)): ?>
    <div class="card">
        <?php
        $p = $resultProfile;
        $abhaNum = (string) ($p['ABHANumber'] ?? $p['abhaNumber'] ?? '');
        $photo   = (string) ($p['photo'] ?? '');
        $status  = strtoupper((string) ($p['abhaStatus']  ?? $p['ABHA_Status'] ?? 'ACTIVE'));
        $type    = strtoupper((string) ($p['abhaType']    ?? ''));
        $firstName  = (string) ($p['firstName']  ?? '');
        $middleName = (string) ($p['middleName'] ?? '');
        $lastName   = (string) ($p['lastName']   ?? '');
        $fullName   = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
        if ($fullName === '') { $fullName = (string) ($p['name'] ?? $p['fullName'] ?? ''); }
        $phrRaw  = $p['phrAddress'] ?? $p['abhaAddress'] ?? null;
        $phrAddr = is_array($phrRaw) ? ($phrRaw[0] ?? '') : (string) ($phrRaw ?? '');
        ?>

        <?php if ($photo !== ''): ?>
        <div class="photo-wrap">
            <img src="data:image/jpeg;base64,<?= esc($photo) ?>" alt="ABHA photo">
        </div>
        <?php endif; ?>

        <div style="text-align:center;margin-bottom:16px;">
            <div class="abha-num"><?= esc($abhaNum) ?></div>
            <?php if ($type !== ''): ?>
                <span class="badge badge-std" style="margin-top:4px;"><?= esc($type) ?></span>
            <?php endif; ?>
            &nbsp;
            <span class="badge <?= $status === 'ACTIVE' ? 'badge-active' : 'badge-inactive' ?>"><?= esc($status) ?></span>
        </div>

        <div class="profile-card">
            <table>
                <?php if ($fullName !== ''): ?>
                <tr><td>Name</td><td><strong><?= esc($fullName) ?></strong></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['gender'])): ?>
                <tr><td>Gender</td><td><?= $p['gender'] === 'M' ? 'Male' : ($p['gender'] === 'F' ? 'Female' : esc((string)$p['gender'])) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['dob'])): ?>
                <tr><td>Date of Birth</td><td><?= esc((string)$p['dob']) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['mobile'])): ?>
                <tr><td>Mobile</td><td>
                    <?= esc((string)$p['mobile']) ?>
                    <?php if (!empty($p['mobileVerified'])): ?>&nbsp;<span style="font-size:11px;color:#059669;">✓ verified</span><?php endif; ?>
                </td></tr>
                <?php endif; ?>
                <?php if ($phrAddr !== ''): ?>
                <tr><td>ABHA Address</td><td style="color:#7c3aed;font-size:12px;"><?= esc($phrAddr) ?></td></tr>
                <?php endif; ?>
                <?php if (!empty($p['address'])): ?>
                <tr><td>Address</td><td style="font-size:12px;"><?= esc((string)$p['address']) ?></td></tr>
                <?php endif; ?>
                <?php
                $dist  = (string)($p['districtName'] ?? '');
                $state = (string)($p['stateName'] ?? '');
                $pin   = (string)($p['pinCode'] ?? '');
                $loc   = implode(', ', array_filter([$dist, $state, $pin]));
                if ($loc !== ''): ?>
                <tr><td>Location</td><td style="font-size:12px;"><?= esc($loc) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>

        <?php if ($xToken !== ''): ?>
        <div style="margin-top:20px;">
            <p style="font-size:12px;color:#6b7280;margin-bottom:6px;">ABHA Card</p>
            <img src="/admin/m1/abha-card?x_token=<?= urlencode($xToken) ?>"
                 alt="ABHA Card"
                 style="max-width:100%;border-radius:8px;border:1px solid #e5e7eb;display:block;"
                 onerror="this.replaceWith(document.createTextNode('Card not available — token may have expired.'))">
            <a href="/admin/m1/abha-card?x_token=<?= urlencode($xToken) ?>" download="abha-card"
               style="display:inline-block;margin-top:8px;font-size:12px;color:#4f46e5;">⬇ Save card image</a>
        </div>
        <?php endif; ?>

        <p style="margin-top:20px;">
            <a href="/admin/m1/abha-profiles">View Patient Master →</a>
            &nbsp;|&nbsp;
            <a href="/admin/m1/verify-flow">Verify another patient</a>
        </p>
    </div>
    <?php endif; ?>

</div></div>

<?= $this->endSection() ?>
