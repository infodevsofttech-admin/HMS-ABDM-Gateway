<?= $this->extend('layout/admin_layout') ?>
<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-qrcode"></i> Health Facility QR</h3>
    </div>
</div>
<div class="clearfix"></div>

<?php
$hfrId        = (string) ($hospital->hfr_id        ?? '');
$hospitalName = esc((string) ($hospital->hospital_name ?? ''));
$qrData       = (string) ($hospital->facility_qr_data ?? '');
$hasQr        = $qrData !== '';
$currentId    = (int) ($hospital->id ?? 0);
?>

<?php if ($message = ($message ?? null)): ?>
<div class="alert alert-success alert-dismissible fade show hidden-print" role="alert">
    <i class="fa fa-check-circle"></i> <?= esc($message) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($error = ($error ?? null)): ?>
<div class="alert alert-danger alert-dismissible fade show hidden-print" role="alert">
    <i class="fa fa-exclamation-circle"></i> <?= esc($error) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Hospital selector -->
<?php if (!empty($hospitals) && count($hospitals) > 1): ?>
<div class="row hidden-print">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_content" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <label style="margin:0;font-weight:600;">Hospital:</label>
                <form method="get" action="/admin/facility-qr" style="display:flex;gap:8px;align-items:center;">
                    <select name="hospital_id" onchange="this.form.submit()" style="padding:6px 10px;border:1px solid #d1d5db;border-radius:4px;">
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= esc((string) $h->id) ?>" <?= $h->id === $currentId ? 'selected' : '' ?>>
                                <?= esc($h->hospital_name) ?> (<?= esc($h->hfr_id) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">

        <?php if (!$hospital): ?>
        <div class="x_panel">
            <div class="x_content text-center" style="padding:40px 0;">
                <i class="fa fa-exclamation-triangle fa-3x" style="color:#f59e0b;margin-bottom:12px;"></i>
                <h4>No active hospital found</h4>
                <p><a href="/admin/hospitals">Add a hospital first →</a></p>
            </div>
        </div>

        <?php elseif ($hasQr): ?>

        <!-- Action buttons -->
        <div style="margin-bottom:14px;display:flex;gap:10px;" class="hidden-print">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa fa-print"></i> Print QR Card</button>
            <button class="btn btn-default btn-sm" data-toggle="collapse" data-target="#replace-form"><i class="fa fa-upload"></i> Replace QR</button>
            <a href="/admin/hospitals" class="btn btn-default btn-sm"><i class="fa fa-arrow-left"></i> Back</a>
        </div>

        <!-- Replace form -->
        <div id="replace-form" class="collapse hidden-print" style="margin-bottom:14px;">
            <div class="x_panel" style="margin-bottom:0;">
                <div class="x_content">
                    <form method="post" action="/admin/facility-qr/upload" enctype="multipart/form-data" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                        <?= csrf_field() ?>
                        <input type="hidden" name="hospital_id" value="<?= $currentId ?>">
                        <div>
                            <label style="font-size:12px;font-weight:600;">New official HFR QR image (PNG/JPEG, max 2 MB)</label>
                            <input type="file" name="facility_qr" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm">Upload &amp; Save</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Printable card -->
        <div class="x_panel">
            <div class="x_content" style="padding:0;">
                <div id="qr-print-area" class="qr-print-area">
                    <div class="qr-header">
                        <div class="qr-hospital-icon"><i class="fa fa-hospital-o"></i></div>
                        <div>
                            <div class="qr-hospital-name"><?= $hospitalName ?></div>
                            <?php if ($hfrId !== ''): ?>
                            <div class="qr-facility-id">Facility ID: <?= esc($hfrId) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <hr class="qr-divider">
                    <div class="qr-body">
                        <div class="qr-code-wrap">
                            <div class="qr-img-wrap">
                                <img src="<?= $qrData ?>" alt="Facility QR" class="qr-official-img">
                            </div>
                            <div class="qr-footer-label">Scan with ABHA App</div>
                        </div>
                        <div class="qr-instructions">
                            <div class="qr-inst-title">How to use</div>
                            <ol class="qr-inst-list">
                                <li>Open the <strong>ABHA</strong> / PHR app on mobile.</li>
                                <li>Tap <strong>Scan Health Facility QR</strong>.</li>
                                <li>Point camera at this QR code.</li>
                                <li>Review &amp; approve sharing health records.</li>
                            </ol>
                            <div class="qr-inst-hindi">
                                <strong>हिंदी:</strong> ABHA ऐप में<br>
                                "स्वास्थ्य सुविधा QR स्कैन करें" पर टैप करें,<br>
                                QR स्कैन करें और रिकॉर्ड साझा करें।
                            </div>
                        </div>
                    </div>
                    <hr class="qr-divider">
                    <div class="qr-card-footer">
                        <span><i class="fa fa-shield"></i> Powered by ABDM (Ayushman Bharat Digital Mission)</span>
                        <span class="qr-print-date">Printed: <?= date('d M Y') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>

        <!-- Upload prompt -->
        <div class="x_panel">
            <div class="x_title"><h2><i class="fa fa-upload"></i> Upload Official HFR Facility QR — <?= $hospitalName ?></h2><div class="clearfix"></div></div>
            <div class="x_content">
                <div style="display:flex;gap:12px;align-items:flex-start;margin-bottom:16px;padding:14px;background:#fff8e1;border-left:4px solid #f59e0b;border-radius:4px;">
                    <i class="fa fa-exclamation-triangle" style="color:#f59e0b;font-size:20px;margin-top:2px;"></i>
                    <div style="font-size:13px;">
                        <strong>Official QR required.</strong>
                        A self-generated QR will show "Invalid QR Code" in the ABHA app.
                        The QR must be the one issued by <strong>HFR (Health Facility Registry)</strong> after facility registration.
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-5">
                        <h5>How to get the official QR</h5>
                        <ol style="font-size:13px;line-height:2.2;color:#374151;">
                            <li>Go to <a href="https://facilityregistry.abdm.gov.in" target="_blank" rel="noopener">facilityregistry.abdm.gov.in</a></li>
                            <li>Log in &amp; open the facility profile</li>
                            <li>Download the <strong>QR Code PNG</strong></li>
                            <li>Upload it here</li>
                        </ol>
                        <p style="font-size:12px;color:#6b7280;">Sandbox: <a href="https://facilitysbx.abdm.gov.in" target="_blank" rel="noopener">facilitysbx.abdm.gov.in</a></p>
                    </div>
                    <div class="col-md-7">
                        <h5>Upload QR Image</h5>
                        <form method="post" action="/admin/facility-qr/upload" enctype="multipart/form-data">
                            <?= csrf_field() ?>
                            <input type="hidden" name="hospital_id" value="<?= $currentId ?>">
                            <div class="form-group">
                                <input type="file" name="facility_qr" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control" required>
                                <span class="help-block">PNG/JPEG, max 2 MB</span>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-upload"></i> Upload &amp; Save</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

<style>
.qr-print-area { max-width:580px; margin:0 auto; padding:28px 32px 20px; font-family:'Segoe UI',Arial,sans-serif; color:#1a1a2e; }
.qr-header { display:flex; align-items:center; gap:16px; }
.qr-hospital-icon { width:52px; height:52px; background:#2c7be5; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:24px; flex-shrink:0; }
.qr-hospital-name { font-size:20px; font-weight:700; line-height:1.2; }
.qr-facility-id { font-size:12px; color:#6b7280; margin-top:2px; }
.qr-divider { border:none; border-top:1px solid #e5e7eb; margin:18px 0; }
.qr-body { display:flex; align-items:flex-start; gap:32px; }
.qr-code-wrap { flex-shrink:0; text-align:center; }
.qr-img-wrap { display:inline-block; padding:8px; border:2px solid #e5e7eb; border-radius:8px; background:#fff; }
.qr-official-img { width:180px; height:180px; display:block; object-fit:contain; }
.qr-footer-label { margin-top:6px; font-size:11px; color:#6b7280; font-weight:600; letter-spacing:.03em; }
.qr-instructions { flex:1; }
.qr-inst-title { font-size:14px; font-weight:700; margin-bottom:8px; color:#374151; }
.qr-inst-list { padding-left:18px; margin:0 0 14px; font-size:13px; line-height:1.8; color:#374151; }
.qr-inst-hindi { background:#f0f9ff; border-left:3px solid #2c7be5; padding:8px 12px; font-size:12.5px; line-height:1.7; color:#1e3a5f; border-radius:0 4px 4px 0; }
.qr-card-footer { display:flex; justify-content:space-between; align-items:center; font-size:11px; color:#9ca3af; gap:8px; flex-wrap:wrap; }
.qr-print-date { font-style:italic; }
@media print {
    .hidden-print { display:none !important; }
    .left_col, .nav_menu, .footer, .x_panel { box-shadow:none !important; border:none !important; }
    body, .right_col, .main_container { background:#fff !important; margin:0 !important; padding:0 !important; }
    .qr-print-area { max-width:100%; padding:20px; }
    .qr-img-wrap { border-color:#000; }
}
</style>

<?= $this->endSection() ?>
