<?= $this->extend('layout/admin_layout') ?>
<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-user-md"></i> HPR Professionals</h3>
    </div>
</div>
<div class="clearfix"></div>

<?php if ($message = ($message ?? null)): ?>
<div class="alert alert-success alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <i class="fa fa-check-circle"></i> <?= esc($message) ?>
</div>
<?php endif; ?>
<?php if ($error = ($error ?? null)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    <i class="fa fa-exclamation-circle"></i> <?= esc($error) ?>
</div>
<?php endif; ?>

<!-- Info Banner -->
<div class="alert alert-info" style="margin-bottom:20px;">
    <i class="fa fa-info-circle"></i>
    <strong>About HPR IDs:</strong>
    HPR (Health Professional Registry) is a separate ABDM registry for doctors, nurses, and allied health professionals.
    HPR IDs follow the format <code>name@hpr.abdm</code> (sandbox) or a 14-digit number.
    Professionals must register at <a href="https://nhpr.abdm.gov.in" target="_blank" rel="noopener">nhpr.abdm.gov.in</a>.
    <br><em>Note: HPR search/verify API is not included in ABDM M1 — HPR IDs are stored here for reference and used in OPD scan &amp; share flows.</em>
</div>

<!-- Hospital Selector -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_content">
                <form method="get" action="/admin/hpr-professionals" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <label style="margin:0;font-weight:600;">Select Hospital:</label>
                    <select name="hospital_id" onchange="this.form.submit()" style="padding:8px 12px;border:1px solid #d1d5db;border-radius:4px;min-width:260px;">
                        <option value="">— Choose a hospital —</option>
                        <?php foreach ($hospitals as $h): ?>
                            <option value="<?= esc((string) $h->id) ?>" <?= ((int)($hospitalId ?? 0) === (int)$h->id) ? 'selected' : '' ?>>
                                <?= esc((string) $h->hospital_name) ?> (<?= esc((string) $h->hfr_id) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($hospital !== null): ?>

<!-- Add Professional Form -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-plus-circle"></i> Add HPR Professional — <?= esc((string) $hospital->hospital_name) ?></h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/hpr-professionals/create">
                    <?= csrf_field() ?>
                    <input type="hidden" name="hospital_id" value="<?= esc((string) $hospital->id) ?>">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Full Name <span style="color:red">*</span></label>
                                <input type="text" name="name" class="form-control" placeholder="Dr. Rajesh Kumar" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>HPR ID <span style="color:red">*</span></label>
                                <input type="text" name="hpr_id" class="form-control" placeholder="rajesh.kumar@hpr.abdm" required>
                                <small class="text-muted">Format: <code>name@hpr.abdm</code> or 14-digit HPR number</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Designation</label>
                                <input type="text" name="designation" class="form-control" placeholder="Senior Physician">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Specialization</label>
                                <input type="text" name="specialization" class="form-control" placeholder="General Medicine">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department" class="form-control" placeholder="OPD">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Medical Council Reg. Number</label>
                                <input type="text" name="registration_number" class="form-control" placeholder="MCI-12345">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary"><i class="fa fa-plus"></i> Add Professional</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Professionals List -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-list"></i> Registered HPR Professionals
                    <small class="text-muted">(<?= count($professionals) ?> total)</small>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <?php if (empty($professionals)): ?>
                    <div class="alert alert-warning">
                        No HPR professionals registered for this hospital yet.
                    </div>
                <?php else: ?>
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>HPR ID</th>
                                <th>Designation</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Reg. No.</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($professionals as $p): ?>
                                <tr>
                                    <td><?= esc((string) $p['id']) ?></td>
                                    <td><strong><?= esc((string) $p['name']) ?></strong></td>
                                    <td>
                                        <code><?= esc((string) $p['hpr_id']) ?></code>
                                        <a href="https://nhpr.abdm.gov.in" target="_blank" rel="noopener" title="Verify on HPR Portal"
                                           style="margin-left:6px;color:#7f8c8d;font-size:11px;">
                                            <i class="fa fa-external-link"></i>
                                        </a>
                                    </td>
                                    <td><?= esc((string) ($p['designation'] ?? '—')) ?></td>
                                    <td><?= esc((string) ($p['specialization'] ?? '—')) ?></td>
                                    <td><?= esc((string) ($p['department'] ?? '—')) ?></td>
                                    <td><?= esc((string) ($p['registration_number'] ?? '—')) ?></td>
                                    <td>
                                        <?php if ($p['is_active']): ?>
                                            <span class="label label-success">Active</span>
                                        <?php else: ?>
                                            <span class="label label-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <form method="post" action="/admin/hpr-professionals/<?= esc((string) $p['id']) ?>/toggle" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs <?= $p['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                                <?= $p['is_active'] ? 'Deactivate' : 'Activate' ?>
                                            </button>
                                        </form>
                                        &nbsp;
                                        <form method="post" action="/admin/hpr-professionals/<?= esc((string) $p['id']) ?>/delete" style="display:inline;"
                                              onsubmit="return confirm('Remove <?= esc(addslashes((string) $p['name'])) ?>?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-danger">
                                                <i class="fa fa-trash"></i> Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info"><i class="fa fa-arrow-up"></i> Select a hospital above to manage its HPR professionals.</div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
