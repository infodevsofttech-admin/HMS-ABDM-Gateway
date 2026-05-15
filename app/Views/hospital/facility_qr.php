<?= $this->extend('layout/hospital_layout') ?>
<?= $this->section('content') ?>

<?php
$hfrId       = esc((string) ($hospital->hfr_id ?? ''));
$hospitalName = esc((string) ($hospital->hospital_name ?? 'Your Hospital'));
$qrContent   = $hfrId !== '' ? 'hipcounter://' . $hfrId : '';
?>

<div class="hp-page-header">
    <div>
        <h1 class="hp-page-title"><i class="fas fa-qrcode"></i> Health Facility QR</h1>
        <p class="hp-page-sub">Print this QR code and place it at your reception counter. Patients scan it with the ABHA app to share their health records.</p>
    </div>
</div>

<?php if ($hfrId === ''): ?>
<div class="hp-card mb-4">
    <div class="hp-card-body text-center py-5">
        <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
        <h4>HFR / HIP ID Not Configured</h4>
        <p class="text-muted mb-3">Your hospital's Health Facility Registry ID (HFR ID) has not been set yet. The gateway administrator needs to register your facility and set the HFR ID before this QR can be generated.</p>
        <p class="text-muted small">Contact your ABDM gateway admin to complete the HIP registration via <strong>Admin → M1 → Scan &amp; Share Setup</strong>.</p>
    </div>
</div>
<?php else: ?>

<!-- Action buttons (hidden on print) -->
<div class="d-flex gap-2 mb-3 hidden-print">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Print QR Card
    </button>
    <button onclick="downloadQrPng()" class="btn btn-outline-secondary">
        <i class="fas fa-download"></i> Download PNG
    </button>
</div>

<!-- Printable QR Card -->
<div class="hp-card" id="qr-card-wrapper">
    <div class="hp-card-body p-0">
        <!-- Print area -->
        <div id="qr-print-area" class="qr-print-area">
            <!-- Hospital brand row -->
            <div class="qr-header">
                <div class="qr-hospital-icon"><i class="fas fa-hospital-alt"></i></div>
                <div>
                    <div class="qr-hospital-name"><?= $hospitalName ?></div>
                    <div class="qr-facility-id">Facility ID: <?= $hfrId ?></div>
                </div>
            </div>

            <hr class="qr-divider">

            <!-- QR code + instructions row -->
            <div class="qr-body">
                <!-- QR code -->
                <div class="qr-code-wrap">
                    <div id="facility-qr"></div>
                    <div class="qr-footer-label">Scan with ABHA App</div>
                </div>

                <!-- Instructions -->
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

            <!-- Footer -->
            <div class="qr-card-footer">
                <span><i class="fas fa-shield-alt"></i> Powered by ABDM (Ayushman Bharat Digital Mission)</span>
                <span class="qr-print-date">Printed: <?= date('d M Y') ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Info panel (screen only) -->
<div class="hp-card mt-3 hidden-print">
    <div class="hp-card-body">
        <h6 class="fw-semibold mb-2"><i class="fas fa-info-circle text-info"></i> About this QR</h6>
        <ul class="mb-0 small text-muted">
            <li>This QR encodes your HIP ID: <code><?= $hfrId ?></code></li>
            <li>When a patient scans it, ABDM will forward their ABHA health profile to your reception queue.</li>
            <li>New patient records will appear in your <a href="/portal/opd-queue">OPD Queue</a>.</li>
            <li>Print at <strong>A5</strong> or larger for best readability at reception.</li>
        </ul>
    </div>
</div>

<?php endif; ?>

<style>
/* ── QR card layout ───────────────────────────────────────────── */
.qr-print-area {
    max-width: 600px;
    margin: 0 auto;
    padding: 28px 32px 20px;
    font-family: 'Segoe UI', Arial, sans-serif;
    color: #1a1a2e;
}

.qr-header {
    display: flex;
    align-items: center;
    gap: 16px;
}

.qr-hospital-icon {
    width: 52px; height: 52px;
    background: #2c7be5;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 24px;
    flex-shrink: 0;
}

.qr-hospital-name {
    font-size: 20px;
    font-weight: 700;
    line-height: 1.2;
}

.qr-facility-id {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.qr-divider {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 18px 0;
}

.qr-body {
    display: flex;
    align-items: flex-start;
    gap: 32px;
}

.qr-code-wrap {
    flex-shrink: 0;
    text-align: center;
}

#facility-qr {
    display: inline-block;
    padding: 10px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    background: #fff;
}

#facility-qr img, #facility-qr canvas {
    display: block;
}

.qr-footer-label {
    margin-top: 6px;
    font-size: 11px;
    color: #6b7280;
    font-weight: 600;
    letter-spacing: .03em;
}

.qr-instructions {
    flex: 1;
}

.qr-inst-title {
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 8px;
    color: #374151;
}

.qr-inst-list {
    padding-left: 18px;
    margin: 0 0 14px;
    font-size: 13px;
    line-height: 1.8;
    color: #374151;
}

.qr-inst-hindi {
    background: #f0f9ff;
    border-left: 3px solid #2c7be5;
    padding: 8px 12px;
    font-size: 12.5px;
    line-height: 1.7;
    color: #1e3a5f;
    border-radius: 0 4px 4px 0;
}

.qr-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 11px;
    color: #9ca3af;
    gap: 8px;
    flex-wrap: wrap;
}

.qr-print-date { font-style: italic; }

/* ── Print overrides ──────────────────────────────────────────── */
@media print {
    .hidden-print { display: none !important; }

    /* Remove portal chrome */
    .hp-topbar, .hp-mobile-nav, .hp-sidebar,
    .hp-page-header .hp-page-sub,
    .hp-topnav { display: none !important; }

    body, .hp-main, .hp-content, .hp-card {
        background: #fff !important;
        box-shadow: none !important;
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .qr-print-area {
        max-width: 100%;
        padding: 20px;
    }

    #facility-qr {
        border-color: #000;
    }
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
        // qrcode.js may render an img instead of canvas in some browsers
        var img = document.querySelector('#facility-qr img');
        if (!img) { alert('QR not ready yet, please wait a moment.'); return; }
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
