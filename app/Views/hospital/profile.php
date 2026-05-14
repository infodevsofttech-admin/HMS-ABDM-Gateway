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

</div><!-- /.hp-content -->

<?= $this->endSection() ?>
