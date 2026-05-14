<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'ABHA Tools'; ?>

<?= $this->section('content') ?>

<?php
$message       = $message ?? null;
$error         = $error ?? null;
$abhaUser      = $abhaUser ?? null;
$otpStep       = $otpStep ?? 1;
$txnId         = $txnId ?? '';
$otpType       = $otpType ?? 'aadhaar';
$otpInput      = $otpInput ?? '';
$recentProfiles= $recentProfiles ?? [];
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-id-card"></i> ABHA Tools</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item active">ABHA Tools</li>
        </ol></nav>
    </div>
</div>

<div class="hp-content">

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-2"></i><?= esc((string)$message) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-2"></i><?= esc((string)$error) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Tabs -->
<ul class="nav nav-pills mb-3" id="abha-tabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link <?= ($otpStep === 1 && $abhaUser === null && $otpType === 'aadhaar') ? 'active' : '' ?>" id="tab-validate" data-toggle="pill" href="#pane-validate" role="tab">
            <i class="fas fa-search mr-1"></i> Validate ABHA
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($otpType === 'aadhaar' && $otpStep > 1) || ($otpType === 'aadhaar' && $otpStep === 1 && $otpInput !== '') ? 'active' : '' ?>" id="tab-create" data-toggle="pill" href="#pane-create" role="tab">
            <i class="fas fa-user-plus mr-1"></i> Create ABHA (Aadhaar)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $otpType === 'mobile' ? 'active' : '' ?>" id="tab-mobile" data-toggle="pill" href="#pane-mobile" role="tab">
            <i class="fas fa-mobile-alt mr-1"></i> Verify via Mobile
        </a>
    </li>
</ul>

<div class="tab-content">

    <!-- ── Validate ABHA ── -->
    <div class="tab-pane fade <?= ($otpStep === 1 && $abhaUser === null && $otpType === 'aadhaar') ? 'show active' : '' ?>" id="pane-validate" role="tabpanel">
        <div class="row">
            <div class="col-md-5">
                <div class="hp-card">
                    <div class="hp-card-head"><i class="fas fa-search"></i> Validate ABHA Number</div>
                    <div class="hp-card-body">
                        <form method="POST" action="/portal/abha/validate">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">ABHA Number</label>
                                <input type="text" name="abha_id" class="form-control" placeholder="e.g. 91-1234-5678-9012" required>
                                <small class="text-muted">14-digit ABHA number in XX-XXXX-XXXX-XXXX format</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-search mr-1"></i> Validate
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php if ($abhaUser !== null): ?>
            <div class="col-md-7">
                <div class="hp-card" style="border-color:#28a745;">
                    <div class="hp-card-head" style="background:#d1fae5;color:#065f46;"><i class="fas fa-check-circle"></i> ABHA Validated</div>
                    <div class="hp-card-body">
                        <table class="hp-tbl">
                            <tbody>
                            <?php foreach ([
                                'ABHA Number' => $abhaUser['abhaNumber'] ?? $abhaUser['abha_id'] ?? '—',
                                'Name'        => $abhaUser['name'] ?? $abhaUser['fullName'] ?? '—',
                                'Gender'      => $abhaUser['gender'] ?? '—',
                                'Year of Birth'=> $abhaUser['yearOfBirth'] ?? '—',
                                'Mobile'      => $abhaUser['mobile'] ?? '—',
                            ] as $lbl => $val): ?>
                            <tr><td style="font-weight:600;color:#495057;width:40%;"><?= esc($lbl) ?></td><td><?= esc((string)$val) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Create ABHA via Aadhaar OTP ── -->
    <div class="tab-pane fade <?= ($otpType === 'aadhaar' && ($otpStep > 1 || $otpInput !== '')) ? 'show active' : '' ?>" id="pane-create" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="hp-card">
                    <div class="hp-card-head"><i class="fas fa-user-plus"></i> Create ABHA via Aadhaar OTP</div>
                    <div class="hp-card-body">

                        <?php if ($otpStep < 2 || $otpType !== 'aadhaar'): ?>
                        <!-- Step 1: Enter Aadhaar -->
                        <form method="POST" action="/portal/abha/otp-gen">
                            <?= csrf_field() ?>
                            <input type="hidden" name="otp_type" value="aadhaar">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Aadhaar Number</label>
                                <input type="text" name="otp_input" class="form-control" placeholder="12-digit Aadhaar" maxlength="12" required>
                                <small class="text-muted">OTP will be sent to the Aadhaar-linked mobile number</small>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-paper-plane mr-1"></i> Send OTP
                            </button>
                        </form>
                        <?php else: ?>
                        <!-- Step 2: Enter OTP -->
                        <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> OTP sent to Aadhaar-linked number for <strong><?= esc($otpInput) ?></strong></div>
                        <form method="POST" action="/portal/abha/otp-verify">
                            <?= csrf_field() ?>
                            <input type="hidden" name="otp_type" value="aadhaar">
                            <input type="hidden" name="txn_id" value="<?= esc($txnId) ?>">
                            <input type="hidden" name="otp_input" value="<?= esc($otpInput) ?>">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Enter OTP</label>
                                <input type="text" name="otp" class="form-control" placeholder="6-digit OTP" maxlength="6" required autofocus>
                            </div>
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Mobile (optional, for ABHA communication)</label>
                                <input type="text" name="mobile" class="form-control" placeholder="10-digit mobile" maxlength="10">
                            </div>
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-check mr-1"></i> Verify OTP &amp; Create ABHA
                            </button>
                            <a href="/portal/abha-tools" class="btn btn-link btn-block btn-sm mt-1">Start over</a>
                        </form>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
            <?php if ($abhaUser !== null && $otpType === 'aadhaar'): ?>
            <div class="col-md-6">
                <div class="hp-card" style="border-color:#28a745;">
                    <div class="hp-card-head" style="background:#d1fae5;color:#065f46;"><i class="fas fa-check-circle"></i> ABHA Created</div>
                    <div class="hp-card-body">
                        <table class="hp-tbl"><tbody>
                        <?php foreach ([
                            'ABHA Number' => $abhaUser['abhaNumber'] ?? '—',
                            'Name'        => $abhaUser['name'] ?? '—',
                            'Gender'      => $abhaUser['gender'] ?? '—',
                            'Year'        => $abhaUser['yearOfBirth'] ?? '—',
                            'Mobile'      => $abhaUser['mobile'] ?? '—',
                        ] as $lbl => $val): ?>
                        <tr><td style="font-weight:600;width:40%;"><?= esc($lbl) ?></td><td><?= esc((string)$val) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody></table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Verify via Mobile OTP ── -->
    <div class="tab-pane fade <?= $otpType === 'mobile' ? 'show active' : '' ?>" id="pane-mobile" role="tabpanel">
        <div class="col-md-6 px-0">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-mobile-alt"></i> Verify ABHA via Mobile OTP</div>
                <div class="hp-card-body">
                    <?php if ($otpStep < 2 || $otpType !== 'mobile'): ?>
                    <form method="POST" action="/portal/abha/otp-gen">
                        <?= csrf_field() ?>
                        <input type="hidden" name="otp_type" value="mobile">
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;">Mobile Number</label>
                            <input type="text" name="otp_input" class="form-control" placeholder="10-digit mobile" maxlength="10" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-paper-plane mr-1"></i> Send OTP</button>
                    </form>
                    <?php else: ?>
                    <div class="alert alert-info"><i class="fas fa-info-circle mr-1"></i> OTP sent to <strong><?= esc($otpInput) ?></strong></div>
                    <form method="POST" action="/portal/abha/otp-verify">
                        <?= csrf_field() ?>
                        <input type="hidden" name="otp_type" value="mobile">
                        <input type="hidden" name="txn_id" value="<?= esc($txnId) ?>">
                        <input type="hidden" name="otp_input" value="<?= esc($otpInput) ?>">
                        <input type="hidden" name="mobile" value="<?= esc($otpInput) ?>">
                        <div class="form-group">
                            <label style="font-size:13px;font-weight:600;">OTP</label>
                            <input type="text" name="otp" class="form-control" placeholder="6-digit OTP" maxlength="6" required autofocus>
                        </div>
                        <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check mr-1"></i> Verify OTP</button>
                        <a href="/portal/abha-tools" class="btn btn-link btn-block btn-sm mt-1">Start over</a>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div><!-- /.tab-content -->

<!-- Recent profiles -->
<div class="hp-card">
    <div class="hp-card-head"><i class="fas fa-history"></i> Recent ABHA Records (This Hospital)</div>
    <?php if (empty($recentProfiles)): ?>
    <div class="hp-card-body" style="text-align:center;padding:30px;color:#6c757d;font-size:13px;">No records yet.</div>
    <?php else: ?>
    <table class="hp-tbl">
        <thead><tr><th>ABHA Number</th><th>Name</th><th>Mobile</th><th>Status</th><th>Verified At</th></tr></thead>
        <tbody>
        <?php foreach ($recentProfiles as $p): ?>
        <tr>
            <td style="font-weight:600;color:var(--hp-primary);"><?= esc((string)($p->abha_number ?? '—')) ?></td>
            <td><?= esc((string)($p->full_name ?? '—')) ?></td>
            <td><?= esc((string)($p->mobile ?? '—')) ?></td>
            <td><span class="hb hb-green"><?= esc(strtoupper((string)($p->status ?? 'verified'))) ?></span></td>
            <td style="font-size:12px;color:#6c757d;"><?= esc((string)($p->last_verified_at ?? '—')) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</div><!-- /.hp-content -->

<?= $this->section('scripts') ?>
<script>
// Auto-activate correct tab based on server state
<?php if ($otpType === 'mobile'): ?>
$('#tab-mobile').tab('show');
<?php elseif ($otpStep > 1 && $otpType === 'aadhaar'): ?>
$('#tab-create').tab('show');
<?php endif; ?>
</script>
<?= $this->endSection() ?>

<?= $this->endSection() ?>
