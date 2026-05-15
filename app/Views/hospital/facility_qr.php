<?= $this->extend('layout/hospital_layout') ?>
<?= $this->section('content') ?>

<?php
$hfrId        = (string) ($hospital->hfr_id        ?? '');
$hospitalName = esc((string) ($hospital->hospital_name ?? 'Your Hospital'));
$qrData       = (string) ($hospital->facility_qr_data ?? '');
$hasQr        = $qrData !== '';
?>

<div class="hp-page-header">
    <div>
        <h1 class="hp-page-title"><i class="fas fa-qrcode"></i> Health Facility QR</h1>
        <p class="hp-page-sub">Print this QR code and place it at your reception counter. Patients scan it with the ABHA app to share their health records.</p>
    </div>
</div>

<?php if ($message = ($message ?? null)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?= esc($message) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($error = ($error ?? null)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?= esc($error) ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<?php if ($hasQr): ?>

<!-- Action buttons -->
<div class="d-flex gap-2 mb-3 hidden-print">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Print QR Card
    </button>
    <button class="btn btn-outline-secondary" data-toggle="collapse" data-target="#replace-form">
        <i class="fas fa-upload"></i> Replace QR
    </button>
</div>

<!-- Replace form (collapsed by default) -->
<div id="replace-form" class="collapse mb-3 hidden-print">
    <div class="hp-card">
        <div class="hp-card-body">
            <h6 class="mb-2">Upload new official HFR Facility QR</h6>
            <form method="post" action="/portal/facility-qr/upload" enctype="multipart/form-data" class="d-flex gap-2 align-items-end flex-wrap">
                <?= csrf_field() ?>
                <div>
                    <input type="file" name="facility_qr" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control" required>
                    <div class="form-text">PNG/JPEG, max 2 MB</div>
                </div>
                <button type="submit" class="btn btn-primary">Upload &amp; Save</button>
            </form>
        </div>
    </div>
</div>

<!-- Printable card with official QR -->
<div class="hp-card" id="qr-card-wrapper">
    <div class="hp-card-body p-0">
        <div id="qr-print-area" class="qr-print-area">
            <div class="qr-header">
                <div class="qr-hospital-icon"><i class="fas fa-hospital-alt"></i></div>
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
                        <li>Open your <strong>ABHA</strong> / PHR app on mobile.</li>
                        <li>Tap <strong>Scan Health Facility QR</strong>.</li>
                        <li>Point camera at this QR code.</li>
                        <li>Review &amp; approve sharing your health records.</li>
                    </ol>
                    <div class="qr-inst-hindi">
                        <strong>हिंदी:</strong> अपने ABHA ऐप में<br>
                        "स्वास्थ्य सुविधा QR स्कैन करें" पर टैप करें,<br>
                        QR स्कैन करें और अपना रिकॉर्ड साझा करें।
                    </div>
                </div>
            </div>
            <hr class="qr-divider">
            <div class="qr-card-footer">
                <span><i class="fas fa-shield-alt"></i> Powered by ABDM (Ayushman Bharat Digital Mission)</span>
                <span class="qr-print-date">Printed: <?= date('d M Y') ?></span>
            </div>
        </div>
    </div>
</div>

<?php else: ?>

<!-- Upload prompt -->
<div class="hp-card mb-4">
    <div class="hp-card-body">
        <div class="qr-upload-info mb-3">
            <div class="qr-upload-icon"><i class="fas fa-qrcode"></i></div>
            <div>
                <h5 class="mb-1">Upload your official HFR Facility QR</h5>
                <p class="text-muted mb-0 small">
                    The QR code must be the <strong>official one issued by HFR</strong> (Health Facility Registry).
                    A self-generated QR will show "Invalid QR Code" in the ABHA app.
                </p>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-semibold mb-2">How to get your Facility QR</h6>
                <ol class="small text-muted" style="line-height:2;">
                    <li>Go to <a href="https://facilityregistry.abdm.gov.in" target="_blank" rel="noopener">facilityregistry.abdm.gov.in</a></li>
                    <li>Log in with your HFR credentials</li>
                    <li>Open your facility profile</li>
                    <li>Download the <strong>QR Code PNG</strong></li>
                    <li>Upload it below</li>
                </ol>
                <p class="small text-muted mb-0">Sandbox: <a href="https://facilitysbx.abdm.gov.in" target="_blank" rel="noopener">facilitysbx.abdm.gov.in</a></p>
            </div>
            <div class="col-md-6">
                <h6 class="fw-semibold mb-2">Upload QR Image</h6>
                <form method="post" action="/portal/facility-qr/upload" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <input type="file" name="facility_qr" accept="image/png,image/jpeg,image/gif,image/webp" class="form-control" required>
                        <div class="form-text">PNG/JPEG, max 2 MB</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload"></i> Upload &amp; Save
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<style>
.qr-upload-info { display:flex; align-items:flex-start; gap:16px; }
.qr-upload-icon { width:52px; height:52px; background:#e0f2fe; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#0369a1; font-size:24px; flex-shrink:0; }
.qr-print-area { max-width:600px; margin:0 auto; padding:28px 32px 20px; font-family:'Segoe UI',Arial,sans-serif; color:#1a1a2e; }
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
    .hp-topbar, .hp-mobile-nav, .hp-topnav { display:none !important; }
    body, .hp-main, .hp-content, .hp-card { background:#fff !important; box-shadow:none !important; border:none !important; margin:0 !important; padding:0 !important; }
    .qr-print-area { max-width:100%; padding:20px; }
    .qr-img-wrap { border-color:#000; }
}
</style>

<?= $this->endSection() ?>
