<?= $this->extend('layout/hospital_layout') ?>
<?php $title = 'New Support Ticket'; ?>

<?= $this->section('content') ?>

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
        <div class="col-md-8">
            <div class="hp-card">
                <div class="hp-card-head"><i class="fas fa-ticket-alt"></i> Create Support Ticket</div>
                <div class="hp-card-body">
                    <form method="post" action="/portal/tickets/new">
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

                        <div class="form-group mb-4">
                            <label style="font-size:13px;font-weight:600;">Message <span style="color:#dc3545;">*</span></label>
                            <textarea name="message" class="form-control" rows="6" required
                                      placeholder="Describe your issue in detail..." style="font-size:13px;resize:vertical;"></textarea>
                        </div>

                        <div class="d-flex gap-2">
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
