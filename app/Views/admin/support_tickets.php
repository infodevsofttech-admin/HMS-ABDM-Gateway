<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'Support Tickets'; ?>

<?= $this->section('content') ?>

<?php
$tickets        = $tickets ?? [];
$counts         = $counts  ?? ['open'=>0,'in_progress'=>0,'resolved'=>0,'closed'=>0];
$staleCount     = $staleCount ?? 0;
$filterStatus   = $filterStatus ?? '';
$filterPriority = $filterPriority ?? '';

$statusColors = [
    'open'        => 'label-info',
    'in_progress' => 'label-warning',
    'resolved'    => 'label-success',
    'closed'      => 'label-default',
];
$priorityColors = [
    'low'    => 'label-default',
    'medium' => 'label-warning',
    'high'   => 'label-danger',
];
?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-ticket"></i> Support Tickets</h3>
    </div>
    <div class="title_right">
        <div class="pull-right" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
            <span class="label label-info" style="font-size:13px;padding:5px 12px;">Open: <?= (int)$counts['open'] ?></span>
            <span class="label label-warning" style="font-size:13px;padding:5px 12px;">In Progress: <?= (int)$counts['in_progress'] ?></span>
            <span class="label label-success" style="font-size:13px;padding:5px 12px;">Resolved: <?= (int)$counts['resolved'] ?></span>
            <span class="label label-default" style="font-size:13px;padding:5px 12px;">Closed: <?= (int)$counts['closed'] ?></span>
            <?php if ($staleCount > 0): ?>
            <form method="post" action="/admin/support/close-stale"
                  onsubmit="return confirm('Close <?= (int)$staleCount ?> ticket(s) with no activity for 7+ days?')"
                  style="margin:0;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-warning btn-sm" style="font-size:12px;">
                    <i class="fa fa-clock-o"></i> Close Stale (<?= (int)$staleCount ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="clearfix"></div>

<!-- Filter Bar -->
<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_content">
                <form method="get" action="/admin/support" class="form-inline" style="gap:8px;display:flex;flex-wrap:wrap;align-items:center;">
                    <div class="form-group" style="margin-right:10px;">
                        <label style="margin-right:6px;font-size:13px;">Status:</label>
                        <select name="status" class="form-control input-sm" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach (['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $filterStatus === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="margin-right:6px;font-size:13px;">Priority:</label>
                        <select name="priority" class="form-control input-sm" onchange="this.form.submit()">
                            <option value="">All</option>
                            <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= $filterPriority === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($filterStatus !== '' || $filterPriority !== ''): ?>
                    <a href="/admin/support" class="btn btn-sm btn-default" style="margin-left:6px;">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-list"></i> All Tickets
                    <small style="font-size:13px;margin-left:8px;">(<?= count($tickets) ?> shown)</small>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <?php if (empty($tickets)): ?>
                <div style="text-align:center;padding:40px;color:#777;">
                    <i class="fa fa-inbox" style="font-size:40px;display:block;margin-bottom:12px;color:#ccc;"></i>
                    No tickets found.
                </div>
                <?php else: ?>
                <table class="table table-striped table-bordered table-hover" style="font-size:13px;">
                    <thead>
                        <tr>
                            <th>Ticket #</th>
                            <th>Hospital</th>
                            <th>Subject</th>
                            <th>Category</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Msgs</th>
                            <th>Last Activity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tickets as $t): ?>
                    <?php
                        $sl = $statusLabel[$t->status]   ?? ['label'=>$t->status,   'class'=>'hb-blue'];
                        $pl = $priorityLabel[$t->priority] ?? ['label'=>$t->priority, 'style'=>''];
                        $lastActivity = $t->last_reply_at ?? $t->created_at ?? null;
                        $isStale = !in_array($t->status, ['closed','resolved'], true)
                            && $lastActivity !== null
                            && strtotime($lastActivity) < strtotime('-7 days');
                    ?>
                    <tr <?= $isStale ? 'style="background:#fffbea;"' : '' ?>>
                        <td>
                            <code style="font-size:11px;"><?= esc($t->ticket_number) ?></code>
                            <?php if ($isStale): ?>
                            <span class="label label-warning" style="font-size:10px;" title="No activity for 7+ days"><i class="fa fa-clock-o"></i> Stale</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= esc((string)($t->hospital_name ?? '—')) ?>
                        </td>
                        <td style="max-width:220px;">
                            <a href="/admin/support/<?= (int)$t->id ?>" style="font-weight:600;">
                                <?= esc($t->subject) ?>
                            </a>
                        </td>
                        <td><?= esc(ucfirst($t->category)) ?></td>
                        <td><span class="label <?= $priorityColors[$t->priority] ?? 'label-default' ?>"><?= esc(ucfirst($t->priority)) ?></span></td>
                        <td><span class="label <?= $statusColors[$t->status] ?? 'label-default' ?>"><?= esc(str_replace('_',' ', ucfirst($t->status))) ?></span></td>
                        <td style="text-align:center;"><?= (int)$t->message_count ?></td>
                        <td style="font-size:11px;color:#777;"><?= esc((string)($t->last_reply_at ?? $t->created_at)) ?></td>
                        <td>
                            <a href="/admin/support/<?= (int)$t->id ?>" class="btn btn-xs btn-primary">
                                <i class="fa fa-eye"></i> View
                            </a>
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

<?= $this->endSection() ?>
