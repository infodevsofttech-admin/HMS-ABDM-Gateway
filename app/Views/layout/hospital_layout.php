<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Hospital Portal') ?> | ABDM Bridge</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gentelella@1.4.0/build/css/custom.min.css">
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
