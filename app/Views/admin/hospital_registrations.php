<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'Hospital Registrations'; ?>

<?= $this->section('content') ?>

<?php
$registrations = $registrations ?? [];
$filterStatus  = $filterStatus  ?? 'pending';
$pendingCount  = $pendingCount  ?? 0;

$statusClass = [
    'pending'  => 'label-warning',
    'approved' => 'label-success',
    'rejected' => 'label-danger',
];
?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-file-text-o"></i> Hospital Registrations
            <?php if ($pendingCount > 0): ?>
            <span class="label label-warning" style="font-size:13px;vertical-align:middle;margin-left:6px;"><?= (int)$pendingCount ?> Pending</span>
            <?php endif; ?>
        </h3>
    </div>
</div>
<div class="clearfix"></div>

<?php if (session()->getFlashdata('message')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<!-- Filter -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel" style="padding:10px 15px;">
            <div class="x_content" style="padding:0;">
                <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;">
                    <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $v=>$l): ?>
                    <a href="/admin/registrations?status=<?= $v ?>"
                       class="btn btn-sm <?= $filterStatus === $v ? 'btn-primary' : 'btn-default' ?>">
                        <?= $l ?>
                        <?php if ($v === 'pending' && $pendingCount > 0): ?>
                        <span class="badge" style="background:#e74c3c;"><?= (int)$pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-list"></i> <?= ucfirst($filterStatus) ?> Registrations
                    <small>(<?= count($registrations) ?>)</small>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="padding:0;">
                <?php if (empty($registrations)): ?>
                <div style="text-align:center;padding:40px;color:#777;">
                    <i class="fa fa-inbox" style="font-size:36px;display:block;margin-bottom:10px;color:#ccc;"></i>
                    No <?= $filterStatus ?> registrations.
                </div>
                <?php else: ?>
                <table class="table table-striped table-bordered table-hover" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Hospital</th>
                            <th>Contact</th>
                            <th>State / City</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($registrations as $r): ?>
                    <tr>
                        <td><?= (int)$r->id ?></td>
                        <td>
                            <strong><?= esc($r->hospital_name) ?></strong>
                            <?php if ($r->hfr_id): ?>
                            <br><small style="color:#7f8c8d;">HFR: <?= esc($r->hfr_id) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= esc($r->contact_name) ?><br>
                            <small><i class="fa fa-envelope-o"></i> <?= esc($r->contact_email) ?></small><br>
                            <small><i class="fa fa-phone"></i> <?= esc($r->contact_phone) ?></small>
                        </td>
                        <td>
                            <?= esc($r->state ?? '—') ?><br>
                            <small style="color:#7f8c8d;"><?= esc($r->city ?? '') ?></small>
                        </td>
                        <td><code><?= esc($r->desired_username) ?></code></td>
                        <td>
                            <span class="label <?= $statusClass[$r->status] ?? 'label-default' ?>">
                                <?= ucfirst($r->status) ?>
                            </span>
                            <?php if ($r->admin_notes): ?>
                            <br><small style="color:#7f8c8d;" title="<?= esc($r->admin_notes) ?>">
                                <i class="fa fa-comment-o"></i> Has notes
                            </small>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:11px;color:#7f8c8d;"><?= esc((string)($r->created_at ?? '')) ?></td>
                        <td>
                            <button type="button" class="btn btn-xs btn-info"
                                    onclick="showDetail(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                                <i class="fa fa-eye"></i> View
                            </button>
                            <?php if ($r->status === 'pending'): ?>
                            <button type="button" class="btn btn-xs btn-success"
                                    onclick="showApprove(<?= (int)$r->id ?>, '<?= esc($r->hospital_name) ?>')">
                                <i class="fa fa-check"></i> Approve
                            </button>
                            <button type="button" class="btn btn-xs btn-danger"
                                    onclick="showReject(<?= (int)$r->id ?>, '<?= esc($r->hospital_name) ?>')">
                                <i class="fa fa-times"></i> Reject
                            </button>
                            <?php endif; ?>
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

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-file-text-o"></i> Registration Details</h4>
            </div>
            <div class="modal-body" id="detailBody"></div>
            <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">Close</button></div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#27ae60;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-check"></i> Approve Registration</h4>
            </div>
            <form id="approveForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p id="approveMsg"></p>
                    <div class="form-group">
                        <label>Admin Notes (optional, sent to hospital via email)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="Welcome message or any notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success"><i class="fa fa-check"></i> Confirm Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#e74c3c;color:#fff;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;">&times;</button>
                <h4 class="modal-title"><i class="fa fa-times"></i> Reject Registration</h4>
            </div>
            <form id="rejectForm" method="post">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <p id="rejectMsg"></p>
                    <div class="form-group">
                        <label>Reason for Rejection (recommended, sent to hospital)</label>
                        <textarea name="admin_notes" class="form-control" rows="3" placeholder="e.g. Incomplete information, duplicate application..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger"><i class="fa fa-times"></i> Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function showDetail(r) {
    var html = '<table class="table table-bordered" style="font-size:13px;">';
    var fields = [
        ['Hospital Name', r.hospital_name], ['HFR ID', r.hfr_id||'—'],
        ['State', r.state||'—'], ['City', r.city||'—'],
        ['Contact Name', r.contact_name], ['Email', r.contact_email],
        ['Phone', r.contact_phone], ['Username', r.desired_username],
        ['Status', r.status], ['Submitted', r.created_at],
        ['Reviewed By', r.reviewed_by||'—'], ['Reviewed At', r.reviewed_at||'—'],
        ['Admin Notes', r.admin_notes||'—'],
    ];
    fields.forEach(function(f){
        html += '<tr><td style="font-weight:700;width:35%;color:#555;">'+escH(f[0])+'</td><td>'+escH(f[1]||'')+'</td></tr>';
    });
    if (r.description) {
        html += '<tr><td style="font-weight:700;color:#555;">Description</td><td>'+escH(r.description)+'</td></tr>';
    }
    html += '</table>';
    document.getElementById('detailBody').innerHTML = html;
    $('#detailModal').modal('show');
}
function showApprove(id, name) {
    document.getElementById('approveMsg').innerHTML = 'Approve registration for <strong>'+escH(name)+'</strong>? This will create the hospital account and send an email notification.';
    document.getElementById('approveForm').action = '/admin/registrations/'+id+'/approve';
    $('#approveModal').modal('show');
}
function showReject(id, name) {
    document.getElementById('rejectMsg').innerHTML = 'Reject registration for <strong>'+escH(name)+'</strong>? A rejection notification will be sent by email.';
    document.getElementById('rejectForm').action = '/admin/registrations/'+id+'/reject';
    $('#rejectModal').modal('show');
}
function escH(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<?= $this->endSection() ?>
