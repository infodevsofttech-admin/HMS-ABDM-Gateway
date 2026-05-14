<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'Ticket ' . esc($ticket->ticket_number ?? ''); ?>

<?= $this->section('content') ?>

<?php
$ticket   = $ticket ?? null;
$messages = $messages ?? [];

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
        <h3>
            <i class="fa fa-ticket"></i>
            <?= esc($ticket->ticket_number ?? '') ?>
            <span class="label <?= $statusColors[$ticket->status ?? 'open'] ?? 'label-info' ?>" style="font-size:13px;margin-left:8px;">
                <?= esc(str_replace('_',' ', ucfirst($ticket->status ?? ''))) ?>
            </span>
        </h3>
    </div>
    <div class="title_right">
        <a href="/admin/support" class="btn btn-sm btn-default pull-right">
            <i class="fa fa-arrow-left"></i> All Tickets
        </a>
    </div>
</div>
<div class="clearfix"></div>

<div class="row">

    <!-- Message Thread (left) -->
    <div class="col-md-8">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-comments"></i> <?= esc($ticket->subject ?? '') ?></h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="padding:0;">

                <?php foreach ($messages as $msg): ?>
                <?php $isAdmin = ($msg->sender_type === 'admin'); ?>
                <div style="display:flex;gap:14px;padding:18px 24px;border-bottom:1px solid #f0f3f8;<?= $isAdmin ? 'background:#f0f7ff;' : '' ?>">
                    <!-- Avatar -->
                    <div style="flex-shrink:0;">
                        <div style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;color:#fff;
                            <?= $isAdmin ? 'background:#1ABB9C;' : 'background:#2A3F54;' ?>">
                            <?= strtoupper(substr((string)($msg->sender_name ?? 'A'), 0, 1)) ?>
                        </div>
                    </div>
                    <!-- Content -->
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <strong style="font-size:13px;"><?= esc((string)($msg->sender_name ?? 'Unknown')) ?></strong>
                            <?php if ($isAdmin): ?>
                            <span class="label label-success" style="font-size:10px;">Admin</span>
                            <?php else: ?>
                            <span class="label label-info" style="font-size:10px;">Hospital</span>
                            <?php endif; ?>
                            <span style="font-size:11px;color:#999;margin-left:auto;"><?= esc((string)($msg->created_at ?? '')) ?></span>
                        </div>
                        <div style="font-size:13px;color:#444;line-height:1.6;white-space:pre-wrap;"><?= esc((string)($msg->message ?? '')) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($messages)): ?>
                <div style="text-align:center;padding:40px;color:#777;">No messages yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply + Status Update Form -->
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-reply"></i> Reply &amp; Update Status</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/support/<?= (int)$ticket->id ?>/reply">
                    <?= csrf_field() ?>

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;">Reply Message</label>
                        <textarea name="message" class="form-control" rows="5"
                                  placeholder="Type your reply to the hospital..." style="font-size:13px;resize:vertical;"></textarea>
                        <span class="help-block" style="font-size:11px;">Leave blank to only change status.</span>
                    </div>

                    <div class="form-group">
                        <label style="font-size:13px;font-weight:600;">Update Status</label>
                        <select name="status" class="form-control" style="max-width:220px;font-size:13px;">
                            <option value="">— Keep Current —</option>
                            <?php foreach (['open'=>'Open','in_progress'=>'In Progress','resolved'=>'Resolved','closed'=>'Closed'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($ticket->status ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-paper-plane"></i> Send Reply / Update
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Ticket Info Sidebar (right) -->
    <div class="col-md-4">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-info-circle"></i> Ticket Info</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="padding:0;">
                <?php
                $details = [
                    ['Ticket #',   $ticket->ticket_number ?? ''],
                    ['Hospital',   $ticket->hospital_name ?? '—'],
                    ['Category',   ucfirst($ticket->category ?? '')],
                    ['Priority',   ucfirst($ticket->priority ?? '')],
                    ['Status',     str_replace('_',' ',ucfirst($ticket->status ?? ''))],
                    ['Messages',   (string)($ticket->message_count ?? 0)],
                    ['Last By',    $ticket->last_reply_by ?? '—'],
                    ['Created',    $ticket->created_at ?? ''],
                    ['Last Reply', $ticket->last_reply_at ?? '—'],
                ];
                foreach ($details as [$label, $val]):
                ?>
                <div style="display:flex;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #f0f3f8;font-size:13px;">
                    <span style="color:#777;font-weight:500;"><?= esc($label) ?></span>
                    <span style="color:#333;font-weight:600;text-align:right;"><?= esc((string)$val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
