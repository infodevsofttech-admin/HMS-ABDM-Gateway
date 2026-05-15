<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'HMS Credential Detail'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-server"></i> HMS Credential Detail</h3>
    </div>
    <div class="title_right">
        <a href="/admin/hms-access" class="btn btn-default btn-sm pull-right">
            <i class="fa fa-arrow-left"></i> Back to HMS Access
        </a>
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
    <!-- Left column: info panels -->
    <div class="col-md-4">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-hospital-o"></i> Hospital Information</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-bordered table-condensed">
                    <tr>
                        <th class="col-md-5">Hospital Name</th>
                        <td><?= esc((string) $credential->hospital_name) ?></td>
                    </tr>
                    <tr>
                        <th>HFR ID</th>
                        <td><?= esc((string) $credential->hfr_id) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-info-circle"></i> HMS Configuration</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-bordered table-condensed">
                    <tr>
                        <th class="col-md-5">HMS Name</th>
                        <td><?= esc((string) $credential->hms_name) ?></td>
                    </tr>
                    <tr>
                        <th>API Endpoint</th>
                        <td><code style="word-break:break-all;"><?= esc((string) $credential->hms_api_endpoint) ?></code></td>
                    </tr>
                    <tr>
                        <th>Auth Type</th>
                        <td><span class="label label-info"><?= esc((string) strtoupper($credential->hms_auth_type)) ?></span></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ((int) $credential->is_active === 1): ?>
                                <span class="label label-success">Active</span>
                            <?php else: ?>
                                <span class="label label-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Verified</th>
                        <td>
                            <?php if ((int) $credential->is_verified === 1): ?>
                                <span class="label label-success"><i class="fa fa-check"></i> Verified</span><br>
                                <small class="text-muted"><?= !empty($credential->last_verified_at) ? esc((string) $credential->last_verified_at) : 'N/A' ?></small>
                            <?php else: ?>
                                <span class="label label-warning"><i class="fa fa-times"></i> Not Verified</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Created</th>
                        <td><?= esc((string) $credential->created_at) ?></td>
                    </tr>
                    <tr>
                        <th>Last Updated</th>
                        <td><?= esc((string) $credential->updated_at) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-bolt"></i> Actions</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/hms-credential/<?= esc((string) $credential->id) ?>/test" style="display:inline;">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-plug"></i> Test Connection
                    </button>
                </form>
                &nbsp;
                <form method="post" action="/admin/hms-credential/<?= esc((string) $credential->id) ?>/delete" style="display:inline;"
                      onsubmit="return confirm('Are you sure you want to delete this credential?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">
                        <i class="fa fa-trash"></i> Delete Credential
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right column: update form -->
    <div class="col-md-8">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-edit"></i> Update Credential</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/hms-credential/<?= esc((string) $credential->id) ?>/update" class="form-horizontal">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="col-sm-3 control-label">API Endpoint</label>
                        <div class="col-sm-9">
                            <input type="text" name="hms_api_endpoint" class="form-control"
                                   value="<?= esc((string) $credential->hms_api_endpoint) ?>" placeholder="HMS API Endpoint" required>
                        </div>
                    </div>

                    <?php if ($credential->hms_auth_type === 'api_key'): ?>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">API Key</label>
                            <div class="col-sm-9">
                                <input type="password" name="hms_api_key" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    <?php elseif ($credential->hms_auth_type === 'basic'): ?>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">Password</label>
                            <div class="col-sm-9">
                                <input type="password" name="hms_password" class="form-control" placeholder="Leave blank to keep current">
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="col-sm-3 control-label">Status</label>
                        <div class="col-sm-9">
                            <select name="is_active" class="form-control">
                                <option value="1" <?= (int) $credential->is_active === 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= (int) $credential->is_active === 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-sm-offset-3 col-sm-9">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-save"></i> Update Credential
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
