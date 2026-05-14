<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hospital Registration - ABDM Gateway</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;
             background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;
             display:flex;align-items:flex-start;justify-content:center;padding:30px 20px}
        .card{background:#fff;padding:40px;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);
              width:100%;max-width:680px;margin:auto}
        h1{font-size:22px;color:#1f2937;margin-bottom:4px;text-align:center}
        .brand-logo{display:block;height:44px;width:auto;margin:0 auto 8px}
        .brand-divider{width:40px;height:3px;background:linear-gradient(90deg,#667eea,#764ba2);border-radius:2px;margin:6px auto 10px}
        .sub{color:#6b7280;font-size:14px;text-align:center;margin-bottom:28px}
        .section-title{font-size:12px;font-weight:700;color:#764ba2;text-transform:uppercase;
                       letter-spacing:.8px;margin:20px 0 12px;padding-bottom:6px;
                       border-bottom:2px solid #f3f0ff}
        .row{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .form-group{margin-bottom:16px}
        label{display:block;margin-bottom:6px;color:#374151;font-weight:600;font-size:13px}
        label span{color:#e53e3e;margin-left:2px}
        input[type=text],input[type=email],input[type=password],input[type=tel],select,textarea{
            width:100%;padding:10px 13px;border:1.5px solid #d1d5db;border-radius:8px;
            font-size:14px;transition:border-color .2s;font-family:inherit;background:#fff}
        input:focus,select:focus,textarea:focus{outline:none;border-color:#764ba2;box-shadow:0 0 0 3px rgba(118,75,162,.1)}
        textarea{resize:vertical;min-height:75px}
        .hint{font-size:11px;color:#9ca3af;margin-top:3px}
        .alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:20px}
        .alert-danger{background:#fef2f2;border:1px solid #fca5a5;color:#991b1b}
        .alert-success{background:#f0fdf4;border:1px solid #86efac;color:#166534;text-align:center;padding:30px}
        .alert-success i{font-size:48px;display:block;margin-bottom:12px}
        .btn{width:100%;padding:13px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;
             border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:8px;
             transition:opacity .2s}
        .btn:hover{opacity:.9}
        .footer-link{text-align:center;margin-top:18px;font-size:13px;color:#6b7280}
        .footer-link a{color:#764ba2;font-weight:600;text-decoration:none}
        #pwMatch{font-size:11px;margin-top:3px}
        @media(max-width:600px){.row{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="card">
    <img src="/assets/img/e-atria-logo.png" alt="E-Atria" class="brand-logo">
    <div class="brand-divider"></div>
    <h1>ABDM Gateway</h1>
    <p class="sub">Hospital Registration Request</p>

    <?php if (!empty($submitted)): ?>
    <div class="alert alert-success">
        <i>✅</i>
        <strong>Application Submitted Successfully!</strong><br><br>
        Your registration request has been received and is pending admin review.<br>
        You will be notified at your contact email once the account is activated.<br><br>
        <a href="/" style="color:#166534;font-weight:600;">← Back to Login</a>
    </div>

    <?php else: ?>

    <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger">⚠ <?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <form method="post" action="/auth/register">
        <?= csrf_field() ?>

        <!-- Hospital Info -->
        <div class="section-title">🏥 Hospital Information</div>
        <div class="form-group">
            <label>Hospital Name <span>*</span></label>
            <input type="text" name="hospital_name" value="<?= esc(old('hospital_name')) ?>" required placeholder="e.g. City General Hospital">
        </div>
        <div class="row">
            <div class="form-group">
                <label>HFR ID (Health Facility Registry)</label>
                <input type="text" name="hfr_id" value="<?= esc(old('hfr_id')) ?>" placeholder="e.g. IN2600XXX">
                <div class="hint">Leave blank if not yet registered with HFR.</div>
            </div>
            <div class="form-group">
                <label>State <span>*</span></label>
                <select name="state" required>
                    <option value="">-- Select State --</option>
                    <?php
                    $states = ['Andhra Pradesh','Arunachal Pradesh','Assam','Bihar','Chhattisgarh','Goa','Gujarat','Haryana','Himachal Pradesh','Jharkhand','Karnataka','Kerala','Madhya Pradesh','Maharashtra','Manipur','Meghalaya','Mizoram','Nagaland','Odisha','Punjab','Rajasthan','Sikkim','Tamil Nadu','Telangana','Tripura','Uttar Pradesh','Uttarakhand','West Bengal','Andaman and Nicobar Islands','Chandigarh','Dadra and Nagar Haveli and Daman and Diu','Delhi','Jammu and Kashmir','Ladakh','Lakshadweep','Puducherry'];
                    $sel = old('state');
                    foreach ($states as $s) echo '<option value="' . esc($s) . '"' . ($sel === $s ? ' selected' : '') . '>' . esc($s) . '</option>';
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>City / District <span>*</span></label>
            <input type="text" name="city" value="<?= esc(old('city')) ?>" required placeholder="e.g. Lucknow">
        </div>
        <div class="form-group">
            <label>Brief Description</label>
            <textarea name="description" placeholder="Tell us about your hospital and intended use of ABDM Gateway..."><?= esc(old('description')) ?></textarea>
        </div>

        <!-- Contact Info -->
        <div class="section-title">👤 Contact Information</div>
        <div class="row">
            <div class="form-group">
                <label>Contact Person Name <span>*</span></label>
                <input type="text" name="contact_name" value="<?= esc(old('contact_name')) ?>" required placeholder="Full name">
            </div>
            <div class="form-group">
                <label>Contact Phone <span>*</span></label>
                <input type="tel" name="contact_phone" value="<?= esc(old('contact_phone')) ?>" required placeholder="10-digit mobile number" pattern="[0-9]{10,15}">
            </div>
        </div>
        <div class="form-group">
            <label>Contact Email <span>*</span></label>
            <input type="email" name="contact_email" value="<?= esc(old('contact_email')) ?>" required placeholder="official@hospital.com">
            <div class="hint">Approval notification will be sent to this email.</div>
        </div>

        <!-- Login Credentials -->
        <div class="section-title">🔐 Portal Login Credentials</div>
        <div class="form-group">
            <label>Desired Username <span>*</span></label>
            <input type="text" name="username" value="<?= esc(old('username')) ?>" required placeholder="e.g. cityhospital_admin" pattern="[a-zA-Z0-9_\-]{4,80}">
            <div class="hint">4–80 characters, letters/numbers/underscore/hyphen only.</div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>Password <span>*</span></label>
                <input type="password" name="password" id="pw" required minlength="8" placeholder="Min 8 characters">
            </div>
            <div class="form-group">
                <label>Confirm Password <span>*</span></label>
                <input type="password" name="confirm_password" id="cpw" required minlength="8" placeholder="Re-enter password">
                <div id="pwMatch"></div>
            </div>
        </div>

        <button type="submit" class="btn">Submit Registration Request</button>
    </form>

    <?php endif; ?>

    <div class="footer-link">Already have an account? <a href="/">Login here</a></div>
</div>
<script>
(function(){
    var p=document.getElementById('pw'), c=document.getElementById('cpw'), m=document.getElementById('pwMatch');
    if (!p || !c) return;
    function chk(){
        if(!c.value){m.textContent='';return}
        if(p.value===c.value){m.textContent='✓ Passwords match';m.style.color='#166534';}
        else{m.textContent='✗ Do not match';m.style.color='#991b1b';}
    }
    p.addEventListener('input',chk); c.addEventListener('input',chk);
})();
</script>
</body>
</html>
