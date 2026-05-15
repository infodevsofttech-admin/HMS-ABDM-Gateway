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
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label">Hospital</label>
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
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label">HMS System Name</label>
                                <input type="text" name="hms_name" class="form-control" placeholder="e.g. Meddata HMS, Athenahealth" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="control-label">API Endpoint</label>
                                <input type="text" name="hms_api_endpoint" class="form-control" placeholder="https://hms.example.com/api" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="control-label">Auth Type</label>
                                <select name="hms_auth_type" id="authType" class="form-control" required onchange="toggleAuthFields()">
                                    <option value="api_key">API Key</option>
                                    <option value="basic">Basic Auth</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="apiKeyFields" class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label">API Key</label>
                                <input type="password" name="hms_api_key" id="apiKeyInput" class="form-control" placeholder="API Key">
                            </div>
                        </div>
                    </div>
                    <div id="basicAuthFields" class="row" style="display:none;">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label">Username</label>
                                <input type="text" name="hms_username" id="basicUsername" class="form-control" placeholder="Username">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="control-label">Password</label>
                                <input type="password" name="hms_password" id="basicPassword" class="form-control" placeholder="Password">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Add HMS Credential
                        </button>
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
    function toggleAuthFields() {
        var authType = document.getElementById('authType').value;
        document.getElementById('apiKeyFields').style.display = authType === 'api_key' ? 'block' : 'none';
        document.getElementById('basicAuthFields').style.display = authType === 'basic' ? 'block' : 'none';
    }
</script>
<?= $this->endSection() ?>
