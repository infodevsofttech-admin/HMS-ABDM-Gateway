<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'Support Ticket - ' . esc($ticket->ticket_number ?? ''); ?>

<?= $this->section('content') ?>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<?php
$ticket      = $ticket      ?? null;
$messages    = $messages    ?? [];
$attachments = $attachments ?? [];

$statusLabel = [
    'open'        => ['label' => 'Open',        'class' => 'label-primary'],
    'in_progress' => ['label' => 'In Progress',  'class' => 'label-warning'],
    'resolved'    => ['label' => 'Resolved',     'class' => 'label-success'],
    'closed'      => ['label' => 'Closed',       'class' => 'label-default'],
];
$sl       = $statusLabel[$ticket->status ?? 'open'] ?? ['label' => ucfirst($ticket->status ?? ''), 'class' => 'label-default'];
$isClosed = in_array($ticket->status ?? '', ['resolved','closed'], true);

function adminAttachIcon(string $mime): string {
    if (str_contains($mime,'pdf'))   return 'fa-file-pdf-o';
    if (str_contains($mime,'image')) return 'fa-file-image-o';
    if (str_contains($mime,'word') || str_contains($mime,'doc')) return 'fa-file-word-o';
    if (str_contains($mime,'sheet') || str_contains($mime,'excel') || str_contains($mime,'xls')) return 'fa-file-excel-o';
    if (str_contains($mime,'zip')  || str_contains($mime,'rar'))  return 'fa-file-archive-o';
    return 'fa-file-o';
}
?>

<div class="page-title">
    <div class="title_left">
        <h3>Support Tickets <small><?= esc($ticket->ticket_number ?? '') ?></small></h3>
    </div>
    <div class="title_right">
        <a href="/admin/support" class="btn btn-default pull-right"><i class="fa fa-arrow-left"></i> Back</a>
    </div>
</div>
<div class="clearfix"></div>

<div class="row">
    <div class="col-md-8">

        <!-- Ticket info bar -->
        <div class="x_panel" style="margin-bottom:15px;">
            <div class="x_content" style="padding:12px 18px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                <strong style="font-size:14px;"><?= esc($ticket->subject ?? '') ?></strong>
                <span class="label <?= $sl['class'] ?>" style="font-size:12px;"><?= $sl['label'] ?></span>
                <span class="label label-info" style="font-size:12px;"><?= ucfirst($ticket->category ?? '') ?></span>
                <span class="label label-default" style="font-size:12px;"><?= ucfirst($ticket->priority ?? '') ?> Priority</span>
                <span style="font-size:12px;color:#7f8c8d;margin-left:auto;">Hospital: <strong><?= esc($ticket->hospital_name ?? 'N/A') ?></strong></span>
            </div>
        </div>

        <!-- Message thread -->
        <div class="x_panel">
            <div class="x_title"><h2><i class="fa fa-comments"></i> Messages</h2><div class="clearfix"></div></div>
            <div class="x_content" style="padding:0;">
                <?php foreach ($messages as $msg): ?>
                <?php $isAdmin = ($msg->sender_type === 'admin'); ?>
                <div style="padding:16px 20px;border-bottom:1px solid #f0f3f8;display:flex;gap:12px;<?= $isAdmin ? 'background:#f0fff4;' : '' ?>">
                    <div style="flex-shrink:0;">
                        <div style="width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#fff;<?= $isAdmin ? 'background:#00897b;' : 'background:#2c3e50;' ?>">
                            <?= strtoupper(substr((string)($msg->sender_name ?? 'A'), 0, 1)) ?>
                        </div>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                            <strong style="font-size:13px;"><?= esc((string)($msg->sender_name ?? 'Unknown')) ?></strong>
                            <?php if ($isAdmin): ?>
                            <span class="label label-success">ADMIN</span>
                            <?php else: ?>
                            <span class="label label-default">HOSPITAL</span>
                            <?php endif; ?>
                            <span style="font-size:11px;color:#adb5bd;margin-left:auto;"><?= esc((string)($msg->created_at ?? '')) ?></span>
                        </div>
                        <!-- Rich text rendered -->
                        <div class="ql-editor" style="padding:0;font-size:13px;color:#374151;"><?= ($msg->message ?? '') ?></div>
                        <!-- Attachments for this message -->
                        <?php if (!empty($attachments[(int)$msg->id])): ?>
                        <div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:8px;">
                            <?php foreach ($attachments[(int)$msg->id] as $att): ?>
                            <a href="/admin/support/attachment/<?= (int)$att->id ?>" target="_blank"
                               class="btn btn-xs btn-default"
                               style="display:inline-flex;align-items:center;gap:5px;font-size:12px;">
                                <i class="fa <?= adminAttachIcon((string)($att->mime_type ?? '')) ?>"></i>
                                <?= esc($att->original_name) ?>
                                <span style="color:#95a5a6;">(<?= round((int)$att->file_size/1024, 1) ?> KB)</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                <div style="text-align:center;padding:40px;color:#6c757d;font-size:13px;">No messages yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reply form -->
        <?php if (!$isClosed): ?>
        <div class="x_panel">
            <div class="x_title"><h2><i class="fa fa-reply"></i> Reply to Hospital</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <form method="post" action="/admin/support/<?= (int)$ticket->id ?>/reply" enctype="multipart/form-data" id="adminReplyForm">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <div id="adminReplyQuill" style="min-height:140px;font-size:13px;background:#fff;"></div>
                        <input type="hidden" name="message" id="adminReplyMessage">
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px;font-weight:600;"><i class="fa fa-paperclip"></i> Attach files (optional, max 5MB each)</label>
                        <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
                            <label for="adminFiles" class="btn btn-default btn-sm" style="font-size:12px;margin:0;cursor:pointer;">
                                <i class="fa fa-cloud-upload"></i> Browse Files
                            </label>
                            <input type="file" id="adminFiles" name="attachments[]" multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip"
                                   style="display:none;" onchange="showAdminFiles(this.files)">
                            <span id="adminFileCount" style="font-size:12px;color:#7f8c8d;"></span>
                        </div>
                        <div id="adminFileList" style="margin-top:6px;"></div>
                    </div>
                    <div class="form-group">
                        <label style="font-size:12px;font-weight:600;"><i class="fa fa-flag"></i> Update Status</label>
                        <select name="status" class="form-control" style="max-width:220px;font-size:13px;">
                            <option value="open"        <?= ($ticket->status ?? '') === 'open'        ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= ($ticket->status ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved"    <?= ($ticket->status ?? '') === 'resolved'    ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed"      <?= ($ticket->status ?? '') === 'closed'      ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-paper-plane"></i> Send Reply
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fa fa-lock"></i> This ticket is <?= esc($ticket->status) ?>. To reopen it, change the status.
        </div>
        <!-- Status-only form when closed -->
        <div class="x_panel">
            <div class="x_title"><h2><i class="fa fa-flag"></i> Update Status</h2><div class="clearfix"></div></div>
            <div class="x_content">
                <form method="post" action="/admin/support/<?= (int)$ticket->id ?>/reply" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="message" value="">
                    <div class="form-group">
                        <select name="status" class="form-control" style="max-width:220px;font-size:13px;">
                            <option value="open"        <?= ($ticket->status ?? '') === 'open'        ? 'selected' : '' ?>>Open</option>
                            <option value="in_progress" <?= ($ticket->status ?? '') === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                            <option value="resolved"    <?= ($ticket->status ?? '') === 'resolved'    ? 'selected' : '' ?>>Resolved</option>
                            <option value="closed"      <?= ($ticket->status ?? '') === 'closed'      ? 'selected' : '' ?>>Closed</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-warning btn-sm"><i class="fa fa-save"></i> Update Status</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="x_panel">
            <div class="x_title"><h2><i class="fa fa-info-circle"></i> Ticket Info</h2><div class="clearfix"></div></div>
            <div class="x_content" style="padding:0;">
                <?php
                $details = [
                    ['Ticket #',    $ticket->ticket_number ?? ''],
                    ['Hospital',    $ticket->hospital_name ?? 'N/A'],
                    ['Status',      $sl['label']],
                    ['Category',    ucfirst($ticket->category ?? '')],
                    ['Priority',    ucfirst($ticket->priority ?? '')],
                    ['Messages',    (string)($ticket->message_count ?? 0)],
                    ['Created',     $ticket->created_at ?? ''],
                    ['Last Reply',  $ticket->last_reply_at ?? '—'],
                ];
                foreach ($details as [$label, $val]):
                ?>
                <div style="display:flex;justify-content:space-between;padding:9px 15px;border-bottom:1px solid #f0f3f8;font-size:13px;">
                    <span style="color:#7f8c8d;font-weight:500;"><?= esc($label) ?></span>
                    <span style="font-weight:600;text-align:right;"><?= esc((string)$val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
<?php if (!$isClosed): ?>
var adminReplyQuill = new Quill('#adminReplyQuill', {
    theme: 'snow',
    placeholder: 'Type your reply...',
    modules: {
        toolbar: [
            [{'header': [1,2,3,false]}],
            ['bold','italic','underline'],
            [{'list':'ordered'},{'list':'bullet'}],
            ['blockquote','code-block','clean']
        ]
    }
});
document.getElementById('adminReplyForm').addEventListener('submit', function() {
    document.getElementById('adminReplyMessage').value = adminReplyQuill.root.innerHTML;
});
function showAdminFiles(files) {
    var list = document.getElementById('adminFileList');
    var cnt  = document.getElementById('adminFileCount');
    cnt.textContent = files.length + ' file(s) selected';
    list.innerHTML  = '';
    for (var i = 0; i < files.length; i++) {
        list.innerHTML += '<div style="font-size:12px;padding:3px 0;">' +
            '<i class="fa fa-file" style="color:#2c3e50;margin-right:5px;"></i>' +
            files[i].name.replace(/&/g,'&amp;').replace(/</g,'&lt;') + '</div>';
    }
}
<?php endif; ?>
</script>
<?= $this->endSection() ?>
