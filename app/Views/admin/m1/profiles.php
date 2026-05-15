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
                        'name'         => (string)($p->full_name ?? ''),
                        'abha_number'  => (string)($p->abha_number ?? ''),
                        'abha_address' => (string)($p->phr_address ?? $p->abha_address ?? ''),
                        'gender'       => (string)($p->gender ?? ''),
                        'dob'          => (string)($p->date_of_birth ?? ''),
                        'mobile'       => (string)($p->mobile ?? ''),
                        'photo'        => $photoB64 ?? '',
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

<!-- ABHA Card Modal -->
<div id="abhaCardModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;max-width:440px;width:95%;box-shadow:0 8px 32px rgba(0,0,0,0.28);overflow:hidden;">
        <div style="background:#1d4ed8;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-weight:700;font-size:15px;"><i class="fa fa-id-card" style="margin-right:7px;"></i>ABHA Health Card</span>
            <button onclick="closeAbhaCard()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <div style="padding:18px 20px 10px;">
            <div id="abhaCardInner" style="border:1.5px solid #e5e7eb;border-radius:10px;padding:16px 18px;background:#f0f4ff;">
                <div style="display:flex;align-items:center;margin-bottom:12px;">
                    <div style="background:#1d4ed8;color:#fff;font-weight:900;font-size:13px;padding:4px 10px;border-radius:5px;letter-spacing:.05em;">ABDM</div>
                    <span style="margin-left:8px;font-size:11px;color:#6b7280;font-weight:600;">Ayushman Bharat Digital Mission</span>
                </div>
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <div id="cardPhoto" style="width:64px;height:64px;border-radius:50%;background:#c7d2fe;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:2px solid #1d4ed8;">
                        <span style="font-size:28px;">👤</span>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div id="cardName" style="font-weight:800;font-size:16px;color:#1e293b;margin-bottom:3px;"></div>
                        <div id="cardAbha" style="font-size:13px;font-weight:700;color:#1d4ed8;letter-spacing:.04em;margin-bottom:5px;"></div>
                        <div style="display:flex;gap:16px;flex-wrap:wrap;">
                            <span style="font-size:12px;color:#374151;"><strong>DOB:</strong> <span id="cardDob"></span></span>
                            <span style="font-size:12px;color:#374151;"><strong>Gender:</strong> <span id="cardGender"></span></span>
                        </div>
                        <div id="cardMobile" style="font-size:12px;color:#374151;margin-top:3px;"></div>
                    </div>
                </div>
                <div style="margin-top:14px;background:#fff;border-radius:7px;padding:10px 14px;text-align:center;border:1px dashed #93c5fd;">
                    <div style="font-size:11px;color:#6b7280;margin-bottom:3px;">ABHA Number</div>
                    <div id="cardAbhaLarge" style="font-size:19px;font-weight:900;color:#1d4ed8;letter-spacing:.08em;"></div>
                </div>
            </div>
        </div>
        <div style="padding:10px 20px 16px;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="printAbhaCard()" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;"><i class="fa fa-print"></i> Print Card</button>
            <button onclick="closeAbhaCard()" style="background:#f1f5f9;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<script>
function showAbhaCard(btn) {
    var p = JSON.parse(btn.getAttribute('data-profile'));
    document.getElementById('cardName').textContent     = p.name || '—';
    document.getElementById('cardAbha').textContent     = p.abha_address || '';
    document.getElementById('cardAbhaLarge').textContent = formatAbhaNum(p.abha_number);
    document.getElementById('cardDob').textContent      = p.dob || '—';
    document.getElementById('cardGender').textContent   = genderLabel(p.gender);
    document.getElementById('cardMobile').innerHTML     = p.mobile ? '<strong>Mobile:</strong> ' + p.mobile : '';
    var photoEl = document.getElementById('cardPhoto');
    if (p.photo) {
        photoEl.innerHTML = '<img src="data:image/jpeg;base64,' + p.photo + '" style="width:64px;height:64px;object-fit:cover;border-radius:50%;">';
    } else {
        photoEl.innerHTML = '<span style="font-size:28px;">👤</span>';
    }
    var modal = document.getElementById('abhaCardModal');
    modal.style.display = 'flex';
}
function closeAbhaCard() { document.getElementById('abhaCardModal').style.display = 'none'; }
function printAbhaCard() {
    var cardHtml = document.getElementById('abhaCardInner').innerHTML;
    var w = window.open('', '_blank', 'width=520,height=480');
    w.document.write('<html><head><title>ABHA Card</title><style>body{font-family:sans-serif;margin:20px;}@media print{body{margin:0;}}</style></head><body>' + cardHtml + '<br><button onclick="window.print()" style="margin-top:10px;padding:8px 20px;background:#1d4ed8;color:#fff;border:none;border-radius:5px;cursor:pointer;">Print</button></body></html>');
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
