<?= $this->extend('layout/admin_layout') ?>
<?php $title = 'SMTP Settings'; ?>

<?= $this->section('content') ?>

<?php $s = $settings ?? []; ?>

<div class="page-title">
    <div class="title_left">
        <h3><i class="fa fa-envelope"></i> SMTP Email Settings</h3>
    </div>
</div>
<div class="clearfix"></div>

<?php if (session()->getFlashdata('message')): ?>
<div class="alert alert-success"><?= esc(session()->getFlashdata('message')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
<div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-cog"></i> SMTP Configuration
                    <small>Used for registration approval emails</small>
                </h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/settings/smtp">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>SMTP Host <span style="color:red;">*</span></label>
                                <input type="text" name="smtp_host" class="form-control"
                                       value="<?= esc($s['smtp_host'] ?? '') ?>"
                                       placeholder="e.g. smtp.gmail.com">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Port <span style="color:red;">*</span></label>
                                <input type="text" name="smtp_port" class="form-control"
                                       value="<?= esc($s['smtp_port'] ?? '587') ?>"
                                       placeholder="587">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SMTP Username</label>
                                <input type="text" name="smtp_user" class="form-control"
                                       value="<?= esc($s['smtp_user'] ?? '') ?>"
                                       placeholder="your@email.com" autocomplete="off">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>SMTP Password</label>
                                <input type="password" name="smtp_pass" class="form-control"
                                       value="<?= esc($s['smtp_pass'] ?? '') ?>"
                                       placeholder="Leave blank to keep current"
                                       autocomplete="new-password">
                                <p class="help-block">Leave blank to keep the saved password unchanged.</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_encryption" class="form-control">
                            <?php
                            $enc = $s['smtp_encryption'] ?? 'tls';
                            foreach (['tls'=>'TLS (Recommended)', 'ssl'=>'SSL', 'none'=>'None'] as $v=>$l):
                            ?>
                            <option value="<?= $v ?>" <?= $enc === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <hr>
                    <h4>Sender Details</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>From Email</label>
                                <input type="email" name="smtp_from_email" class="form-control"
                                       value="<?= esc($s['smtp_from_email'] ?? '') ?>"
                                       placeholder="noreply@hospital.com">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" name="smtp_from_name" class="form-control"
                                       value="<?= esc($s['smtp_from_name'] ?? '') ?>"
                                       placeholder="ABDM Gateway">
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin-top:10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> Save SMTP Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Test Email -->
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-paper-plane"></i> Test Email</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content">
                <form method="post" action="/admin/settings/smtp/test">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label>Send test email to</label>
                        <input type="email" name="test_email" class="form-control"
                               placeholder="recipient@example.com" required>
                    </div>
                    <button type="submit" class="btn btn-default">
                        <i class="fa fa-send"></i> Send Test Email
                    </button>
                </form>
            </div>
        </div>

        <!-- Tips -->
        <div class="x_panel">
            <div class="x_title">
                <h2><i class="fa fa-lightbulb-o"></i> Quick Tips</h2>
                <div class="clearfix"></div>
            </div>
            <div class="x_content" style="font-size:12px;color:#555;">
                <p><strong>Gmail:</strong></p>
                <ul>
                    <li>Host: smtp.gmail.com</li>
                    <li>Port: 587 / TLS or 465 / SSL</li>
                    <li>Use App Password (not Google account password)</li>
                    <li>Enable "Less secure app access" or use 2FA + App Password</li>
                </ul>
                <p><strong>Hostinger:</strong></p>
                <ul>
                    <li>Host: smtp.hostinger.com</li>
                    <li>Port: 587 / TLS</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
