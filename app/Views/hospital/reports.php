<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Reports'; ?>

<?= $this->section('content') ?>

<?php
$totalProfiles = $totalProfiles ?? 0;
$todayProfiles = $todayProfiles ?? 0;
$monthProfiles = $monthProfiles ?? 0;
$totalTokens   = $totalTokens ?? 0;
$todayTokens   = $todayTokens ?? 0;
$monthTokens   = $monthTokens ?? 0;
$trend         = $trend ?? [];
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-chart-bar"></i> Reports &amp; Statistics</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item active">Reports</li>
        </ol></nav>
    </div>
    <div style="font-size:12px;color:#6c757d;"><i class="fas fa-calendar mr-1"></i> Data as of <?= date('d M Y') ?></div>
</div>

<div class="hp-content">

    <!-- Stat cards -->
    <div class="row mb-3">
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="hp-card" style="margin:0;">
                <div class="hp-card-body" style="padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:var(--hp-primary);"><?= (int)$totalProfiles ?></div>
                    <div style="font-size:11px;color:#6c757d;font-weight:600;">Total Patients</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="hp-card" style="margin:0;">
                <div class="hp-card-body" style="padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:#065f46;"><?= (int)$todayProfiles ?></div>
                    <div style="font-size:11px;color:#6c757d;font-weight:600;">Patients Today</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="hp-card" style="margin:0;">
                <div class="hp-card-body" style="padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:#1565c0;"><?= (int)$monthProfiles ?></div>
                    <div style="font-size:11px;color:#6c757d;font-weight:600;">Patients This Month</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="hp-card" style="margin:0;">
                <div class="hp-card-body" style="padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:#713f12;"><?= (int)$totalTokens ?></div>
                    <div style="font-size:11px;color:#6c757d;font-weight:600;">Total OPD Tokens</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="hp-card" style="margin:0;">
                <div class="hp-card-body" style="padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:#d97706;"><?= (int)$todayTokens ?></div>
                    <div style="font-size:11px;color:#6c757d;font-weight:600;">Tokens Today</div>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-3">
            <div class="hp-card" style="margin:0;">
                <div class="hp-card-body" style="padding:16px;text-align:center;">
                    <div style="font-size:28px;font-weight:800;color:#6d28d9;"><?= (int)$monthTokens ?></div>
                    <div style="font-size:11px;color:#6c757d;font-weight:600;">Tokens This Month</div>
                </div>
            </div>
        </div>
    </div>

    <!-- 7-day trend table -->
    <div class="hp-card">
        <div class="hp-card-head"><i class="fas fa-chart-line"></i> Last 7 Days Activity</div>
        <?php if (empty($trend)): ?>
        <div class="hp-card-body" style="color:#6c757d;text-align:center;padding:30px;">No data available.</div>
        <?php else: ?>

        <!-- Bar chart (pure CSS) -->
        <?php
        $maxVal = 1;
        foreach ($trend as $d) { $maxVal = max($maxVal, $d['profiles'], $d['tokens']); }
        ?>
        <div class="hp-card-body" style="padding:20px;">
            <div style="display:flex;align-items:flex-end;gap:8px;height:120px;border-bottom:2px solid var(--hp-border);padding-bottom:4px;margin-bottom:8px;">
                <?php foreach ($trend as $d): ?>
                <div style="flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;height:100%;">
                    <div style="display:flex;align-items:flex-end;gap:2px;height:100%;">
                        <div title="Patients: <?= $d['profiles'] ?>" style="width:14px;background:var(--hp-primary);border-radius:3px 3px 0 0;height:<?= $maxVal > 0 ? round($d['profiles'] / $maxVal * 100) : 0 ?>%;min-height:<?= $d['profiles'] > 0 ? '3px' : '0' ?>;"></div>
                        <div title="Tokens: <?= $d['tokens'] ?>" style="width:14px;background:var(--hp-accent);border-radius:3px 3px 0 0;height:<?= $maxVal > 0 ? round($d['tokens'] / $maxVal * 100) : 0 ?>%;min-height:<?= $d['tokens'] > 0 ? '3px' : '0' ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:8px;">
                <?php foreach ($trend as $d): ?>
                <div style="flex:1;text-align:center;font-size:10px;color:#6c757d;"><?= esc($d['label']) ?></div>
                <?php endforeach; ?>
            </div>
            <div style="display:flex;gap:16px;margin-top:10px;justify-content:center;font-size:12px;">
                <span><span style="display:inline-block;width:12px;height:12px;background:var(--hp-primary);border-radius:2px;vertical-align:middle;margin-right:4px;"></span>Patients</span>
                <span><span style="display:inline-block;width:12px;height:12px;background:var(--hp-accent);border-radius:2px;vertical-align:middle;margin-right:4px;"></span>OPD Tokens</span>
            </div>
        </div>

        <table class="hp-tbl">
            <thead><tr>
                <th>Date</th><th>Patients Added</th><th>OPD Tokens</th>
            </tr></thead>
            <tbody>
            <?php foreach (array_reverse($trend) as $d): ?>
            <tr>
                <td style="font-weight:600;"><?= esc($d['label']) ?> <span style="font-size:11px;color:#adb5bd;">(<?= esc($d['date']) ?>)</span></td>
                <td>
                    <span style="font-size:16px;font-weight:700;color:var(--hp-primary);"><?= (int)$d['profiles'] ?></span>
                </td>
                <td>
                    <span style="font-size:16px;font-weight:700;color:var(--hp-accent);"><?= (int)$d['tokens'] ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Quick links -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <a href="/portal/patients" style="text-decoration:none;">
                <div class="hp-card" style="margin:0;border-color:var(--hp-primary);">
                    <div class="hp-card-body" style="padding:16px 20px;display:flex;align-items:center;gap:12px;">
                        <i class="fas fa-user-injured" style="font-size:24px;color:var(--hp-primary);"></i>
                        <div><div style="font-weight:700;color:var(--hp-primary);">View All Patients</div><div style="font-size:12px;color:#6c757d;"><?= (int)$totalProfiles ?> total records</div></div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-6 mb-3">
            <a href="/portal/opd-queue" style="text-decoration:none;">
                <div class="hp-card" style="margin:0;border-color:var(--hp-accent);">
                    <div class="hp-card-body" style="padding:16px 20px;display:flex;align-items:center;gap:12px;">
                        <i class="fas fa-list-ol" style="font-size:24px;color:var(--hp-accent);"></i>
                        <div><div style="font-weight:700;color:var(--hp-accent);">View OPD Queue</div><div style="font-size:12px;color:#6c757d;"><?= (int)$todayTokens ?> tokens today</div></div>
                    </div>
                </div>
            </a>
        </div>
    </div>

</div><!-- /.hp-content -->

<?= $this->endSection() ?>
