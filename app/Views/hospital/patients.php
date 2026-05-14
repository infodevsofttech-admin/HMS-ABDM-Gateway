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
            </tr></thead>
            <tbody>
            <?php $i = 1; foreach ($patients as $p): ?>
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
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /.hp-content -->

<?= $this->endSection() ?>
