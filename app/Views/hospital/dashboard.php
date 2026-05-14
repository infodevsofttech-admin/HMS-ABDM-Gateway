<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Dashboard'; ?>

<?= $this->section('content') ?>

<?php
$hospital = $hospital ?? null;
$hospitalName = is_object($hospital) ? ($hospital->hospital_name ?? 'Your Hospital')
    : (is_array($hospital) ? ($hospital['hospital_name'] ?? 'Your Hospital') : 'Your Hospital');
$hfrId = is_object($hospital) ? ($hospital->hfr_id ?? '')
    : (is_array($hospital) ? ($hospital['hfr_id'] ?? '') : '');
$mode = is_object($hospital) ? ($hospital->gateway_mode ?? 'test')
    : (is_array($hospital) ? ($hospital['gateway_mode'] ?? 'test') : 'test');
?>

<!-- Page header -->
<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-home"></i> Dashboard</h5>
        <nav><ol class="breadcrumb"><li class="breadcrumb-item active">Home</li></ol></nav>
    </div>
    <div class="d-flex align-items-center gap-2" style="gap:8px;">
        <span class="hb hb-<?= $mode === 'live' ? 'red' : 'blue' ?>" style="font-size:12px;padding:5px 12px;">
            <i class="fas fa-circle" style="font-size:7px;vertical-align:middle;"></i>
            <?= strtoupper(esc($mode)) ?> MODE
        </span>
        <?php if ($hfrId !== ''): ?>
        <span class="hb hb-blue" style="font-size:12px;padding:5px 12px;">HFR: <?= esc($hfrId) ?></span>
        <?php endif; ?>
    </div>
</div>

<div class="hp-content">

    <!-- Hospital info banner -->
    <div class="hp-card" style="background: linear-gradient(120deg, var(--hp-dark) 0%, var(--hp-primary) 100%); border:0;">
        <div class="hp-card-body" style="padding: 24px 28px;">
            <div class="d-flex align-items-center" style="gap:16px;">
                <div style="width:52px;height:52px;background:rgba(255,255,255,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-hospital-alt" style="font-size:24px;color:#fff;"></i>
                </div>
                <div>
                    <div style="color:rgba(255,255,255,.6);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Logged in as</div>
                    <div style="color:#fff;font-size:20px;font-weight:700;margin:2px 0;"><?= esc($hospitalName) ?></div>
                    <?php if ($hfrId !== ''): ?>
                    <div style="color:rgba(255,255,255,.6);font-size:12px;">
                        <i class="fas fa-id-card-alt mr-1"></i> HFR ID: <?= esc($hfrId) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Placeholder: more items coming -->
    <div class="hp-card">
        <div class="hp-card-body" style="text-align:center;padding:60px 20px;">
            <i class="fas fa-tools" style="font-size:48px;color:#dee2e6;display:block;margin-bottom:16px;"></i>
            <h5 style="color:#6c757d;font-weight:600;">More features coming soon</h5>
            <p style="color:#adb5bd;font-size:14px;margin:0;">
                This portal is ready. Tell us what to add here.
            </p>
        </div>
    </div>

</div>

<?= $this->endSection() ?>


<?= $this->section('content') ?>

<?php
$hospital = $hospital ?? null;
$hospitalName = is_object($hospital) ? ($hospital->hospital_name ?? 'Your Hospital')
    : (is_array($hospital) ? ($hospital['hospital_name'] ?? 'Your Hospital') : 'Your Hospital');
$hfrId = is_object($hospital) ? ($hospital->hfr_id ?? '')
    : (is_array($hospital) ? ($hospital['hfr_id'] ?? '') : '');
$mode = is_object($hospital) ? ($hospital->gateway_mode ?? 'test')
    : (is_array($hospital) ? ($hospital['gateway_mode'] ?? 'test') : 'test');
$recentProfiles = $recentProfiles ?? [];
?>

<!-- Page Header -->
<div class="hp-page-header">
    <div>
        <h4><i class="fas fa-hospital-alt" style="color:var(--hp-primary);margin-right:8px;"></i><?= esc($hospitalName) ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    <div>
        <span class="badge badge-<?= $mode === 'live' ? 'danger' : 'info' ?>" style="font-size:12px;padding:6px 12px;">
            <i class="fas fa-circle" style="font-size:8px;"></i>
            <?= strtoupper(esc($mode)) ?> MODE
        </span>
        <?php if ($hfrId !== ''): ?>
        <span class="badge badge-secondary ml-2" style="font-size:12px;padding:6px 12px;">
            HFR: <?= esc($hfrId) ?>
        </span>
        <?php endif; ?>
    </div>
</div>

<div class="hp-content">

    <!-- Quick Action Tiles -->
    <div class="row">
        <div class="col-md-3 col-sm-6">
            <a href="/admin/m1/abha-validate" style="text-decoration:none;">
                <div class="hp-stat">
                    <div class="stat-icon" style="background:#0d6efd;"><i class="fas fa-search"></i></div>
                    <div>
                        <div class="stat-label">Validate</div>
                        <div style="font-size:15px;font-weight:700;color:#0a3d62;">ABHA</div>
                        <div style="font-size:11px;color:#6c757d;">Quick lookup</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="/admin/m1/otp-flow" style="text-decoration:none;">
                <div class="hp-stat">
                    <div class="stat-icon" style="background:#00b894;"><i class="fas fa-user-plus"></i></div>
                    <div>
                        <div class="stat-label">Create</div>
                        <div style="font-size:15px;font-weight:700;color:#0a3d62;">New Patient</div>
                        <div style="font-size:11px;color:#6c757d;">Aadhaar OTP flow</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="/admin/m1/verify-flow" style="text-decoration:none;">
                <div class="hp-stat">
                    <div class="stat-icon" style="background:#6f42c1;"><i class="fas fa-check-circle"></i></div>
                    <div>
                        <div class="stat-label">Verify</div>
                        <div style="font-size:15px;font-weight:700;color:#0a3d62;">Existing ABHA</div>
                        <div style="font-size:11px;color:#6c757d;">OTP verification</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6">
            <a href="/admin/m1/scan-share" style="text-decoration:none;">
                <div class="hp-stat">
                    <div class="stat-icon" style="background:#e74c3c;"><i class="fas fa-ticket-alt"></i></div>
                    <div>
                        <div class="stat-label">OPD Queue</div>
                        <div style="font-size:15px;font-weight:700;color:#0a3d62;">Token Queue</div>
                        <div style="font-size:11px;color:#6c757d;">Scan &amp; Share</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Patient Master table -->
    <div class="hp-card">
        <div class="hp-card-header">
            <i class="fas fa-database"></i> Recent Patient Master
            <a href="/admin/m1/abha-profiles" class="btn btn-sm btn-outline-primary ml-auto" style="font-size:12px;">
                View All →
            </a>
        </div>
        <div class="hp-card-body" style="padding:0;">
            <?php if (count($recentProfiles) > 0): ?>
            <table class="hp-table">
                <thead>
                    <tr>
                        <th>ABHA Number</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Gender</th>
                        <th>Status</th>
                        <th>Verified At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentProfiles as $p): ?>
                    <tr>
                        <td><strong style="font-family:monospace;color:#1d4ed8;"><?= esc((string)($p->abha_number ?? '')) ?></strong></td>
                        <td><?= esc((string)($p->full_name ?? '')) ?></td>
                        <td><?= esc((string)($p->mobile ?? '—')) ?></td>
                        <td><?php
                            $g = (string)($p->gender ?? '');
                            echo esc($g === 'M' ? 'Male' : ($g === 'F' ? 'Female' : ($g ?: '—')));
                        ?></td>
                        <td>
                            <?php $st = strtoupper((string)($p->abha_status ?? 'ACTIVE')); ?>
                            <span class="hp-badge hp-badge-<?= $st === 'ACTIVE' ? 'success' : 'warning' ?>"><?= esc($st) ?></span>
                        </td>
                        <td style="font-size:12px;color:#6c757d;"><?= esc(substr((string)($p->last_verified_at ?? ''), 0, 16)) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align:center;padding:40px;color:#adb5bd;">
                <i class="fas fa-inbox" style="font-size:40px;display:block;margin-bottom:12px;"></i>
                No ABHA profiles yet.
                <a href="/admin/m1/otp-flow" style="color:var(--hp-primary);">Create the first one →</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?= $this->endSection() ?>


<?= $this->section('content') ?>

<?php
$hospital = $hospital ?? null;
$hospitalName = is_object($hospital) ? ($hospital->hospital_name ?? 'Your Hospital')
    : (is_array($hospital) ? ($hospital['hospital_name'] ?? 'Your Hospital') : 'Your Hospital');
$hfrId = is_object($hospital) ? ($hospital->hfr_id ?? '')
    : (is_array($hospital) ? ($hospital['hfr_id'] ?? '') : '');
$mode = is_object($hospital) ? ($hospital->gateway_mode ?? 'test')
    : (is_array($hospital) ? ($hospital['gateway_mode'] ?? 'test') : 'test');
?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-home"></i> Dashboard <small><?= esc($hospitalName) ?></small></h3>
    </div>
    <div class="title_right">
        <span class="label label-<?= $mode === 'live' ? 'danger' : 'info' ?>" style="font-size:12px;padding:5px 10px;">
            <?= strtoupper(esc($mode)) ?> MODE
        </span>
    </div>
</div>
<div class="clearfix"></div>

<!-- Hospital Info Banner -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel" style="background: linear-gradient(135deg, #2A3F54 0%, #1ABB9C 100%); color: #fff; border: 0;">
            <div class="x_content" style="padding: 20px 24px;">
                <div class="row">
                    <div class="col-md-8">
                        <h2 style="margin:0 0 6px;font-size:22px;color:#fff;">
                            <i class="fa fa-hospital-o"></i> <?= esc($hospitalName) ?>
                        </h2>
                        <?php if ($hfrId !== ''): ?>
                        <p style="margin:0;opacity:.8;font-size:13px;">
                            <i class="fa fa-id-card-o"></i> HFR ID: <strong><?= esc($hfrId) ?></strong>
                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            <i class="fa fa-user"></i> <?= esc(session()->get('username')) ?>
                            &nbsp;&nbsp;|&nbsp;&nbsp;
                            <i class="fa fa-tag"></i> <?= esc(session()->get('role')) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-right" style="padding-top:8px;">
                        <a href="/admin/m1/scan-share" class="btn btn-default btn-sm">
                            <i class="fa fa-ticket"></i> Token Queue
                        </a>
                        <a href="/admin/m1/otp-flow" class="btn btn-default btn-sm" style="margin-left:6px;">
                            <i class="fa fa-plus-circle"></i> New ABHA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Action Cards -->
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="x_panel" style="text-align:center;cursor:pointer;" onclick="location.href='/admin/m1/abha-validate'">
            <div class="x_content" style="padding:30px 20px;">
                <i class="fa fa-search" style="font-size:36px;color:#3498DB;margin-bottom:12px;display:block;"></i>
                <h4 style="margin:0 0 6px;">Validate ABHA</h4>
                <p style="color:#6b7280;font-size:12px;margin:0;">Verify an ABHA number instantly</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="x_panel" style="text-align:center;cursor:pointer;" onclick="location.href='/admin/m1/otp-flow'">
            <div class="x_content" style="padding:30px 20px;">
                <i class="fa fa-plus-circle" style="font-size:36px;color:#1ABB9C;margin-bottom:12px;display:block;"></i>
                <h4 style="margin:0 0 6px;">Create ABHA</h4>
                <p style="color:#6b7280;font-size:12px;margin:0;">New patient via Aadhaar OTP</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="x_panel" style="text-align:center;cursor:pointer;" onclick="location.href='/admin/m1/verify-flow'">
            <div class="x_content" style="padding:30px 20px;">
                <i class="fa fa-check-circle" style="font-size:36px;color:#9B59B6;margin-bottom:12px;display:block;"></i>
                <h4 style="margin:0 0 6px;">Verify ABHA</h4>
                <p style="color:#6b7280;font-size:12px;margin:0;">Verify existing ABHA holder</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="x_panel" style="text-align:center;cursor:pointer;" onclick="location.href='/admin/m1/scan-share'">
            <div class="x_content" style="padding:30px 20px;">
                <i class="fa fa-ticket" style="font-size:36px;color:#E74C3C;margin-bottom:12px;display:block;"></i>
                <h4 style="margin:0 0 6px;">OPD Queue</h4>
                <p style="color:#6b7280;font-size:12px;margin:0;">Scan &amp; Share token list</p>
            </div>
        </div>
    </div>
</div>

<!-- Patient Master -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-database"></i> Patient Master
                    <small>verified ABHA profiles</small>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <?php if (!empty($recentProfiles) && count($recentProfiles) > 0): ?>
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ABHA Number</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Status</th>
                            <th>Verified At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentProfiles as $p): ?>
                        <tr>
                            <td><strong style="color:#1d4ed8;font-family:monospace;"><?= esc((string)($p->abha_number ?? '')) ?></strong></td>
                            <td><?= esc((string)($p->full_name ?? '')) ?></td>
                            <td><?= esc((string)($p->mobile ?? '')) ?></td>
                            <td>
                                <?php $st = strtoupper((string)($p->abha_status ?? 'ACTIVE')); ?>
                                <span class="label label-<?= $st === 'ACTIVE' ? 'success' : 'warning' ?>"><?= esc($st) ?></span>
                            </td>
                            <td style="font-size:12px;"><?= esc((string)($p->last_verified_at ?? '')) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><a href="/admin/m1/abha-profiles">View all records →</a></p>
                <?php else: ?>
                <p class="text-muted" style="padding:20px 0;">
                    No ABHA profiles yet.
                    <a href="/admin/m1/otp-flow">Create the first one →</a>
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
