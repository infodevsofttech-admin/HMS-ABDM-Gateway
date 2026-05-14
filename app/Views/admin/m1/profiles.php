<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'ABHA Patient Master'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-database"></i> Patient Master <small>All verified ABHA profiles</small></h3>
    </div>
</div>
<div class="clearfix"></div>

<style>
        h1 { margin-bottom: 4px; }
        .subtitle { color: #6b7280; font-size: 14px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 0; box-shadow: 0 1px 4px rgba(0,0,0,0.08); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #f1f5f9; font-size: 12px; color: #374151; text-transform: uppercase; letter-spacing: .04em; padding: 10px 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:last-child td { border-bottom: 0; }
        tr:hover td { background: #f8fafc; }
        .abha { font-weight: 700; letter-spacing: .04em; color: #1d4ed8; white-space: nowrap; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .badge-active  { background: #d1fae5; color: #065f46; }
        .badge-inactive{ background: #fef9c3; color: #713f12; }
        .badge-std     { background: #e0e7ff; color: #3730a3; }
        .photo { width: 40px; height: 40px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; }
        .no-photo { width: 40px; height: 40px; border-radius: 6px; background: #e5e7eb; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; color: #9ca3af; }
        .addr { max-width: 200px; font-size: 12px; color: #6b7280; }
        .phr  { font-size: 11px; color: #7c3aed; }
        .empty { text-align: center; padding: 40px; color: #9ca3af; }
        a { color: #1d4ed8; text-decoration: none; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .count { font-size: 13px; color: #6b7280; }
        .ok  { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; }
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; }
</style>

<?php
    $profiles = $profiles ?? [];
    $count    = count($profiles);
    ?>



    <?php if (!empty($message)): ?>
        <div class="ok"><?= esc((string) $message) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="err"><?= esc((string) $error) ?></div>
    <?php endif; ?>

    <div class="toolbar">
        <span class="count"><?= $count ?> record<?= $count !== 1 ? 's' : '' ?></span>
        <a href="/admin/m1/otp-flow">+ New OTP Verification</a>
    </div>

    <div class="card">
        <?php if ($count === 0): ?>
            <p class="empty">No ABHA profiles saved yet. Run an OTP flow to capture the first record.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>ABHA Number</th>
                    <th>Name</th>
                    <th>DOB / Gender</th>
                    <th>Mobile</th>
                    <th>PHR Address</th>
                    <th>Location</th>
                    <th>Type / Status</th>
                    <th>Verified At</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $p):
                    $json     = is_string($p->profile_json ?? null) ? json_decode($p->profile_json, true) : null;
                    $photoB64 = is_array($json) ? ($json['photo'] ?? null) : null;
                    $abhaStatus = strtoupper((string) ($p->abha_status ?? 'ACTIVE'));
                    $abhaType   = strtoupper((string) ($p->abha_type ?? ''));
                ?>
                <tr>
                    <td>
                        <?php if ($photoB64): ?>
                            <img class="photo" src="data:image/jpeg;base64,<?= esc($photoB64) ?>" alt="photo">
                        <?php else: ?>
                            <span class="no-photo">👤</span>
                        <?php endif; ?>
                    </td>
                    <td class="abha"><?= esc((string) ($p->abha_number ?? '')) ?></td>
                    <td><strong><?= esc((string) ($p->full_name ?? '')) ?></strong></td>
                    <td>
                        <?= esc((string) ($p->date_of_birth ?? '')) ?>
                        <?php if (!empty($p->gender)): ?>
                            <br><span style="font-size:11px;color:#6b7280;"><?= $p->gender === 'M' ? 'Male' : ($p->gender === 'F' ? 'Female' : esc((string)$p->gender)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= esc((string) ($p->mobile ?? '')) ?>
                        <?php if (!empty($p->mobile_verified)): ?>
                            <br><span style="font-size:11px;color:#059669;">✓ verified</span>
                        <?php endif; ?>
                    </td>
                    <td class="phr"><?= esc((string) ($p->phr_address ?? $p->abha_address ?? '')) ?></td>
                    <td class="addr">
                        <?php
                        $parts = array_filter([
                            (string) ($p->district_name ?? ''),
                            (string) ($p->state_name ?? ''),
                            (string) ($p->pin_code ?? ''),
                        ]);
                        echo esc(implode(', ', $parts));
                        ?>
                    </td>
                    <td>
                        <?php if ($abhaType !== ''): ?>
                            <span class="badge badge-std"><?= esc($abhaType) ?></span><br>
                        <?php endif; ?>
                        <span class="badge <?= $abhaStatus === 'ACTIVE' ? 'badge-active' : 'badge-inactive' ?>">
                            <?= esc($abhaStatus) ?>
                        </span>
                    </td>
                    <td style="white-space:nowrap;font-size:12px;"><?= esc((string) ($p->last_verified_at ?? '')) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
<?= $this->endSection() ?>
