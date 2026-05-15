<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'Patient Records'; ?>

<?= $this->section('content') ?>

<?php
$patients = $patients ?? [];
$search   = $search ?? '';
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-user-injured"></i> Patient Records</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item active">Patients</li>
        </ol></nav>
    </div>
    <div>
        <form method="GET" action="/portal/patients" style="display:flex;gap:8px;margin:0;">
            <input type="text" name="search" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search ABHA / Name / Mobile" style="width:240px;">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
            <?php if ($search !== ''): ?>
            <a href="/portal/patients" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="hp-content">

    <?php if ($search !== ''): ?>
    <div class="alert alert-info py-2" style="font-size:13px;">
        <i class="fas fa-search mr-1"></i> Showing results for "<strong><?= esc($search) ?></strong>" — <?= count($patients) ?> record(s) found.
    </div>
    <?php endif; ?>

    <div class="hp-card">
        <div class="hp-card-head">
            <i class="fas fa-list"></i> Patient Master — <?= count($patients) ?> record(s)
            <a href="/portal/abha-tools" style="margin-left:auto;font-size:12px;font-weight:500;color:var(--hp-primary);">+ Add Patient via ABHA Tools</a>
        </div>
        <?php if (empty($patients)): ?>
        <div class="hp-card-body" style="text-align:center;padding:50px 20px;color:#6c757d;">
            <i class="fas fa-user-slash" style="font-size:36px;color:#dee2e6;display:block;margin-bottom:10px;"></i>
            <?= $search !== '' ? 'No patients found matching your search.' : 'No patients yet. Use ABHA Tools to validate or create patient ABHA.' ?>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="hp-tbl">
            <thead><tr>
                <th>#</th>
                <th>ABHA Number</th>
                <th>Name</th>
                <th>Gender</th>
                <th>DOB / Year</th>
                <th>Mobile</th>
                <th>Status</th>
                <th>Verified At</th>
                <th>Actions</th>
            </tr></thead>
            <tbody>
            <?php $i = 1; foreach ($patients as $p): ?>
            <?php
                $profileJson = [];
                if (!empty($p->profile_json)) {
                    $decoded = json_decode((string)$p->profile_json, true);
                    if (is_array($decoded)) $profileJson = $decoded;
                }
                $photoB64    = $profileJson['photo'] ?? null;
                $cardPayload = json_encode([
                    'name'         => (string)($p->full_name ?? '—'),
                    'abha_number'  => (string)($p->abha_number ?? ''),
                    'abha_address' => (string)($p->abha_address ?? $p->phr_address ?? ''),
                    'gender'       => (string)($p->gender ?? ''),
                    'dob'          => (string)($p->date_of_birth ?? $p->year_of_birth ?? ''),
                    'mobile'       => (string)($p->mobile ?? ''),
                    'photo'        => $photoB64 ?? '',
                ]);
            ?>
            <tr>
                <td style="color:#adb5bd;font-size:12px;"><?= $i++ ?></td>
                <td style="font-weight:700;color:var(--hp-primary);font-size:12px;"><?= esc((string)($p->abha_number ?? '—')) ?></td>
                <td>
                    <div style="font-weight:600;"><?= esc((string)($p->full_name ?? '—')) ?></div>
                    <?php if (!empty($p->abha_address)): ?>
                    <div style="font-size:11px;color:#6c757d;"><?= esc((string)$p->abha_address) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= esc((string)($p->gender ?? '—')) ?></td>
                <td style="font-size:12px;">
                    <?= esc((string)($p->date_of_birth ?? ($p->year_of_birth ?? '—'))) ?>
                </td>
                <td><?= esc((string)($p->mobile ?? '—')) ?></td>
                <td><span class="hb hb-<?= ($p->status ?? '') === 'verified' ? 'green' : 'yellow' ?>"><?= esc(strtoupper((string)($p->status ?? 'verified'))) ?></span></td>
                <td style="font-size:11px;color:#6c757d;"><?= esc((string)($p->last_verified_at ?? '—')) ?></td>
                <td>
                    <button type="button"
                            onclick="showAbhaCard(this)"
                            data-profile='<?= htmlspecialchars($cardPayload, ENT_QUOTES, 'UTF-8') ?>'
                            style="background:#0d6efd;color:#fff;border:none;border-radius:5px;padding:4px 10px;font-size:12px;cursor:pointer;">
                        <i class="fas fa-id-card"></i> ABHA Card
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.hp-content -->

<!-- ABHA Card Modal — NHA official style -->
<div id="abhaCardModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;max-width:600px;width:96%;box-shadow:0 12px 40px rgba(0,0,0,0.3);overflow:hidden;">
        <!-- Modal chrome -->
        <div style="background:#1a3a6b;padding:11px 18px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-weight:700;font-size:14px;"><i class="fas fa-id-card" style="margin-right:7px;"></i>ABHA Health Card</span>
            <button onclick="closeAbhaCard()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <!-- NHA Card -->
        <div style="padding:14px 16px 8px;">
            <div id="abhaCardInner" style="border:1px solid #ccc;border-radius:4px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
                <!-- Blue header bar -->
                <div style="background:#1a56b0;padding:10px 16px;display:flex;align-items:center;gap:10px;">
                    <div style="background:#fff;border-radius:5px;padding:5px 8px;text-align:center;flex-shrink:0;line-height:1.3;">
                        <div style="font-size:7px;color:#1a56b0;font-weight:700;text-transform:uppercase;">national</div>
                        <div style="font-size:7px;color:#1a56b0;font-weight:700;text-transform:uppercase;">health</div>
                        <div style="font-size:7px;color:#1a56b0;font-weight:700;text-transform:uppercase;">authority</div>
                    </div>
                    <div style="flex:1;text-align:center;color:#fff;padding:0 6px;">
                        <div style="font-size:14px;font-weight:700;line-height:1.3;">Ayushman Bharat Health Account (ABHA)</div>
                        <div style="font-size:11px;margin-top:4px;line-height:1.3;">आयुष्मान भारत स्वास्थ्य खाता (आभा)</div>
                    </div>
                    <div style="width:42px;height:42px;background:#fff;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,0.5);">
                        <span style="font-size:7.5px;font-weight:900;color:#1a56b0;text-align:center;line-height:1.2;">ABDM</span>
                    </div>
                </div>
                <!-- Card body -->
                <div style="background:#fff;padding:14px 16px;display:flex;gap:14px;align-items:flex-start;">
                    <div id="cardPhoto" style="width:90px;height:108px;background:#f3f4f6;flex-shrink:0;overflow:hidden;border:1px solid #bbb;display:flex;align-items:center;justify-content:center;">
                        <span style="font-size:32px;">👤</span>
                    </div>
                    <div style="flex:1;min-width:0;padding-left:4px;">
                        <div style="margin-bottom:10px;">
                            <div style="font-size:10px;color:#6b7280;">Name/नाम</div>
                            <div id="cardName" style="font-size:17px;font-weight:700;color:#111;margin-top:1px;"></div>
                        </div>
                        <div style="margin-bottom:10px;">
                            <div style="font-size:10px;color:#6b7280;">ABHA number/आभा-संख्या</div>
                            <div id="cardAbhaLarge" style="font-size:15px;font-weight:700;color:#111;margin-top:1px;letter-spacing:.02em;"></div>
                        </div>
                        <div>
                            <div style="font-size:10px;color:#6b7280;">ABHA address/आभा पता</div>
                            <div id="cardAbha" style="font-size:13px;font-weight:600;color:#111;margin-top:1px;word-break:break-all;"></div>
                        </div>
                    </div>
                    <div id="cardQr" style="width:110px;height:110px;flex-shrink:0;display:flex;align-items:center;justify-content:center;"></div>
                </div>
                <!-- Footer row -->
                <div style="border-top:1px solid #e5e7eb;padding:10px 16px;display:flex;background:#fafbfc;">
                    <div style="flex:1;">
                        <div style="font-size:10px;color:#6b7280;">Gender/लिंग</div>
                        <div id="cardGender" style="font-size:13px;font-weight:700;color:#111;margin-top:2px;"></div>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:10px;color:#6b7280;">Date of birth/जन्मतिथि</div>
                        <div id="cardDob" style="font-size:13px;font-weight:700;color:#111;margin-top:2px;"></div>
                    </div>
                    <div style="flex:1;">
                        <div style="font-size:10px;color:#6b7280;">Mobile/मोबाइल</div>
                        <div id="cardMobile" style="font-size:13px;font-weight:700;color:#111;margin-top:2px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Actions -->
        <div style="padding:8px 16px 14px;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="printAbhaCard()" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;"><i class="fas fa-print"></i> Print Card</button>
            <button onclick="closeAbhaCard()" style="background:#f1f5f9;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
function showAbhaCard(btn) {
    var p = JSON.parse(btn.getAttribute('data-profile'));
    document.getElementById('cardName').textContent      = p.name || '—';
    document.getElementById('cardAbha').textContent      = p.abha_address || '';
    document.getElementById('cardAbhaLarge').textContent = formatAbhaNum(p.abha_number);
    document.getElementById('cardGender').textContent    = genderLabel(p.gender);
    document.getElementById('cardDob').textContent       = p.dob || '—';
    document.getElementById('cardMobile').textContent    = p.mobile || '—';
    var photoEl = document.getElementById('cardPhoto');
    if (p.photo) {
        photoEl.innerHTML = '<img src="data:image/jpeg;base64,' + p.photo + '" style="width:90px;height:108px;object-fit:cover;">';
    } else {
        photoEl.innerHTML = '<span style="font-size:32px;">👤</span>';
    }
    var qrEl = document.getElementById('cardQr');
    qrEl.innerHTML = '';
    var qrText = p.abha_number || p.abha_address || '';
    if (typeof QRCode !== 'undefined' && qrText) {
        new QRCode(qrEl, { text: qrText, width: 110, height: 110, correctLevel: QRCode.CorrectLevel.M });
    }
    document.getElementById('abhaCardModal').style.display = 'flex';
}
function closeAbhaCard() {
    document.getElementById('abhaCardModal').style.display = 'none';
}
function printAbhaCard() {
    var qrEl = document.getElementById('cardQr');
    var qrCanvas = qrEl ? qrEl.querySelector('canvas') : null;
    if (qrCanvas) {
        var dataUrl = qrCanvas.toDataURL('image/png');
        qrEl.innerHTML = '<img src="' + dataUrl + '" style="width:110px;height:110px;">';
    }
    var cardHtml = document.getElementById('abhaCardInner').outerHTML;
    var w = window.open('', '_blank', 'width=660,height=560');
    w.document.write('<!DOCTYPE html><html><head><title>ABHA Card</title><style>body{font-family:Arial,sans-serif;margin:20px;}@media print{body{margin:0;}.no-print{display:none;}}</style></head><body>');
    w.document.write(cardHtml);
    w.document.write('<br><button class="no-print" onclick="window.print()" style="margin-top:12px;padding:8px 22px;background:#1d4ed8;color:#fff;border:none;border-radius:5px;cursor:pointer;font-size:14px;">🖨 Print</button>');
    w.document.write('</body></html>');
    w.document.close();
}
function formatAbhaNum(n) {
    if (!n) return '';
    var d = n.replace(/[^0-9]/g, '');
    if (d.length === 14) return d.substr(0,2)+'-'+d.substr(2,4)+'-'+d.substr(6,4)+'-'+d.substr(10,4);
    return n;
}
function genderLabel(g) {
    if (!g) return '—';
    var u = g.toUpperCase();
    if (u === 'M') return 'Male'; if (u === 'F') return 'Female'; if (u === 'O') return 'Other';
    return g;
}
document.getElementById('abhaCardModal').addEventListener('click', function(e) {
    if (e.target === this) closeAbhaCard();
});
</script>

<?= $this->endSection() ?>
