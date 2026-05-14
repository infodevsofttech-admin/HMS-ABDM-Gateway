<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'Hospital Users'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-users"></i> Hospital Users</h3>
    </div>
</div>
<div class="clearfix"></div>

<div class="row">
    <!-- Create User Form -->
    <div class="col-md-4">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-user-plus"></i> Create User</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/users/create" class="form-horizontal">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Hospital</label>
                        <div class="col-sm-8">
                            <select name="hospital_id" class="form-control" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($hospitals as $h): ?>
                                    <option value="<?= esc((string) $h->id) ?>">
                                        <?= esc((string) $h->hospital_name) ?> (<?= esc((string) $h->hfr_id) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Username</label>
                        <div class="col-sm-8">
                            <input type="text" name="username" class="form-control" placeholder="Username" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-4 control-label">Password</label>
                        <div class="col-sm-8">
                            <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-offset-4 col-sm-8">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fa fa-plus"></i> Create User &amp; API Token
                            </button>
                        </div>
                    </div>
                    <p class="text-muted" style="font-size:11px;text-align:center;">
                        The API token will be shown once after creation. Save it immediately.
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- User List -->
    <div class="col-md-8">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-list"></i> User List <small class="text-muted"><?= count($users) ?> users</small></h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Hospital</th>
                            <th>HFR ID</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="7" class="text-center text-muted">No users yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= esc((string) $u->id) ?></td>
                                <td><strong><i class="fa fa-user-o"></i> <?= esc((string) $u->username) ?></strong></td>
                                <td><?= esc((string) ($u->hospital_name ?? '—')) ?></td>
                                <td><code><?= esc((string) ($u->hfr_id ?? '—')) ?></code></td>
                                <td><span class="label label-info"><?= esc((string) $u->role) ?></span></td>
                                <td>
                                    <?php if ((int)$u->is_active === 1): ?>
                                        <span class="label label-success">Active</span>
                                    <?php else: ?>
                                        <span class="label label-default">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc(substr((string) $u->created_at, 0, 10)) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
