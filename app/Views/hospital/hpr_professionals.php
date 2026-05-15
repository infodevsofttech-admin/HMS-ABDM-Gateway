<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'HPR Professionals'; ?>

<?= $this->section('content') ?>

<?php
$professionals = $professionals ?? [];
$message = $message ?? null;
$error   = $error   ?? null;
$hospital = $hospital ?? null;
?>

<div class="hp-page-header">
    <div>
        <h2 class="hp-page-title"><i class="fa fa-user-md"></i> HPR Professionals</h2>
        <p class="hp-page-subtitle">Manage the healthcare professionals registered under your hospital in ABDM's Health Professional Registry.</p>
    </div>
</div>

<?php if ($message): ?>
<div class="hp-alert hp-alert-success"><i class="fa fa-check-circle"></i> <?= esc($message) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="hp-alert hp-alert-danger"><i class="fa fa-exclamation-circle"></i> <?= esc($error) ?></div>
<?php endif; ?>

<!-- Info box -->
<div class="hp-card" style="margin-bottom:20px;background:#eff6ff;border-left:4px solid #3b82f6;">
    <div class="hp-card-body" style="padding:14px 18px;">
        <p style="margin:0;font-size:14px;color:#1e40af;">
            <i class="fa fa-info-circle"></i>
            <strong>HPR ID</strong> is your doctor's unique Health Professional Registry identifier (e.g. <code>drname@hpr.abdm</code>).
            These are used in ABDM scan &amp; share to associate an OPD visit with a specific doctor.
            Register professionals at <a href="https://nhpr.abdm.gov.in" target="_blank" rel="noopener">nhpr.abdm.gov.in</a>.
        </p>
    </div>
</div>

<!-- Add Professional Form -->
<div class="hp-card" style="margin-bottom:24px;">
    <div class="hp-card-header">
        <h3 class="hp-card-title"><i class="fa fa-plus-circle"></i> Add Professional</h3>
    </div>
    <div class="hp-card-body">
        <form method="post" action="/portal/hpr-professionals/create">
            <?= csrf_field() ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
                <div>
                    <label class="hp-label">Full Name <span style="color:red">*</span></label>
                    <input type="text" name="name" class="hp-input" placeholder="Dr. Rajesh Kumar" required>
                </div>
                <div>
                    <label class="hp-label">HPR ID <span style="color:red">*</span></label>
                    <input type="text" name="hpr_id" class="hp-input" placeholder="rajesh.kumar@hpr.abdm" required>
                    <small style="color:#6b7280;font-size:12px;">Format: <code>name@hpr.abdm</code> or 14-digit number</small>
                </div>
                <div>
                    <label class="hp-label">Designation</label>
                    <input type="text" name="designation" class="hp-input" placeholder="Senior Physician">
                </div>
                <div>
                    <label class="hp-label">Specialization</label>
                    <input type="text" name="specialization" class="hp-input" placeholder="General Medicine">
                </div>
                <div>
                    <label class="hp-label">Department</label>
                    <input type="text" name="department" class="hp-input" placeholder="OPD">
                </div>
                <div>
                    <label class="hp-label">Reg. Number (MCI/State)</label>
                    <input type="text" name="registration_number" class="hp-input" placeholder="MCI-12345">
                </div>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="hp-btn hp-btn-primary"><i class="fa fa-plus"></i> Add Professional</button>
            </div>
        </form>
    </div>
</div>

<!-- Professionals List -->
<div class="hp-card">
    <div class="hp-card-header">
        <h3 class="hp-card-title"><i class="fa fa-list"></i> Registered Professionals
            <span style="font-size:13px;font-weight:400;color:#6b7280;">(<?= count($professionals) ?>)</span>
        </h3>
    </div>
    <div class="hp-card-body" style="padding:0;">
        <?php if (empty($professionals)): ?>
            <div style="padding:32px;text-align:center;color:#6b7280;">
                <i class="fa fa-user-md" style="font-size:32px;margin-bottom:12px;display:block;"></i>
                No professionals added yet. Add your first HPR professional above.
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f9fafb;">
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Name</th>
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">HPR ID</th>
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Designation</th>
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Specialization</th>
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Department</th>
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Status</th>
                            <th style="padding:12px 16px;text-align:left;font-size:13px;font-weight:600;color:#374151;border-bottom:1px solid #e5e7eb;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($professionals as $p): ?>
                            <tr style="border-bottom:1px solid #f3f4f6;">
                                <td style="padding:12px 16px;">
                                    <strong><?= esc((string) $p['name']) ?></strong>
                                    <?php if (!empty($p['registration_number'])): ?>
                                        <br><small style="color:#9ca3af;"><?= esc((string) $p['registration_number']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px;">
                                    <code style="font-size:13px;"><?= esc((string) $p['hpr_id']) ?></code>
                                </td>
                                <td style="padding:12px 16px;color:#6b7280;"><?= esc((string) ($p['designation'] ?? '—')) ?></td>
                                <td style="padding:12px 16px;color:#6b7280;"><?= esc((string) ($p['specialization'] ?? '—')) ?></td>
                                <td style="padding:12px 16px;color:#6b7280;"><?= esc((string) ($p['department'] ?? '—')) ?></td>
                                <td style="padding:12px 16px;">
                                    <?php if ($p['is_active']): ?>
                                        <span style="padding:3px 10px;background:#d1fae5;color:#065f46;border-radius:12px;font-size:12px;font-weight:600;">Active</span>
                                    <?php else: ?>
                                        <span style="padding:3px 10px;background:#fee2e2;color:#991b1b;border-radius:12px;font-size:12px;font-weight:600;">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px;">
                                    <form method="post" action="/portal/hpr-professionals/<?= esc((string) $p['id']) ?>/delete" style="display:inline;"
                                          onsubmit="return confirm('Remove <?= esc(addslashes((string) $p['name'])) ?> from your HPR professionals?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" style="padding:5px 10px;background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;border-radius:4px;font-size:12px;cursor:pointer;">
                                            <i class="fa fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
