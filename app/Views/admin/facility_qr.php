<?= $this->extend('layout/admin_layout') ?>
<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-qrcode"></i> Health Facility QR</h3>
    </div>
</div>
<div class="clearfix"></div>

<?php
$hfrId        = esc((string) ($hospital->hfr_id ?? ''));
$hospitalName = esc((string) ($hospital->hospital_name ?? ''));
$currentId    = (int) ($hospital->id ?? 0);
$qrContent    = $hfrId !== '' ? 'hipcounter://' . $hfrId : '';
?>

<!-- Hospital selector -->
<?php if (!empty($hospitals) && count($hospitals) > 1): ?>
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_content" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <label style="margin:0;font-weight:600;">Select Hospital:</label>
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

        <?php if ($hfrId === ''): ?>
        <div class="x_panel">
            <div class="x_content text-center" style="padding:40px 0;">
                <i class="fa fa-exclamation-triangle fa-3x" style="color:#f59e0b;margin-bottom:12px;"></i>
                <h4>HFR ID Not Set</h4>
                <p style="color:#6b7280;">
                    <?php if ($hospital): ?>
                        Hospital <strong><?= $hospitalName ?></strong> does not have an HFR ID configured.
                        <a href="/admin/hospitals">Edit in Hospitals →</a>
                    <?php else: ?>
                        No active hospital found. <a href="/admin/hospitals">Add a hospital first →</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php else: ?>

        <!-- Action buttons -->
        <div style="margin-bottom:14px;display:flex;gap:10px;" class="hidden-print">
            <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="fa fa-print"></i> Print QR Card</button>
            <button onclick="downloadQrPng()" class="btn btn-default btn-sm"><i class="fa fa-download"></i> Download PNG</button>
            <a href="/admin/hospitals" class="btn btn-default btn-sm hidden-print"><i class="fa fa-arrow-left"></i> Back to Hospitals</a>
        </div>

        <div class="x_panel" id="qr-card-wrapper">
            <div class="x_content" style="padding:0;">
                <div id="qr-print-area" class="qr-print-area">
                    <!-- Header -->
                    <div class="qr-header">
                        <div class="qr-hospital-icon"><i class="fa fa-hospital-o"></i></div>
                        <div>
                            <div class="qr-hospital-name"><?= $hospitalName ?></div>
                            <div class="qr-facility-id">Facility ID (HIP ID): <?= $hfrId ?></div>
                        </div>
                    </div>

                    <hr class="qr-divider">

                    <div class="qr-body">
                        <div class="qr-code-wrap">
                            <div id="facility-qr"></div>
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

        <div class="x_panel hidden-print">
            <div class="x_title"><h2><i class="fa fa-info-circle"></i> About this QR</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <ul style="font-size:13px;color:#374151;line-height:2;">
                    <li>QR encodes HIP ID: <code><?= $hfrId ?></code></li>
                    <li>Patients scan it with their ABHA app → profile is forwarded to this hospital's OPD queue.</li>
                    <li>Print at <strong>A5</strong> or larger for best readability.</li>
                    <li>If this hospital's HIP is not yet registered with ABDM, complete setup via <a href="/admin/m1/scan-share-setup">Scan &amp; Share Setup →</a></li>
                </ul>
            </div>
        </div>

        <?php endif; ?>

    </div>
</div>

<style>
.qr-print-area {
    max-width: 580px;
    margin: 0 auto;
    padding: 28px 32px 20px;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #1a1a2e;
}
.qr-header { display:flex; align-items:center; gap:16px; }
.qr-hospital-icon {
    width:52px; height:52px; background:#2c7be5; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:24px; flex-shrink:0;
}
.qr-hospital-name { font-size:20px; font-weight:700; line-height:1.2; }
.qr-facility-id { font-size:12px; color:#6b7280; margin-top:2px; }
.qr-divider { border:none; border-top:1px solid #e5e7eb; margin:18px 0; }
.qr-body { display:flex; align-items:flex-start; gap:32px; }
.qr-code-wrap { flex-shrink:0; text-align:center; }
#facility-qr { display:inline-block; padding:10px; border:2px solid #e5e7eb; border-radius:8px; background:#fff; }
#facility-qr img, #facility-qr canvas { display:block; }
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
    #facility-qr { border-color:#000; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function () {
    var qrContent = <?= json_encode($qrContent) ?>;
    if (!qrContent) return;
    new QRCode(document.getElementById('facility-qr'), {
        text: qrContent,
        width: 180,
        height: 180,
        correctLevel: QRCode.CorrectLevel.M,
    });
})();

function downloadQrPng() {
    var canvas = document.querySelector('#facility-qr canvas');
    if (!canvas) {
        var img = document.querySelector('#facility-qr img');
        if (!img) { alert('QR not ready yet.'); return; }
        var a = document.createElement('a');
        a.href = img.src;
        a.download = 'facility-qr-<?= $hfrId ?>.png';
        a.click();
        return;
    }
    var a = document.createElement('a');
    a.download = 'facility-qr-<?= $hfrId ?>.png';
    a.href = canvas.toDataURL('image/png');
    a.click();
}
</script>

<?= $this->endSection() ?>
