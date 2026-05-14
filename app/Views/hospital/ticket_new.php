<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'New Support Ticket'; ?>

<?= $this->section('content') ?>

<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">

<div class="hp-page-header">
    <div>
        <h5><i class="fas fa-plus-circle"></i> New Support Ticket</h5>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/dashboard">Home</a></li>
            <li class="breadcrumb-item"><a href="/portal/tickets">Support</a></li>
            <li class="breadcrumb-item active">New Ticket</li>
        </ol></nav>
    </div>
</div>

<div class="hp-content">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-ticket-alt"></i> Create Support Ticket</div>
                <div class="hp-card-body">
                    <form method="post" action="/portal/tickets/new" enctype="multipart/form-data" id="ticketForm">
                        <?= csrf_field() ?>

                        <div class="form-group mb-3">
                            <label style="font-size:13px;font-weight:600;">Subject <span style="color:#dc3545;">*</span></label>
                            <input type="text" name="subject" class="form-control" placeholder="Brief description of your issue"
                                   maxlength="200" required style="font-size:13px;">
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label style="font-size:13px;font-weight:600;">Category</label>
                                    <select name="category" class="form-control" style="font-size:13px;">
                                        <option value="general">General</option>
                                        <option value="technical">Technical Issue</option>
                                        <option value="abha">ABHA Related</option>
                                        <option value="opd">OPD / Token Queue</option>
                                        <option value="billing">Billing</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label style="font-size:13px;font-weight:600;">Priority</label>
                                    <select name="priority" class="form-control" style="font-size:13px;">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Quill rich text editor -->
                        <div class="form-group mb-3">
                            <label style="font-size:13px;font-weight:600;">Message <span style="color:#dc3545;">*</span></label>
                            <div id="quillEditor" style="min-height:180px;font-size:13px;background:#fff;"></div>
                            <input type="hidden" name="message" id="messageInput">
                            <div style="font-size:11px;color:#6c757d;margin-top:4px;">Supports bold, italic, lists, headings and more.</div>
                        </div>

                        <!-- Attachments -->
                        <div class="form-group mb-4">
                            <label style="font-size:13px;font-weight:600;"><i class="fas fa-paperclip mr-1"></i> Attachments <span style="font-size:11px;font-weight:400;color:#6c757d;">(optional, max 5MB each)</span></label>
                            <div id="dropzone" style="border:2px dashed var(--hp-border);border-radius:8px;padding:20px;text-align:center;cursor:pointer;background:#f8f9fb;transition:border-color .2s;"
                                 onclick="document.getElementById('fileInput').click()"
                                 ondragover="event.preventDefault();this.style.borderColor='var(--hp-primary)'"
                                 ondragleave="this.style.borderColor='var(--hp-border)'"
                                 ondrop="handleDrop(event)">
                                <i class="fas fa-cloud-upload-alt" style="font-size:28px;color:#adb5bd;display:block;margin-bottom:6px;"></i>
                                <div style="font-size:13px;color:#6c757d;">Drag &amp; drop files here, or click to browse</div>
                                <div style="font-size:11px;color:#adb5bd;margin-top:4px;">PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, ZIP — max 5MB each</div>
                            </div>
                            <input type="file" id="fileInput" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.txt,.zip" style="display:none;" onchange="showFiles(this.files)">
                            <div id="fileList" style="margin-top:8px;"></div>
                        </div>

                        <div class="d-flex" style="gap:10px;">
                            <button type="submit" class="btn btn-primary" style="font-size:13px;">
                                <i class="fas fa-paper-plane mr-1"></i> Submit Ticket
                            </button>
                            <a href="/portal/tickets" class="btn btn-outline-secondary" style="font-size:13px;margin-left:8px;">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
var quill = new Quill('#quillEditor', {
    theme: 'snow',
    placeholder: 'Describe your issue in detail...',
    modules: {
        toolbar: [
            [{ 'header': [1,2,3,false] }],
            ['bold','italic','underline','strike'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            ['blockquote','code-block'],
            ['clean']
        ]
    }
});
document.getElementById('ticketForm').addEventListener('submit', function() {
    document.getElementById('messageInput').value = quill.root.innerHTML;
});
function showFiles(files) {
    var list = document.getElementById('fileList');
    list.innerHTML = '';
    for (var i = 0; i < files.length; i++) {
        var size = (files[i].size / 1024).toFixed(1) + ' KB';
        list.innerHTML += '<div style="display:flex;align-items:center;gap:8px;padding:6px 10px;background:#f0f7ff;border-radius:6px;margin-bottom:4px;font-size:12px;">' +
            '<i class="fas fa-file" style="color:var(--hp-primary);"></i>' +
            '<span style="flex:1;">' + escHtml(files[i].name) + '</span>' +
            '<span style="color:#6c757d;">' + size + '</span>' +
            '</div>';
    }
}
function handleDrop(e) {
    e.preventDefault();
    document.getElementById('dropzone').style.borderColor = 'var(--hp-border)';
    document.getElementById('fileInput').files = e.dataTransfer.files;
    showFiles(e.dataTransfer.files);
}
function escHtml(s) { return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
</script>
<?= $this->endSection() ?>
