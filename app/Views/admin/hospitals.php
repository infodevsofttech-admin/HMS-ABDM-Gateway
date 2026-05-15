<?= $this->extend('layout/admin_layout') ?>

<?= $this->section('content') ?>

<h1>Hospitals</h1>

<div style="background: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
    <h2 style="margin-bottom: 16px; color: #1f2937;">Add Hospital</h2>
    <form method="post" action="/admin/hospitals/create" style="display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 12px;">
        <?= csrf_field() ?>
        <input type="text" name="hospital_name" placeholder="Hospital Name" required style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
        <input type="text" name="hfr_id" placeholder="HFR ID" required style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
        <select name="gateway_mode" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
            <option value="test">TEST</option>
            <option value="live">LIVE</option>
        </select>
        <input type="text" name="contact_name" placeholder="Contact Name" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
        <input type="email" name="contact_email" placeholder="Contact Email" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
        <input type="tel" name="contact_phone" placeholder="Contact Phone" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
        <select name="is_active" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
        <button type="submit" style="padding: 10px; background: #1d4ed8; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Create Hospital</button>
    </form>
</div>

<div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.08);">
    <h2 style="margin-bottom: 16px; color: #1f2937;">Hospital List</h2>
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f3f4f6;">
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">ID</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">Hospital</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">HFR ID</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">Mode</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">Status</th>
                <th style="border: 1px solid #e5e7eb; padding: 12px; text-align: left; font-weight: 600; color: #374151;">HMS Config</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hospitals)): ?>
                <tr>
                    <td colspan="6" style="border: 1px solid #e5e7eb; padding: 12px; text-align: center; color: #6b7280;">No hospitals found</td>
                </tr>
            <?php else: ?>
                <?php foreach ($hospitals as $hospital): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="border: 1px solid #e5e7eb; padding: 12px;"><?= esc((string) $hospital->id) ?></td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;"><?= esc((string) $hospital->hospital_name) ?></td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;"><?= esc((string) $hospital->hfr_id) ?></td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;">
                            <form method="post" action="/admin/hospitals/<?= esc((string) $hospital->id) ?>/mode" style="display: inline-flex; gap: 8px;">
                                <?= csrf_field() ?>
                                <select name="gateway_mode" style="padding: 6px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 12px;">
                                    <option value="test" <?= $hospital->gateway_mode === 'test' ? 'selected' : '' ?>>TEST</option>
                                    <option value="live" <?= $hospital->gateway_mode === 'live' ? 'selected' : '' ?>>LIVE</option>
                                </select>
                                <button type="submit" style="padding: 6px 12px; background: #059660; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500;">Update</button>
                            </form>
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;">
                            <?php if ($hospital->is_active): ?>
                                <span style="display: inline-block; padding: 4px 12px; background: #d1fae5; color: #065f46; border-radius: 4px; font-size: 12px; font-weight: 600;">Active</span>
                            <?php else: ?>
                                <span style="display: inline-block; padding: 4px 12px; background: #fee2e2; color: #991b1b; border-radius: 4px; font-size: 12px; font-weight: 600;">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td style="border: 1px solid #e5e7eb; padding: 12px;">
                            <a href="/admin/hms-access?hospital_id=<?= esc((string) $hospital->id) ?>" style="color: #1d4ed8; text-decoration: none; font-size: 14px;">Configure →</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
