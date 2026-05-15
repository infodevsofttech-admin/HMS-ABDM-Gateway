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

<!-- ABHA Card Modal -->
<div id="abhaCardModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:0;max-width:440px;width:95%;box-shadow:0 8px 32px rgba(0,0,0,0.28);overflow:hidden;">
        <!-- Modal Header -->
        <div style="background:#1d4ed8;padding:14px 18px;display:flex;align-items:center;justify-content:space-between;">
            <span style="color:#fff;font-weight:700;font-size:15px;"><i class="fas fa-id-card" style="margin-right:7px;"></i>ABHA Health Card</span>
            <button onclick="closeAbhaCard()" style="background:none;border:none;color:#fff;font-size:20px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <!-- Card Body -->
        <div style="padding:18px 20px 10px;">
            <div id="abhaCardInner" style="border:1.5px solid #e5e7eb;border-radius:10px;padding:16px 18px;background:#f0f4ff;position:relative;">
                <!-- ABDM Logo bar -->
                <div style="display:flex;align-items:center;margin-bottom:12px;">
                    <div style="background:#1d4ed8;color:#fff;font-weight:900;font-size:13px;padding:4px 10px;border-radius:5px;letter-spacing:.05em;">ABDM</div>
                    <span style="margin-left:8px;font-size:11px;color:#6b7280;font-weight:600;">Ayushman Bharat Digital Mission</span>
                </div>
                <div style="display:flex;gap:14px;align-items:flex-start;">
                    <!-- Photo -->
                    <div id="cardPhoto" style="width:64px;height:64px;border-radius:50%;background:#c7d2fe;overflow:hidden;flex-shrink:0;display:flex;align-items:center;justify-content:center;border:2px solid #1d4ed8;">
                        <span style="font-size:28px;">👤</span>
                    </div>
                    <!-- Info -->
                    <div style="flex:1;min-width:0;">
                        <div id="cardName" style="font-weight:800;font-size:16px;color:#1e293b;margin-bottom:3px;"></div>
                        <div id="cardAbha" style="font-size:13px;font-weight:700;color:#1d4ed8;letter-spacing:.04em;margin-bottom:5px;"></div>
                        <div style="display:flex;gap:16px;flex-wrap:wrap;">
                            <span style="font-size:12px;color:#374151;"><strong>DOB:</strong> <span id="cardDob"></span></span>
                            <span style="font-size:12px;color:#374151;"><strong>Gender:</strong> <span id="cardGender"></span></span>
                        </div>
                        <div id="cardMobile" style="font-size:12px;color:#374151;margin-top:3px;"></div>
                        <div id="cardAddress" style="font-size:11px;color:#6b7280;margin-top:4px;word-break:break-all;"></div>
                    </div>
                </div>
                <!-- ABHA Number large display -->
                <div style="margin-top:14px;background:#fff;border-radius:7px;padding:10px 14px;text-align:center;border:1px dashed #93c5fd;">
                    <div style="font-size:11px;color:#6b7280;margin-bottom:3px;">ABHA Number</div>
                    <div id="cardAbhaLarge" style="font-size:19px;font-weight:900;color:#1d4ed8;letter-spacing:.08em;"></div>
                </div>
            </div>
        </div>
        <!-- Actions -->
        <div style="padding:10px 20px 16px;display:flex;gap:10px;justify-content:flex-end;">
            <button onclick="printAbhaCard()" style="background:#16a34a;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:13px;font-weight:600;cursor:pointer;"><i class="fas fa-print"></i> Print Card</button>
            <button onclick="closeAbhaCard()" style="background:#f1f5f9;color:#374151;border:1px solid #d1d5db;border-radius:6px;padding:8px 16px;font-size:13px;cursor:pointer;">Close</button>
        </div>
    </div>
</div>

<script>
function showAbhaCard(btn) {
    var p = JSON.parse(btn.getAttribute('data-profile'));
    document.getElementById('cardName').textContent    = p.name || '—';
    document.getElementById('cardAbha').textContent    = p.abha_address || '';
    document.getElementById('cardAbhaLarge').textContent = formatAbhaNum(p.abha_number);
    document.getElementById('cardDob').textContent     = p.dob || '—';
    document.getElementById('cardGender').textContent  = genderLabel(p.gender);
    document.getElementById('cardMobile').innerHTML    = p.mobile ? '<strong>Mobile:</strong> ' + p.mobile : '';
    document.getElementById('cardAddress').textContent = p.abha_address || '';
    var photoEl = document.getElementById('cardPhoto');
    if (p.photo) {
        photoEl.innerHTML = '<img src="data:image/jpeg;base64,' + p.photo + '" style="width:64px;height:64px;object-fit:cover;border-radius:50%;">';
    } else {
        photoEl.innerHTML = '<span style="font-size:28px;">👤</span>';
    }
    var modal = document.getElementById('abhaCardModal');
    modal.style.display = 'flex';
}
function closeAbhaCard() {
    document.getElementById('abhaCardModal').style.display = 'none';
}
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
    if (u === 'M') return 'Male';
    if (u === 'F') return 'Female';
    if (u === 'O') return 'Other';
    return g;
}
document.getElementById('abhaCardModal').addEventListener('click', function(e) {
    if (e.target === this) closeAbhaCard();
});
</script>

<?= $this->endSection() ?>
