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
                    <th>ABHA Card</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($profiles as $p):
                    $json     = is_string($p->profile_json ?? null) ? json_decode($p->profile_json, true) : null;
                    $photoB64 = is_array($json) ? ($json['photo'] ?? null) : null;
                    $abhaStatus = strtoupper((string) ($p->abha_status ?? 'ACTIVE'));
                    $abhaType   = strtoupper((string) ($p->abha_type ?? ''));
                    $cardPayload = json_encode([
                        'name'             => (string)($p->full_name ?? ''),
                        'abha_number'      => (string)($p->abha_number ?? ''),
                        'abha_address'     => (string)($p->phr_address ?? $p->abha_address ?? ''),
                        'gender'           => (string)($p->gender ?? ''),
                        'dob'              => (string)($p->date_of_birth ?? ''),
                        'mobile'           => (string)($p->mobile ?? ''),
                        'photo'            => $photoB64 ?? '',
                        'abha_card_stored' => is_array($json) && !empty($json['abha_card_base64']),
                    ]);
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
                    <td>
                        <button type="button"
                                onclick="showAbhaCard(this)"
                                data-profile='<?= htmlspecialchars($cardPayload, ENT_QUOTES, 'UTF-8') ?>'
                                style="background:#1d4ed8;color:#fff;border:none;border-radius:5px;padding:5px 11px;font-size:12px;cursor:pointer;white-space:nowrap;">
                            <i class="fa fa-id-card"></i> View Card
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

<!-- ABHA Card Modal — NHA official style -->
<div id="abhaCardModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:10px;max-width:600px;width:96%;box-shadow:0 12px 40px rgba(0,0,0,0.3);overflow:hidden;">
        <div style="background:#1a3a6b;padding:11px 18px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-weight:700;font-size:14px;"><i class="fa fa-id-card" style="margin-right:7px;"></i>ABHA Health Card</span>
            <button onclick="closeAbhaCard()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <!-- Official ABDM card PNG (stored at creation/verify time) -->
        <div id="abhaRealCard" style="display:none;padding:14px 16px 8px;text-align:center;">
            <img id="abhaRealCardImg" src="" alt="ABHA Card" style="max-width:100%;border:1px solid #ccc;border-radius:4px;"
                 onerror="document.getElementById('abhaRealCard').style.display='none';document.getElementById('abhaCardInner').parentElement.style.display='block';">
        </div>
        <!-- Fallback locally generated card -->
        <div style="padding:14px 16px 8px;">
            <div id="abhaCardInner" style="border:1px solid #ccc;border-radius:4px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
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
        <div id="abhaCardActions" style="padding:8px 16px 14px;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
var _currentAbhaNum = '';
function showAbhaCard(btn) {
    var p = JSON.parse(btn.getAttribute('data-profile'));
    _currentAbhaNum = p.abha_number || '';
    var realCard = document.getElementById('abhaRealCard');
    var htmlWrap = document.getElementById('abhaCardInner').parentElement;
    var actions  = document.getElementById('abhaCardActions');
    if (p.abha_card_stored && _currentAbhaNum) {
        realCard.style.display = 'block';
        htmlWrap.style.display = 'none';
        document.getElementById('abhaRealCardImg').src = '/admin/m1/patient-card?abha_number=' + encodeURIComponent(_currentAbhaNum);
        actions.innerHTML = '<a href="/admin/m1/patient-card?abha_number=' + encodeURIComponent(_currentAbhaNum) + '" download="abha-card-' + _currentAbhaNum + '.png" style="background:#16a34a;color:#fff;text-decoration:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;display:inline-block;"><i class="fa fa-download"></i> Download Card</a>'
            + '<button onclick="closeAbhaCard()" style="background:#f1f5f9;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Close</button>';
    } else {
        realCard.style.display = 'none';
        htmlWrap.style.display = 'block';
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
        actions.innerHTML = '<button onclick="printAbhaCard()" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;"><i class="fa fa-print"></i> Print Card</button>'
            + '<button onclick="closeAbhaCard()" style="background:#f1f5f9;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Close</button>';
    }
    document.getElementById('abhaCardModal').style.display = 'flex';
}
function closeAbhaCard() { document.getElementById('abhaCardModal').style.display = 'none'; }
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
