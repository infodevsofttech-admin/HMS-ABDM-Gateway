<?= $this->extend('layout/admin_layout') ?>

<?= $this->section('content') ?>

<h1>Dashboard</h1>

<div style="display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 16px; margin-bottom: 30px;">
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #1d4ed8;">
        <strong style="color: #6b7280; font-size: 12px;">Hospitals</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $hospitalCount) ?></div>
    </div>
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #059660;">
        <strong style="color: #6b7280; font-size: 12px;">Users</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $userCount) ?></div>
    </div>
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #dc2626;">
        <strong style="color: #6b7280; font-size: 12px;">HMS Credentials</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $hmsCredentialCount) ?></div>
    </div>
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #f59e0b;">
        <strong style="color: #6b7280; font-size: 12px;">Request Logs</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $requestLogCount) ?></div>
    </div>
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #7c3aed;">
        <strong style="color: #6b7280; font-size: 12px;">Audit Logs</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $auditCount) ?></div>
    </div>
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #0ea5e9;">
        <strong style="color: #6b7280; font-size: 12px;">Bundles</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $bundleCount) ?></div>
    </div>
    <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08); border-left: 4px solid #6366f1;">
        <strong style="color: #6b7280; font-size: 12px;">Test Submissions</strong>
        <div style="font-size: 32px; font-weight: 700; color: #1f2937; margin-top: 8px;"><?= esc((string) $testLogCount) ?></div>
    </div>
</div>

<h2 style="margin-bottom: 16px; color: #1f2937;">Recent Hospitals</h2>
<div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">ID</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">Hospital</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">HFR ID</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">Mode</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hospitals)): ?>
                <tr>
                    <td colspan="5" style="border: 1px solid #e5e7eb; padding: 12px; text-align: center; color: #6b7280;">No hospitals yet</td>
                </tr>
            <?php else: ?>
                <?php foreach ($hospitals as $hospital): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="border: 1px solid #e5e7eb; padding: 12px;"><?= esc((string) $hospital->id) ?></td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;"><?= esc((string) $hospital->hospital_name) ?></td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;"><?= esc((string) $hospital->hfr_id) ?></td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;">
                            <span style="display: inline-block; padding: 4px 12px; background: <?= $hospital->gateway_mode === 'live' ? '#fee2e2' : '#dbeafe' ?>; color: <?= $hospital->gateway_mode === 'live' ? '#991b1b' : '#1d4ed8' ?>; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                <?= strtoupper($hospital->gateway_mode) ?>
                            </span>
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;">
                            <span style="color: <?= $hospital->is_active ? '#059660' : '#991b1b' ?>;">
                                <?= $hospital->is_active ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
