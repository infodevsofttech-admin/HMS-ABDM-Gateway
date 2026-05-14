<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'M1 Suite'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-heartbeat"></i> ABDM M1 Suite</h3>
    </div>
</div>
<div class="clearfix"></div>

<div class="row">
    <!-- ABHA APIs -->
    <div class="col-md-6">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-id-card-o"></i> ABHA APIs</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="/admin/m1/otp-flow"><i class="fa fa-plus-circle text-success"></i> <strong>ABHA Creation</strong> — OTP Guided Flow (Aadhaar)</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/m1/verify-flow"><i class="fa fa-check-circle text-primary"></i> <strong>ABHA Verification</strong> — Verify existing ABHA holders</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/m1/abha-validate"><i class="fa fa-search text-warning"></i> <strong>ABHA Validate</strong> — Quick lookup by ABHA ID</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/m1/abha-profiles"><i class="fa fa-database text-info"></i> <strong>Patient Master</strong> — Saved ABHA profiles</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Scan & Share -->
    <div class="col-md-6">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-qrcode"></i> Scan &amp; Share (HFR QR)</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="/admin/m1/scan-share"><i class="fa fa-ticket text-success"></i> <strong>OPD Token Queue</strong> — Patients who scanned today's facility QR</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/m1/scan-share-setup"><i class="fa fa-cogs text-warning"></i> <strong>Setup / Register HIP</strong> — Update bridge URL &amp; register with ABDM</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Test Logs -->
    <div class="col-md-6">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-flask"></i> Test Logs &amp; Export</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <ul class="list-group">
                    <li class="list-group-item">
                        <a href="/admin/m1-module"><i class="fa fa-terminal"></i> <strong>Raw M1 Test Console</strong> — All endpoints</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/m1-module/export?format=csv"><i class="fa fa-download text-success"></i> Export M1 Test Logs (CSV)</a>
                    </li>
                    <li class="list-group-item">
                        <a href="/admin/m1-module/export?format=json"><i class="fa fa-download text-info"></i> Export M1 Test Logs (JSON)</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <?php if (!empty($profiles)): ?>
    <!-- Recent Profiles -->
    <div class="col-md-6">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-users"></i> Recent Verified Profiles</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-striped table-condensed">
                    <thead><tr><th>ABHA Number</th><th>Name</th><th>Verified</th></tr></thead>
                    <tbody>
                        <?php foreach ($profiles as $p): ?>
                        <tr>
                            <td><code><?= esc((string) ($p->abha_number ?? '')) ?></code></td>
                            <td><?= esc((string) ($p->full_name ?? '—')) ?></td>
                            <td><?= esc(substr((string) ($p->last_verified_at ?? ''), 0, 10)) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

