<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'HMS Access Management'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-server"></i> HMS Access Management</h3>
    </div>
</div>
<div class="clearfix"></div>

<?php if (!empty($message)): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        <i class="fa fa-check-circle"></i> <?= esc($message) ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        <i class="fa fa-exclamation-circle"></i> <?= esc($error) ?>
    </div>
<?php endif; ?>

<?php if (!empty($generated_key)): ?>
    <div class="alert alert-warning" role="alert">
        <h4><i class="fa fa-key"></i> &nbsp;Generated API Key — Copy Now</h4>
        <p>This key will <strong>not be shown again</strong>. Configure it in your HMS system.</p>
        <div style="display:flex;gap:8px;align-items:center;">
            <input type="text" id="generatedKeyBox" class="form-control"
                   value="<?= esc($generated_key) ?>" readonly
                   style="font-family:monospace;font-size:13px;background:#fffde7;">
            <button type="button" class="btn btn-warning btn-sm" onclick="copyKey()">
                <i class="fa fa-copy"></i> Copy
            </button>
        </div>
        <p class="help-block" style="margin-top:6px;">
            <strong>Gateway API Endpoint:</strong> <code>https://abdm-bridge.e-atria.in/api</code><br>
            Use this key as the <code>Authorization: Bearer &lt;key&gt;</code> header in your HMS API calls.
        </p>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-plus-circle"></i> Add HMS Credential</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/hms-credential/create" class="form-horizontal">
                    <?= csrf_field() ?>

                    <?php if (!empty($selectedHospital)): ?>
                        <input type="hidden" name="hospital_id" value="<?= esc((string) $selectedHospital->id) ?>">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Hospital</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">
                                    <strong><?= esc((string) $selectedHospital->hospital_name) ?></strong>
                                    <span class="text-muted">(<?= esc((string) $selectedHospital->hfr_id) ?>)</span>
                                </p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">Hospital</label>
                            <div class="col-sm-4">
                                <select name="hospital_id" class="form-control" required>
                                    <option value="">-- Select Hospital --</option>
                                    <?php foreach ($hospitals as $hospital): ?>
                                        <option value="<?= esc((string) $hospital->id) ?>">
                                            <?= esc((string) $hospital->hospital_name) ?> (<?= esc((string) $hospital->hfr_id) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">HMS System Name <small class="text-muted">(optional)</small></label>
                        <div class="col-sm-4">
                            <input type="text" name="hms_name" class="form-control" placeholder="Auto-filled from hospital name if blank">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">API Endpoint</label>
                        <div class="col-sm-6">
                            <p class="form-control-static">
                                <code>https://abdm-bridge.e-atria.in/api</code>
                                <span class="text-muted" style="font-size:12px;"> — fixed for all HMS systems</span>
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-sm-2 control-label">API Key</label>
                        <div class="col-sm-6">
                            <p class="form-control-static text-muted">
                                <i class="fa fa-magic"></i> Auto-generated securely on submit. Shown once for you to copy.
                            </p>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-2 col-sm-10">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-plus-circle"></i> Create &amp; Generate API Key
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-list"></i> HMS Credentials List</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>Hospital</th>
                            <th>HMS Name</th>
                            <th>Auth Type</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($credentials)): ?>
                            <?php foreach ($credentials as $cred): ?>
                                <tr>
                                    <td>
                                        <strong><?= esc((string) $cred->hospital_name) ?></strong><br>
                                        <small class="text-muted"><?= esc((string) $cred->hfr_id) ?></small>
                                    </td>
                                    <td><?= esc((string) $cred->hms_name) ?></td>
                                    <td><span class="label label-info"><?= esc((string) strtoupper($cred->hms_auth_type)) ?></span></td>
                                    <td>
                                        <?php if ((int) $cred->is_active === 1): ?>
                                            <span class="label label-success">Active</span>
                                        <?php else: ?>
                                            <span class="label label-danger">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int) $cred->is_verified === 1): ?>
                                            <span class="label label-success"><i class="fa fa-check"></i> Verified</span><br>
                                            <small class="text-muted"><?= !empty($cred->last_verified_at) ? esc((string) $cred->last_verified_at) : 'N/A' ?></small>
                                        <?php else: ?>
                                            <span class="label label-warning"><i class="fa fa-times"></i> Not Verified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/hms-credential/<?= esc((string) $cred->id) ?>" class="btn btn-xs btn-default">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        <form method="post" action="/admin/hms-credential/<?= esc((string) $cred->id) ?>/test" style="display:inline;">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-xs btn-success">
                                                <i class="fa fa-plug"></i> Test
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No HMS credentials found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function copyKey() {
    var box = document.getElementById('generatedKeyBox');
    box.select();
    box.setSelectionRange(0, 99999);
    document.execCommand('copy');
    alert('API Key copied to clipboard!');
}
</script>
<?= $this->endSection() ?>
