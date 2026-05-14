<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Dashboard'; ?>

<?= $this->section('content') ?>

<?php
$hospital      = $hospital ?? null;
$hospitalName  = is_object($hospital) ? ($hospital->hospital_name ?? 'Your Hospital')
    : (is_array($hospital) ? ($hospital['hospital_name'] ?? 'Your Hospital') : 'Your Hospital');
$hfrId = is_object($hospital) ? ($hospital->hfr_id ?? '')
    : (is_array($hospital) ? ($hospital['hfr_id'] ?? '') : '');
$mode  = is_object($hospital) ? ($hospital->gateway_mode ?? 'test')
    : (is_array($hospital) ? ($hospital['gateway_mode'] ?? 'test') : 'test');
$recentPatients = $recentPatients ?? [];
$totalPatients  = $totalPatients ?? 0;
$todayTokens    = $todayTokens ?? 0;
$monthTokens    = $monthTokens ?? 0;
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-home"></i> Dashboard</h5>
        <nav><ol class="breadcrumb"><li class="breadcrumb-item active">Home</li></ol></nav>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
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

    <!-- Hospital banner -->
    <div class="hp-card" style="background:linear-gradient(120deg,var(--hp-dark) 0%,var(--hp-primary) 100%);border:0;">
        <div class="hp-card-body" style="padding:20px 24px;">
            <div style="display:flex;align-items:center;gap:16px;">
                <div style="width:48px;height:48px;background:rgba(255,255,255,.15);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fas fa-hospital-alt" style="font-size:22px;color:#fff;"></i>
                </div>
                <div>
                    <div style="color:rgba(255,255,255,.6);font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;">Logged in as</div>
                    <div style="color:#fff;font-size:18px;font-weight:700;"><?= esc($hospitalName) ?></div>
                    <?php if ($hfrId !== ''): ?>
                    <div style="color:rgba(255,255,255,.6);font-size:12px;"><i class="fas fa-id-card-alt mr-1"></i> HFR: <?= esc($hfrId) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats row -->
    <div class="row" style="margin-bottom:4px;">
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="hp-card" style="margin-bottom:0;">
                <div class="hp-card-body" style="display:flex;align-items:center;gap:14px;padding:18px 20px;">
                    <div style="width:48px;height:48px;background:#dbeafe;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-users" style="color:#1565c0;font-size:20px;"></i>
                    </div>
                    <div>
                        <div style="font-size:24px;font-weight:800;color:#1a1a2e;"><?= (int)$totalPatients ?></div>
                        <div style="font-size:12px;color:#6c757d;font-weight:600;">Total Patients</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="hp-card" style="margin-bottom:0;">
                <div class="hp-card-body" style="display:flex;align-items:center;gap:14px;padding:18px 20px;">
                    <div style="width:48px;height:48px;background:#d1fae5;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-ticket-alt" style="color:#065f46;font-size:20px;"></i>
                    </div>
                    <div>
                        <div style="font-size:24px;font-weight:800;color:#1a1a2e;"><?= (int)$todayTokens ?></div>
                        <div style="font-size:12px;color:#6c757d;font-weight:600;">OPD Tokens Today</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 mb-3">
            <div class="hp-card" style="margin-bottom:0;">
                <div class="hp-card-body" style="display:flex;align-items:center;gap:14px;padding:18px 20px;">
                    <div style="width:48px;height:48px;background:#fef9c3;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="fas fa-calendar-check" style="color:#713f12;font-size:20px;"></i>
                    </div>
                    <div>
                        <div style="font-size:24px;font-weight:800;color:#1a1a2e;"><?= (int)$monthTokens ?></div>
                        <div style="font-size:12px;color:#6c757d;font-weight:600;">Tokens This Month</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick actions -->
    <div class="row mb-3">
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/portal/abha-tools" style="text-decoration:none;">
                <div class="hp-card" style="margin-bottom:0;transition:.15s;cursor:pointer;" onmouseover="this.style.borderColor='var(--hp-primary)'" onmouseout="this.style.borderColor='var(--hp-border)'">
                    <div class="hp-card-body" style="text-align:center;padding:20px 12px;">
                        <div style="width:48px;height:48px;background:#dbeafe;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                            <i class="fas fa-id-card" style="color:#1565c0;font-size:20px;"></i>
                        </div>
                        <div style="font-weight:700;color:#1a1a2e;font-size:13px;">ABHA Tools</div>
                        <div style="font-size:11px;color:#6c757d;">Validate &amp; Create</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/portal/opd-queue" style="text-decoration:none;">
                <div class="hp-card" style="margin-bottom:0;transition:.15s;cursor:pointer;" onmouseover="this.style.borderColor='var(--hp-primary)'" onmouseout="this.style.borderColor='var(--hp-border)'">
                    <div class="hp-card-body" style="text-align:center;padding:20px 12px;">
                        <div style="width:48px;height:48px;background:#d1fae5;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                            <i class="fas fa-list-ol" style="color:#065f46;font-size:20px;"></i>
                        </div>
                        <div style="font-weight:700;color:#1a1a2e;font-size:13px;">OPD Queue</div>
                        <div style="font-size:11px;color:#6c757d;">Today's tokens</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/portal/patients" style="text-decoration:none;">
                <div class="hp-card" style="margin-bottom:0;transition:.15s;cursor:pointer;" onmouseover="this.style.borderColor='var(--hp-primary)'" onmouseout="this.style.borderColor='var(--hp-border)'">
                    <div class="hp-card-body" style="text-align:center;padding:20px 12px;">
                        <div style="width:48px;height:48px;background:#ede9fe;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                            <i class="fas fa-user-injured" style="color:#6d28d9;font-size:20px;"></i>
                        </div>
                        <div style="font-weight:700;color:#1a1a2e;font-size:13px;">Patients</div>
                        <div style="font-size:11px;color:#6c757d;">Patient master</div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <a href="/portal/reports" style="text-decoration:none;">
                <div class="hp-card" style="margin-bottom:0;transition:.15s;cursor:pointer;" onmouseover="this.style.borderColor='var(--hp-primary)'" onmouseout="this.style.borderColor='var(--hp-border)'">
                    <div class="hp-card-body" style="text-align:center;padding:20px 12px;">
                        <div style="width:48px;height:48px;background:#fef9c3;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">
                            <i class="fas fa-chart-bar" style="color:#713f12;font-size:20px;"></i>
                        </div>
                        <div style="font-weight:700;color:#1a1a2e;font-size:13px;">Reports</div>
                        <div style="font-size:11px;color:#6c757d;">Stats &amp; trends</div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Recent patients -->
    <div class="hp-card">
        <div class="hp-card-head">
            <i class="fas fa-clock"></i> Recent Patients
            <a href="/portal/patients" style="margin-left:auto;font-size:12px;font-weight:500;color:var(--hp-primary);">View all &rarr;</a>
        </div>
        <?php if (empty($recentPatients)): ?>
        <div class="hp-card-body" style="text-align:center;padding:40px 20px;color:#6c757d;font-size:13px;">
            <i class="fas fa-user-slash" style="font-size:32px;color:#dee2e6;display:block;margin-bottom:10px;"></i>
            No patients yet. Use ABHA Tools to add your first patient.
        </div>
        <?php else: ?>
        <table class="hp-tbl">
            <thead><tr>
                <th>ABHA Number</th><th>Name</th><th>Mobile</th><th>Status</th><th>Verified At</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentPatients as $p): ?>
            <tr>
                <td><a href="/portal/patients?search=<?= urlencode((string)($p->abha_number ?? '')) ?>" style="color:var(--hp-primary);font-weight:600;"><?= esc((string)($p->abha_number ?? '—')) ?></a></td>
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

</div>

<?= $this->endSection() ?>
