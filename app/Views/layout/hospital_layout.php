<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Hospital Portal') ?> | ABDM Bridge</title>
    <!-- Bootstrap 4 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --hp-primary:   #0d6efd;
            --hp-dark:      #0a3d62;
            --hp-accent:    #00b894;
            --hp-light-bg:  #f0f4f8;
            --hp-card-bg:   #ffffff;
            --hp-border:    #dee2e6;
        }

        body {
            background: var(--hp-light-bg);
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #212529;
            min-height: 100vh;
        }

        /* ── TOP NAVBAR ── */
        .hp-navbar {
            background: var(--hp-dark);
            padding: 0;
            box-shadow: 0 2px 8px rgba(0,0,0,.25);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .hp-navbar .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 20px;
            height: 56px;
            text-decoration: none;
        }
        .hp-navbar .brand .brand-icon {
            width: 36px; height: 36px;
            background: var(--hp-primary);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 18px;
        }
        .hp-navbar .brand .brand-text {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.1;
        }
        .hp-navbar .brand .brand-sub {
            color: rgba(255,255,255,.55);
            font-size: 10px;
            font-weight: 400;
            display: block;
        }
        .hp-nav-menu {
            display: flex;
            align-items: center;
            height: 56px;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .hp-nav-menu li a,
        .hp-nav-menu li button {
            display: flex;
            align-items: center;
            gap: 7px;
            height: 56px;
            padding: 0 18px;
            color: rgba(255,255,255,.8);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            background: none;
            border: none;
            cursor: pointer;
            transition: background .15s, color .15s;
            white-space: nowrap;
        }
        .hp-nav-menu li a:hover,
        .hp-nav-menu li button:hover {
            background: rgba(255,255,255,.1);
            color: #fff;
        }
        .hp-nav-menu li a.active {
            background: var(--hp-primary);
            color: #fff;
        }
        /* dropdown */
        .hp-dropdown { position: relative; }
        .hp-dropdown-menu {
            display: none;
            position: absolute;
            top: 56px;
            left: 0;
            min-width: 220px;
            background: #fff;
            border: 1px solid var(--hp-border);
            border-radius: 0 0 8px 8px;
            box-shadow: 0 6px 20px rgba(0,0,0,.12);
            z-index: 999;
            padding: 6px 0;
        }
        .hp-dropdown:hover .hp-dropdown-menu { display: block; }
        .hp-dropdown-menu a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 18px;
            color: #212529 !important;
            font-size: 13px;
            text-decoration: none;
            transition: background .12s;
        }
        .hp-dropdown-menu a:hover { background: #f0f4f8; }
        .hp-dropdown-menu a i { width: 16px; color: var(--hp-primary); }
        .hp-dropdown-divider { border-top: 1px solid #e9ecef; margin: 4px 0; }

        /* user chip */
        .hp-user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 0 16px;
            height: 56px;
            color: rgba(255,255,255,.75);
            font-size: 12px;
        }
        .hp-user-chip .avatar {
            width: 30px; height: 30px;
            background: var(--hp-accent);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
        }

        /* ── BREADCRUMB / HEADER BAR ── */
        .hp-page-header {
            background: #fff;
            border-bottom: 1px solid var(--hp-border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .hp-page-header h4 {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--hp-dark);
        }
        .hp-page-header .breadcrumb {
            margin: 0;
            padding: 0;
            background: none;
            font-size: 12px;
        }

        /* ── MAIN CONTENT ── */
        .hp-content {
            padding: 24px;
        }

        /* ── CARDS ── */
        .hp-card {
            background: var(--hp-card-bg);
            border-radius: 10px;
            border: 1px solid var(--hp-border);
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            margin-bottom: 20px;
        }
        .hp-card-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--hp-border);
            font-weight: 700;
            font-size: 14px;
            color: var(--hp-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .hp-card-header i { color: var(--hp-primary); }
        .hp-card-body { padding: 20px; }

        /* ── STAT TILE ── */
        .hp-stat {
            background: #fff;
            border-radius: 10px;
            border: 1px solid var(--hp-border);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,.05);
        }
        .hp-stat .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            color: #fff;
            flex-shrink: 0;
        }
        .hp-stat .stat-num { font-size: 26px; font-weight: 800; line-height: 1; color: var(--hp-dark); }
        .hp-stat .stat-label { font-size: 12px; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 2px; }

        /* ── TABLE ── */
        .hp-table { font-size: 13px; width: 100%; border-collapse: collapse; }
        .hp-table th { background: #f8f9fa; padding: 10px 14px; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; color: #6c757d; border-bottom: 2px solid var(--hp-border); text-align: left; }
        .hp-table td { padding: 10px 14px; border-bottom: 1px solid #f0f4f8; vertical-align: middle; }
        .hp-table tr:last-child td { border-bottom: 0; }
        .hp-table tr:hover td { background: #f8fafc; }

        /* badges */
        .hp-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 700; }
        .hp-badge-success { background: #d1fae5; color: #065f46; }
        .hp-badge-warning { background: #fef9c3; color: #713f12; }
        .hp-badge-info    { background: #dbeafe; color: #1e40af; }

        /* ── FOOTER ── */
        .hp-footer {
            text-align: center;
            padding: 16px;
            font-size: 12px;
            color: #adb5bd;
            border-top: 1px solid var(--hp-border);
            margin-top: 20px;
        }

        /* mobile nav toggle */
        .hp-menu-toggle {
            display: none;
            align-items: center;
            padding: 0 14px;
            height: 56px;
            color: #fff;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .hp-nav-desktop { display: none !important; }
            .hp-menu-toggle { display: flex !important; }
            .hp-nav-mobile {
                display: none;
                flex-direction: column;
                background: var(--hp-dark);
                padding-bottom: 10px;
            }
            .hp-nav-mobile.open { display: flex; }
            .hp-nav-mobile a {
                padding: 10px 20px;
                color: rgba(255,255,255,.8);
                font-size: 14px;
                text-decoration: none;
                display: flex; gap: 10px; align-items: center;
            }
            .hp-nav-mobile a:hover { background: rgba(255,255,255,.08); color: #fff; }
            .hp-content { padding: 16px; }
        }
    </style>
</head>
<body>

<!-- ============ NAVBAR ============ -->
<nav class="hp-navbar">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <!-- Brand -->
            <a href="/dashboard" class="brand">
                <div class="brand-icon"><i class="fas fa-hospital-alt"></i></div>
                <div class="brand-text">
                    Hospital Portal
                    <span class="brand-sub">ABDM Bridge Gateway</span>
                </div>
            </a>

            <!-- Desktop nav -->
            <ul class="hp-nav-menu hp-nav-desktop ml-2">
                <li>
                    <a href="/dashboard" class="<?= !str_contains(current_url(), '/admin') ? 'active' : '' ?>">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </li>
                <li class="hp-dropdown">
                    <a href="#" onclick="return false;" class="<?= str_contains(current_url(), '/admin/m1') ? 'active' : '' ?>">
                        <i class="fas fa-id-card"></i> ABHA Tools <i class="fas fa-caret-down ml-1" style="font-size:10px;"></i>
                    </a>
                    <div class="hp-dropdown-menu">
                        <a href="/admin/m1/abha-validate"><i class="fas fa-search"></i> Validate ABHA</a>
                        <a href="/admin/m1/otp-flow"><i class="fas fa-plus-circle"></i> Create ABHA (New Patient)</a>
                        <a href="/admin/m1/verify-flow"><i class="fas fa-check-circle"></i> Verify ABHA (Existing)</a>
                        <div class="hp-dropdown-divider"></div>
                        <a href="/admin/m1/abha-profiles"><i class="fas fa-database"></i> Patient Master</a>
                    </div>
                </li>
                <li>
                    <a href="/admin/m1/scan-share" class="<?= str_contains(current_url(), 'scan-share') ? 'active' : '' ?>">
                        <i class="fas fa-ticket-alt"></i> OPD Token Queue
                    </a>
                </li>
            </ul>
        </div>

        <!-- Right: user + logout -->
        <div class="d-flex align-items-center hp-nav-desktop">
            <div class="hp-user-chip">
                <div class="avatar"><?= strtoupper(substr((string) session()->get('username'), 0, 1)) ?></div>
                <div>
                    <div style="color:#fff;font-weight:600;"><?= esc((string) session()->get('username')) ?></div>
                    <div style="font-size:10px;"><?= esc((string) session()->get('role')) ?></div>
                </div>
            </div>
            <a href="/portal/logout" style="height:56px;padding:0 18px;display:flex;align-items:center;gap:6px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;border-left:1px solid rgba(255,255,255,.1);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Mobile toggle -->
        <button class="hp-menu-toggle" onclick="document.getElementById('hp-mobile-nav').classList.toggle('open')">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Mobile nav -->
    <div id="hp-mobile-nav" class="hp-nav-mobile">
        <a href="/dashboard"><i class="fas fa-home"></i> Dashboard</a>
        <a href="/admin/m1/abha-validate"><i class="fas fa-search"></i> Validate ABHA</a>
        <a href="/admin/m1/otp-flow"><i class="fas fa-plus-circle"></i> Create ABHA</a>
        <a href="/admin/m1/verify-flow"><i class="fas fa-check-circle"></i> Verify ABHA</a>
        <a href="/admin/m1/abha-profiles"><i class="fas fa-database"></i> Patient Master</a>
        <a href="/admin/m1/scan-share"><i class="fas fa-ticket-alt"></i> OPD Token Queue</a>
        <a href="/portal/logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<!-- ============ PAGE CONTENT ============ -->
<main>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= esc((string) session()->getFlashdata('error')) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('message')): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <?= esc((string) session()->getFlashdata('message')) ?>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    <?php endif; ?>

    <?= $this->renderSection('content') ?>
</main>

<div class="hp-footer">
    ABDM Bridge Gateway &copy; <?= date('Y') ?> &nbsp;|&nbsp; Hospital Portal
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

    <style>
        .site_title { font-size: 18px !important; font-weight: 700 !important; }
        .nav_title { background: #2A3F54 !important; }
        .profile_info h2 { font-size: 14px !important; color: #fff !important; }
        .badge-role { font-size: 10px; padding: 2px 7px; border-radius: 999px; background: #1ABB9C; color: #fff; font-weight: 600; }
        .x_panel { margin-bottom: 20px; }
        .table td, .table th { vertical-align: middle !important; }
    </style>
</head>
<body class="nav-md">
<div class="container body">
<div class="main_container">

    <!-- ============ LEFT SIDEBAR ============ -->
    <div class="col-md-3 left_col menu_fixed">
        <div class="left_col scroll-view">
            <div class="navbar nav_title" style="border: 0;">
                <a href="/dashboard" class="site_title">
                    <i class="fa fa-hospital-o"></i>
                    <span>Hospital Portal</span>
                </a>
            </div>
            <div class="clearfix"></div>

            <div class="profile clearfix">
                <div class="profile_info">
                    <span style="color:#aaa;font-size:11px;">Welcome,</span>
                    <h2><?= esc((string) session()->get('username')) ?></h2>
                    <span class="badge-role"><?= esc((string) (session()->get('role') ?? 'hospital_user')) ?></span>
                </div>
            </div>
            <br>

            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                <div class="menu_section">
                    <h3>Portal</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/dashboard') && !str_contains(current_url(), '/admin') ? 'current-page active' : '' ?>">
                            <a href="/dashboard"><i class="fa fa-home"></i> Dashboard</a>
                        </li>
                    </ul>
                </div>
                <div class="menu_section">
                    <h3>ABHA Tools</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/admin/m1/abha-validate') ? 'current-page active' : '' ?>">
                            <a href="/admin/m1/abha-validate"><i class="fa fa-search"></i> Validate ABHA</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/m1/otp-flow') ? 'current-page active' : '' ?>">
                            <a href="/admin/m1/otp-flow"><i class="fa fa-plus-circle"></i> Create ABHA</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/m1/verify-flow') ? 'current-page active' : '' ?>">
                            <a href="/admin/m1/verify-flow"><i class="fa fa-check-circle"></i> Verify ABHA</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/m1/abha-profiles') ? 'current-page active' : '' ?>">
                            <a href="/admin/m1/abha-profiles"><i class="fa fa-database"></i> Patient Master</a>
                        </li>
                    </ul>
                </div>
                <div class="menu_section">
                    <h3>OPD Queue</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/admin/m1/scan-share') && !str_contains(current_url(), 'setup') ? 'current-page active' : '' ?>">
                            <a href="/admin/m1/scan-share"><i class="fa fa-ticket"></i> Token Queue</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ TOP NAVIGATION ============ -->
    <div class="top_nav">
        <div class="nav_menu">
            <nav>
                <div class="nav toggle">
                    <a id="menu_toggle"><i class="fa fa-bars"></i></a>
                </div>
                <ul class="nav navbar-nav navbar-right">
                    <li><span class="nav_link" style="line-height:55px;padding:0 20px;color:#ECF0F1;font-size:13px;">
                        <i class="fa fa-user-circle-o"></i> <?= esc((string) session()->get('username')) ?>
                    </span></li>
                    <li>
                        <a href="/portal/logout" style="line-height:55px;padding:0 20px;color:#ECF0F1;">
                            <i class="fa fa-sign-out"></i> Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- ============ PAGE CONTENT ============ -->
    <div class="right_col" role="main">
        <div class="row">
            <div class="col-md-12">

                <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <i class="fa fa-exclamation-circle"></i> <?= esc((string) session()->getFlashdata('error')) ?>
                    </div>
                <?php endif; ?>

                <?php if (session()->getFlashdata('message')): ?>
                    <div class="alert alert-success alert-dismissible" role="alert">
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                        <i class="fa fa-check-circle"></i> <?= esc((string) session()->getFlashdata('message')) ?>
                    </div>
                <?php endif; ?>

                <?= $this->renderSection('content') ?>

            </div>
        </div>
    </div>

    <footer>
        <div class="pull-right" style="color:#aaa;font-size:12px;">
            ABDM Bridge Gateway &copy; <?= date('Y') ?>
        </div>
        <div class="clearfix"></div>
    </footer>

</div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gentelella@1.4.0/build/js/custom.min.js"></script>
</body>
</html>
