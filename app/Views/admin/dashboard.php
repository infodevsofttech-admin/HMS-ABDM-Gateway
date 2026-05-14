<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'Dashboard'; ?>

<?= $this->section('content') ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-home"></i> Dashboard</h3>
    </div>
</div>
<div class="clearfix"></div>

<div class="row tile_count">
    <div class="col-md-2 col-sm-4 tile_stats_count">
        <span class="count_top"><i class="fa fa-hospital-o"></i> Hospitals</span>
        <div class="count"><?= esc((string) $hospitalCount) ?></div>
        <span class="count_bottom"><a href="/admin/hospitals">View all</a></span>
    </div>
    <div class="col-md-2 col-sm-4 tile_stats_count">
        <span class="count_top"><i class="fa fa-users"></i> Hospital Users</span>
        <div class="count green"><?= esc((string) $userCount) ?></div>
        <span class="count_bottom"><a href="/admin/users">View all</a></span>
    </div>
    <div class="col-md-2 col-sm-4 tile_stats_count">
        <span class="count_top"><i class="fa fa-list-alt"></i> Request Logs</span>
        <div class="count"><?= esc((string) $requestLogCount) ?></div>
        <span class="count_bottom"><a href="/admin/logs">View all</a></span>
    </div>
    <div class="col-md-2 col-sm-4 tile_stats_count">
        <span class="count_top"><i class="fa fa-shield"></i> Audit Logs</span>
        <div class="count purple"><?= esc((string) $auditCount) ?></div>
        <span class="count_bottom"><a href="/admin/audit">View all</a></span>
    </div>
    <div class="col-md-2 col-sm-4 tile_stats_count">
        <span class="count_top"><i class="fa fa-flask"></i> Test Submissions</span>
        <div class="count blue"><?= esc((string) $testLogCount) ?></div>
        <span class="count_bottom"><a href="/admin/test-logs">View all</a></span>
    </div>
    <div class="col-md-2 col-sm-4 tile_stats_count">
        <span class="count_top"><i class="fa fa-heartbeat"></i> M1 Suite</span>
        <div class="count red">&nbsp;</div>
        <span class="count_bottom"><a href="/admin/m1">Open M1</a></span>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-hospital-o"></i> Registered Hospitals</h2>
                <ul class="nav navbar-right panel_toolbox">
                    <li><a href="/admin/hospitals" class="btn btn-xs btn-primary">View All</a></li>
                </ul>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hospital Name</th>
                            <th>HFR ID</th>
                            <th>Mode</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hospitals)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No hospitals registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($hospitals as $h): ?>
                            <tr>
                                <td><?= esc((string) $h->id) ?></td>
                                <td><strong><?= esc((string) $h->hospital_name) ?></strong></td>
                                <td><code><?= esc((string) $h->hfr_id) ?></code></td>
                                <td>
                                    <span class="label <?= $h->gateway_mode === 'live' ? 'label-danger' : 'label-primary' ?>">
                                        <?= strtoupper((string) $h->gateway_mode) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h->is_active): ?>
                                        <span class="label label-success">Active</span>
                                    <?php else: ?>
                                        <span class="label label-default">Inactive</span>
                                    <?php endif; ?>
                                </td>
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
