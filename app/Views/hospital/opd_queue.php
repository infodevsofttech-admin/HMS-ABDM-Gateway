<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'OPD Queue'; ?>

<?= $this->section('content') ?>

<?php
$tokens  = $tokens ?? [];
$date    = $date ?? date('Y-m-d');
$message = $message ?? null;
$error   = $error ?? null;
$statusColors = ['PENDING'=>'hb-yellow','CALLED'=>'hb-blue','COMPLETED'=>'hb-green','CANCELLED'=>'hb-red'];
?>

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-list-ol"></i> OPD Token Queue</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item active">OPD Queue</li>
        </ol></nav>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
        <form method="GET" action="/portal/opd-queue" style="display:flex;gap:8px;margin:0;">
            <input type="date" name="date" value="<?= esc($date) ?>" class="form-control form-control-sm" style="height:34px;">
            <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
        </form>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTokenModal">
            <i class="fas fa-plus mr-1"></i> New Token
        </button>
    </div>
</div>

<div class="hp-content">

<?php if ($message): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle mr-2"></i><?= esc((string)$message) ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle mr-2"></i><?= esc((string)$error) ?><button type="button" class="close" data-dismiss="alert"><span>&times;</span></button></div>
<?php endif; ?>

<!-- Summary badges -->
<?php
$pending   = count(array_filter($tokens, fn($t) => ($t->status ?? '') === 'PENDING'));
$called    = count(array_filter($tokens, fn($t) => ($t->status ?? '') === 'CALLED'));
$completed = count(array_filter($tokens, fn($t) => ($t->status ?? '') === 'COMPLETED'));
$cancelled = count(array_filter($tokens, fn($t) => ($t->status ?? '') === 'CANCELLED'));
?>
<div class="row mb-3">
    <div class="col-6 col-md-3 mb-2"><div class="hp-card" style="margin:0;"><div class="hp-card-body" style="padding:12px 16px;text-align:center;"><div style="font-size:22px;font-weight:800;color:#713f12;"><?= count($tokens) ?></div><div style="font-size:11px;color:#6c757d;font-weight:600;">Total Today</div></div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="hp-card" style="margin:0;"><div class="hp-card-body" style="padding:12px 16px;text-align:center;"><div style="font-size:22px;font-weight:800;color:#d97706;"><?= $pending ?></div><div style="font-size:11px;color:#6c757d;font-weight:600;">Pending</div></div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="hp-card" style="margin:0;"><div class="hp-card-body" style="padding:12px 16px;text-align:center;"><div style="font-size:22px;font-weight:800;color:#065f46;"><?= $completed ?></div><div style="font-size:11px;color:#6c757d;font-weight:600;">Completed</div></div></div></div>
    <div class="col-6 col-md-3 mb-2"><div class="hp-card" style="margin:0;"><div class="hp-card-body" style="padding:12px 16px;text-align:center;"><div style="font-size:22px;font-weight:800;color:#991b1b;"><?= $cancelled ?></div><div style="font-size:11px;color:#6c757d;font-weight:600;">Cancelled</div></div></div></div>
</div>

<!-- Token table -->
<div class="hp-card">
    <div class="hp-card-head">
        <i class="fas fa-calendar-day"></i> <?= date('d M Y', strtotime($date)) ?> — <?= count($tokens) ?> token(s)
    </div>
    <?php if (empty($tokens)): ?>
    <div class="hp-card-body" style="text-align:center;padding:50px 20px;color:#6c757d;">
        <i class="fas fa-inbox" style="font-size:36px;color:#dee2e6;display:block;margin-bottom:10px;"></i>
        No tokens for this date.
        <button class="btn btn-primary btn-sm mt-3 d-block mx-auto" data-toggle="modal" data-target="#addTokenModal">Create First Token</button>
    </div>
    <?php else: ?>
    <table class="hp-tbl">
        <thead><tr>
            <th style="width:70px;">Token#</th>
            <th>Patient Name</th>
            <th>Mobile</th>
            <th>ABHA Number</th>
            <th>Department</th>
            <th>Status</th>
            <th style="width:160px;">Action</th>
        </tr></thead>
        <tbody>
        <?php foreach ($tokens as $t): ?>
        <tr>
            <td style="font-size:20px;font-weight:800;color:var(--hp-primary);"><?= (int)($t->token_number ?? 0) ?></td>
            <td style="font-weight:600;"><?= esc((string)($t->patient_name ?? '—')) ?></td>
            <td><?= esc((string)($t->phone ?? '—')) ?></td>
            <td style="font-size:12px;"><?= esc((string)($t->abha_number ?? '—')) ?></td>
            <td><?= esc((string)($t->context ?? 'General OPD')) ?></td>
            <td><span class="hb <?= $statusColors[$t->status ?? 'PENDING'] ?? 'hb-yellow' ?>"><?= esc((string)($t->status ?? 'PENDING')) ?></span></td>
            <td>
                <form method="POST" action="/portal/opd-queue/status" style="display:flex;gap:4px;align-items:center;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="token_id" value="<?= (int)($t->id ?? 0) ?>">
                    <select name="status" class="form-control form-control-sm" style="height:28px;font-size:12px;padding:2px 4px;">
                        <option value="PENDING" <?= ($t->status ?? '') === 'PENDING' ? 'selected' : '' ?>>Pending</option>
                        <option value="CALLED" <?= ($t->status ?? '') === 'CALLED' ? 'selected' : '' ?>>Called</option>
                        <option value="COMPLETED" <?= ($t->status ?? '') === 'COMPLETED' ? 'selected' : '' ?>>Completed</option>
                        <option value="CANCELLED" <?= ($t->status ?? '') === 'CANCELLED' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" style="height:28px;padding:2px 8px;font-size:12px;">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</div><!-- /.hp-content -->

<!-- Add Token Modal -->
<div class="modal fade" id="addTokenModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--hp-primary);color:#fff;">
                <h5 class="modal-title"><i class="fas fa-plus mr-2"></i>New OPD Token</h5>
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;"><span>&times;</span></button>
            </div>
            <form method="POST" action="/portal/opd-queue/add">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;">Patient Name <span class="text-danger">*</span></label>
                        <input type="text" name="patient_name" class="form-control" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Mobile</label>
                                <input type="text" name="phone" class="form-control" maxlength="15">
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label style="font-size:13px;font-weight:600;">Gender</label>
                                <select name="gender" class="form-control">
                                    <option value="">—</option>
                                    <option value="M">Male</option>
                                    <option value="F">Female</option>
                                    <option value="O">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;">ABHA Number (optional)</label>
                        <input type="text" name="abha_number" class="form-control" placeholder="91-XXXX-XXXX-XXXX">
                    </div>
                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;">Department</label>
                        <select name="department" class="form-control">
                            <option value="General OPD">General OPD</option>
                            <option value="Cardiology">Cardiology</option>
                            <option value="Orthopaedics">Orthopaedics</option>
                            <option value="Gynaecology">Gynaecology</option>
                            <option value="Paediatrics">Paediatrics</option>
                            <option value="ENT">ENT</option>
                            <option value="Ophthalmology">Ophthalmology</option>
                            <option value="Dermatology">Dermatology</option>
                            <option value="Neurology">Neurology</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i> Create Token</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
