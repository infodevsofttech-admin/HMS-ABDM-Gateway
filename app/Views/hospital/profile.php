<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Hospital Profile'; ?>

<?= $this->section('content') ?>

<?php
$hospital = $hospital ?? null;
$name     = is_object($hospital) ? ($hospital->hospital_name ?? '—') : (is_array($hospital) ? ($hospital['hospital_name'] ?? '—') : '—');
$hfrId    = is_object($hospital) ? ($hospital->hfr_id ?? '—') : (is_array($hospital) ? ($hospital['hfr_id'] ?? '—') : '—');
$mode     = is_object($hospital) ? ($hospital->gateway_mode ?? 'test') : (is_array($hospital) ? ($hospital['gateway_mode'] ?? 'test') : 'test');
$contact  = is_object($hospital) ? ($hospital->contact_name ?? '—') : (is_array($hospital) ? ($hospital['contact_name'] ?? '—') : '—');
$email    = is_object($hospital) ? ($hospital->contact_email ?? '—') : (is_array($hospital) ? ($hospital['contact_email'] ?? '—') : '—');
$phone    = is_object($hospital) ? ($hospital->contact_phone ?? '—') : (is_array($hospital) ? ($hospital['contact_phone'] ?? '—') : '—');
$isActive = is_object($hospital) ? ($hospital->is_active ?? 1) : (is_array($hospital) ? ($hospital['is_active'] ?? 1) : 1);
$createdAt= is_object($hospital) ? ($hospital->created_at ?? '—') : (is_array($hospital) ? ($hospital['created_at'] ?? '—') : '—');
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-hospital"></i> Hospital Profile</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item active">Profile</li>
        </ol></nav>
    </div>
</div>

<div class="hp-content">

    <div class="alert alert-info" style="font-size:13px;">
        <i class="fas fa-info-circle mr-2"></i>
        This profile is managed by your system administrator. To update any details, contact your admin.
    </div>

    <div class="row">
        <div class="col-md-6">
            <!-- Hospital Details -->
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-hospital-alt"></i> Hospital Details</div>
                <div class="hp-card-body" style="padding:0;">
                    <table class="hp-tbl">
                        <tbody>
                        <tr>
                            <td style="font-weight:600;color:#495057;width:40%;padding:14px 20px;">Hospital Name</td>
                            <td style="padding:14px 20px;font-weight:700;font-size:15px;"><?= esc((string)$name) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">HFR ID</td>
                            <td style="padding:14px 20px;">
                                <span style="font-family:monospace;font-size:14px;background:#f0f4f8;padding:3px 8px;border-radius:4px;"><?= esc((string)$hfrId) ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">Gateway Mode</td>
                            <td style="padding:14px 20px;">
                                <span class="hb hb-<?= $mode === 'live' ? 'red' : 'blue' ?>">
                                    <i class="fas fa-circle" style="font-size:7px;vertical-align:middle;"></i>
                                    <?= strtoupper(esc((string)$mode)) ?> MODE
                                </span>
                                <div style="font-size:11px;color:#6c757d;margin-top:4px;">
                                    <?= $mode === 'live' ? 'Live ABDM integration active.' : 'Test/Sandbox mode. No real ABDM calls are made.' ?>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">Status</td>
                            <td style="padding:14px 20px;">
                                <span class="hb hb-<?= $isActive ? 'green' : 'red' ?>"><?= $isActive ? 'ACTIVE' : 'INACTIVE' ?></span>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">Registered On</td>
                            <td style="padding:14px 20px;font-size:13px;color:#6c757d;"><?= esc((string)$createdAt) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Contact Details -->
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-address-card"></i> Contact Information</div>
                <div class="hp-card-body" style="padding:0;">
                    <table class="hp-tbl">
                        <tbody>
                        <tr>
                            <td style="font-weight:600;color:#495057;width:40%;padding:14px 20px;">Contact Person</td>
                            <td style="padding:14px 20px;"><?= esc((string)$contact) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">Email</td>
                            <td style="padding:14px 20px;">
                                <?php if ($email !== '—' && $email !== ''): ?>
                                <a href="mailto:<?= esc((string)$email) ?>"><?= esc((string)$email) ?></a>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">Phone</td>
                            <td style="padding:14px 20px;"><?= esc((string)$phone) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Portal user info -->
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-user-shield"></i> Your Portal Account</div>
                <div class="hp-card-body" style="padding:0;">
                    <table class="hp-tbl">
                        <tbody>
                        <tr>
                            <td style="font-weight:600;color:#495057;width:40%;padding:14px 20px;">Username</td>
                            <td style="padding:14px 20px;font-weight:700;"><?= esc((string) session()->get('username')) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:14px 20px;">Role</td>
                            <td style="padding:14px 20px;">
                                <span class="hb hb-blue"><?= esc(strtoupper((string) session()->get('role'))) ?></span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="row" style="margin-top:4px;">
        <div class="col-md-6">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-plug"></i> HMS API Configuration</div>
                <div class="hp-card-body" style="padding:0;">
                    <?php if (!empty($credential)): ?>
                    <table class="hp-tbl">
                        <tbody>
                        <tr>
                            <td style="font-weight:600;color:#495057;width:38%;padding:12px 18px;">HMS Name</td>
                            <td style="padding:12px 18px;"><?= esc((string)($credential->hms_name ?? '—')) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:12px 18px;">Gateway URL</td>
                            <td style="padding:12px 18px;font-family:monospace;font-size:12px;word-break:break-all;">
                                <?= esc((string)$api_endpoint) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:12px 18px;">API Key</td>
                            <td style="padding:12px 18px;">
                                <?php if (!empty($masked_key)): ?>
                                    <code style="font-size:12px;letter-spacing:1px;background:#f0f4f8;padding:3px 8px;border-radius:4px;"><?= esc($masked_key) ?></code>
                                    <div style="font-size:11px;color:#6c757d;margin-top:4px;">Contact admin to regenerate or receive key by email.</div>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size:13px;">Not configured yet</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:12px 18px;">Auth Header</td>
                            <td style="padding:12px 18px;font-family:monospace;font-size:12px;">
                                Authorization: Bearer &lt;api-key&gt;
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:12px 18px;">HFR ID</td>
                            <td style="padding:12px 18px;font-family:monospace;font-size:13px;">
                                <?= esc((string)$hfrId) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight:600;color:#495057;padding:12px 18px;">Status</td>
                            <td style="padding:12px 18px;">
                                <span class="hb hb-<?= ($credential->is_active ?? 0) ? 'green' : 'red' ?>">
                                    <?= ($credential->is_active ?? 0) ? 'ACTIVE' : 'INACTIVE' ?>
                                </span>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div style="padding:20px 18px;color:#6c757d;font-size:13px;">
                        <i class="fas fa-info-circle mr-1"></i>
                        No HMS API credential configured yet. Please contact your system administrator.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="row" style="margin-top:4px;">
        <div class="col-md-6">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-key"></i> Change Password</div>
                <div class="hp-card-body">
                    <?php if (session()->getFlashdata('pw_error')): ?>
                    <div class="alert alert-danger" style="font-size:13px;padding:10px 14px;">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?= esc(session()->getFlashdata('pw_error')) ?>
                    </div>
                    <?php endif; ?>
                    <?php if (session()->getFlashdata('pw_success')): ?>
                    <div class="alert alert-success" style="font-size:13px;padding:10px 14px;">
                        <i class="fas fa-check-circle mr-1"></i> <?= esc(session()->getFlashdata('pw_success')) ?>
                    </div>
                    <?php endif; ?>
                    <form method="post" action="/portal/profile/change-password">
                        <?= csrf_field() ?>
                        <div class="form-group mb-3">
                            <label style="font-size:13px;font-weight:600;">Current Password <span style="color:#dc3545;">*</span></label>
                            <input type="password" name="current_password" class="form-control" style="font-size:13px;" required autocomplete="current-password">
                        </div>
                        <div class="form-group mb-3">
                            <label style="font-size:13px;font-weight:600;">New Password <span style="color:#dc3545;">*</span></label>
                            <input type="password" name="new_password" id="newPw" class="form-control" style="font-size:13px;" required minlength="8" autocomplete="new-password">
                            <div style="font-size:11px;color:#6c757d;margin-top:3px;">Minimum 8 characters.</div>
                        </div>
                        <div class="form-group mb-4">
                            <label style="font-size:13px;font-weight:600;">Confirm New Password <span style="color:#dc3545;">*</span></label>
                            <input type="password" name="confirm_password" id="confirmPw" class="form-control" style="font-size:13px;" required autocomplete="new-password">
                            <div id="pwMatchMsg" style="font-size:11px;margin-top:3px;"></div>
                        </div>
                        <button type="submit" class="btn btn-primary" style="font-size:13px;">
                            <i class="fas fa-save mr-1"></i> Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.hp-content -->

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function() {
    var np = document.getElementById('newPw');
    var cp = document.getElementById('confirmPw');
    var msg = document.getElementById('pwMatchMsg');
    function check() {
        if (cp.value === '') { msg.textContent = ''; return; }
        if (np.value === cp.value) {
            msg.textContent = '✓ Passwords match';
            msg.style.color = '#198754';
        } else {
            msg.textContent = '✗ Passwords do not match';
            msg.style.color = '#dc3545';
        }
    }
    np.addEventListener('input', check);
    cp.addEventListener('input', check);
})();
</script>
<?= $this->endSection() ?>
