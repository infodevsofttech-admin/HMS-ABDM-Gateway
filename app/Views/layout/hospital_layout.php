<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Hospital Portal') ?> | ABDM Bridge</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --hp-primary: #1565c0;
            --hp-dark:    #0d3b6e;
            --hp-accent:  #00897b;
            --hp-bg:      #f4f6f9;
            --hp-border:  #dde3ec;
        }
        * { box-sizing: border-box; }
        body {
            background: var(--hp-bg);
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #1a1a2e;
            min-height: 100vh;
            margin: 0;
        }

        /* ── TOPBAR ── */
        .hp-topbar {
            background: var(--hp-dark);
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,.3);
        }
        .hp-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .hp-brand-logo {
            width: 38px; height: 38px;
            background: var(--hp-primary);
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 19px;
            flex-shrink: 0;
        }
        .hp-brand-name {
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            line-height: 1.2;
        }
        .hp-brand-name span {
            display: block;
            font-size: 10px;
            font-weight: 400;
            color: rgba(255,255,255,.5);
        }

        /* nav links */
        .hp-topnav {
            display: flex;
            align-items: center;
            gap: 2px;
            list-style: none;
            margin: 0; padding: 0;
        }
        .hp-topnav a {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 58px;
            padding: 0 16px;
            color: rgba(255,255,255,.75);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: background .15s, color .15s;
        }
        .hp-topnav a:hover { background: rgba(255,255,255,.09); color: #fff; text-decoration: none; }
        .hp-topnav a.active { background: var(--hp-primary); color: #fff; }

        /* user pill */
        .hp-user {
            display: flex; align-items: center; gap: 10px;
            border-left: 1px solid rgba(255,255,255,.12);
            padding-left: 16px;
        }
        .hp-avatar {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: var(--hp-accent);
            color: #fff;
            font-size: 14px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .hp-user-info { line-height: 1.2; }
        .hp-user-info strong { color: #fff; font-size: 13px; font-weight: 600; display: block; }
        .hp-user-info small { color: rgba(255,255,255,.5); font-size: 10px; }
        .hp-logout {
            display: flex; align-items: center; gap: 6px;
            height: 58px; padding: 0 16px;
            color: rgba(255,255,255,.6);
            font-size: 13px;
            text-decoration: none;
            border-left: 1px solid rgba(255,255,255,.12);
            transition: background .15s, color .15s;
        }
        .hp-logout:hover { background: rgba(220,53,69,.25); color: #ff8a80; text-decoration: none; }

        /* mobile */
        .hp-hamburger { display: none; background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; padding: 0 8px; }
        .hp-mobile-nav { display: none; flex-direction: column; background: var(--hp-dark); border-top: 1px solid rgba(255,255,255,.1); }
        .hp-mobile-nav.open { display: flex; }
        .hp-mobile-nav a { padding: 12px 20px; color: rgba(255,255,255,.8); font-size: 14px; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .hp-mobile-nav a:hover { background: rgba(255,255,255,.08); color: #fff; }

        @media (max-width: 768px) {
            .hp-topnav, .hp-user { display: none !important; }
            .hp-logout-desktop { display: none !important; }
            .hp-hamburger { display: block; }
        }

        /* ── PAGE HEADER ── */
        .hp-page-header {
            background: #fff;
            border-bottom: 1px solid var(--hp-border);
            padding: 14px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .hp-page-header h5 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--hp-dark);
            display: flex; align-items: center; gap: 8px;
        }
        .hp-page-header .breadcrumb {
            margin: 0; padding: 0; background: none; font-size: 12px;
        }

        /* ── MAIN CONTENT ── */
        .hp-content { padding: 28px; }

        /* ── CARD ── */
        .hp-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid var(--hp-border);
            box-shadow: 0 1px 5px rgba(0,0,0,.05);
            margin-bottom: 22px;
            overflow: hidden;
        }
        .hp-card-head {
            padding: 14px 20px;
            border-bottom: 1px solid var(--hp-border);
            font-size: 14px;
            font-weight: 700;
            color: var(--hp-dark);
            display: flex; align-items: center; gap: 8px;
        }
        .hp-card-head i { color: var(--hp-primary); }
        .hp-card-body { padding: 20px; }

        /* ── TABLE ── */
        .hp-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
        .hp-tbl th { background: #f8f9fb; padding: 10px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; border-bottom: 2px solid var(--hp-border); text-align: left; font-weight: 600; }
        .hp-tbl td { padding: 10px 14px; border-bottom: 1px solid #f0f3f8; vertical-align: middle; }
        .hp-tbl tr:last-child td { border-bottom: 0; }
        .hp-tbl tr:hover td { background: #f8fafc; }

        /* ── BADGES ── */
        .hb { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .hb-green  { background: #d1fae5; color: #065f46; }
        .hb-yellow { background: #fef9c3; color: #713f12; }
        .hb-blue   { background: #dbeafe; color: #1e40af; }
        .hb-red    { background: #fee2e2; color: #991b1b; }

        /* ── FOOTER ── */
        .hp-footer { text-align: center; padding: 18px; font-size: 12px; color: #adb5bd; border-top: 1px solid var(--hp-border); margin-top: 8px; }
    </style>
</head>
<body>

<!-- ══ TOPBAR ══ -->
<header class="hp-topbar">
    <!-- Brand -->
    <a href="/dashboard" class="hp-brand">
        <div class="hp-brand-logo"><i class="fas fa-hospital-alt"></i></div>
        <div class="hp-brand-name">
            Hospital Portal
            <span>ABDM Bridge</span>
        </div>
    </a>

    <!-- Nav links (desktop) -->
    <ul class="hp-topnav">
        <li><a href="/dashboard" class="<?= str_ends_with(current_url(), '/dashboard') ? 'active' : '' ?>"><i class="fas fa-home"></i> Dashboard</a></li>
        <li><a href="/portal/abha-tools" class="<?= str_contains(current_url(), 'abha-tools') ? 'active' : '' ?>"><i class="fas fa-id-card"></i> ABHA Tools</a></li>
        <li><a href="/portal/opd-queue" class="<?= str_contains(current_url(), 'opd-queue') ? 'active' : '' ?>"><i class="fas fa-list-ol"></i> OPD Queue</a></li>
        <li><a href="/portal/patients" class="<?= str_contains(current_url(), 'patients') ? 'active' : '' ?>"><i class="fas fa-user-injured"></i> Patients</a></li>
        <li><a href="/portal/reports" class="<?= str_contains(current_url(), 'reports') ? 'active' : '' ?>"><i class="fas fa-chart-bar"></i> Reports</a></li>
        <li><a href="/portal/profile" class="<?= str_contains(current_url(), 'profile') ? 'active' : '' ?>"><i class="fas fa-hospital"></i> Profile</a></li>
        <li><a href="/portal/tickets" class="<?= str_contains(current_url(), '/portal/ticket') ? 'active' : '' ?>"><i class="fas fa-ticket-alt"></i> Support</a></li>
        <?= $this->renderSection('nav_extra') ?>
    </ul>

    <!-- User + logout (desktop) -->
    <div class="d-flex align-items-center">
        <div class="hp-user">
            <div class="hp-avatar"><?= strtoupper(substr((string) session()->get('username'), 0, 1)) ?></div>
            <div class="hp-user-info">
                <strong><?= esc((string) session()->get('username')) ?></strong>
                <small><?= esc((string) session()->get('role')) ?></small>
            </div>
        </div>
        <a href="/portal/logout" class="hp-logout hp-logout-desktop">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <!-- Mobile hamburger -->
        <button class="hp-hamburger" onclick="document.getElementById('hp-mnav').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
    </div>
</header>

<!-- Mobile nav -->
<div id="hp-mnav" class="hp-mobile-nav">
    <a href="/dashboard"><i class="fas fa-home"></i> Dashboard</a>
    <a href="/portal/abha-tools"><i class="fas fa-id-card"></i> ABHA Tools</a>
    <a href="/portal/opd-queue"><i class="fas fa-list-ol"></i> OPD Queue</a>
    <a href="/portal/patients"><i class="fas fa-user-injured"></i> Patients</a>
    <a href="/portal/reports"><i class="fas fa-chart-bar"></i> Reports</a>
    <a href="/portal/profile"><i class="fas fa-hospital"></i> Profile</a>
    <a href="/portal/tickets"><i class="fas fa-ticket-alt"></i> Support</a>
    <?= $this->renderSection('mobile_nav_extra') ?>
    <a href="/portal/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<!-- ══ FLASH ALERTS ══ -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-0" role="alert" style="border-radius:0;border-left:0;border-right:0;">
        <i class="fas fa-exclamation-circle mr-2"></i> <?= esc((string) session()->getFlashdata('error')) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>
<?php if (session()->getFlashdata('message')): ?>
    <div class="alert alert-success alert-dismissible fade show mb-0" role="alert" style="border-radius:0;border-left:0;border-right:0;">
        <i class="fas fa-check-circle mr-2"></i> <?= esc((string) session()->getFlashdata('message')) ?>
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<!-- ══ PAGE CONTENT ══ -->
<main>
    <?= $this->renderSection('content') ?>
</main>

<div class="hp-footer">
    ABDM Bridge Gateway &copy; <?= date('Y') ?> &nbsp;|&nbsp; Hospital Portal
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
