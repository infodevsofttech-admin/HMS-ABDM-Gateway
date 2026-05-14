<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'ABDM Gateway') ?> | ABDM Bridge</title>
    <!-- Bootstrap 3 -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- Gentelella -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gentelella@1.4.0/build/css/custom.min.css">
    <style>
        .site_title { font-size: 18px !important; font-weight: 700 !important; }
        .nav_title { background: #2A3F54 !important; }
        .profile_info h2 { font-size: 14px !important; color: #fff !important; }
        .badge-role { font-size: 10px; padding: 2px 7px; border-radius: 999px; background: #1ABB9C; color: #fff; font-weight: 600; }
        .x_panel { margin-bottom: 20px; }
        .label-test { background: #3498DB; }
        .label-live { background: #E74C3C; }
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
                <a href="/admin/dashboard" class="site_title">
                    <i class="fa fa-h-square"></i>
                    <span>ABDM Gateway</span>
                </a>
            </div>
            <div class="clearfix"></div>

            <div class="profile clearfix">
                <div class="profile_info">
                    <span style="color:#aaa;font-size:11px;">Welcome,</span>
                    <h2><?= esc((string) session()->get('username')) ?></h2>
                    <span class="badge-role"><?= esc((string) (session()->get('role') ?? 'admin')) ?></span>
                </div>
            </div>
            <br>

            <div id="sidebar-menu" class="main_menu_side hidden-print main_menu">
                <div class="menu_section">
                    <h3>Main</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/admin/dashboard') ? 'current-page active' : '' ?>">
                            <a href="/admin/dashboard"><i class="fa fa-home"></i> Dashboard</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/hospitals') ? 'current-page active' : '' ?>">
                            <a href="/admin/hospitals"><i class="fa fa-hospital-o"></i> Hospitals</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/users') ? 'current-page active' : '' ?>">
                            <a href="/admin/users"><i class="fa fa-users"></i> Hospital Users</a>
                        </li>
                    </ul>
                </div>
                <div class="menu_section">
                    <h3>ABDM M1</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/admin/m1') ? 'current-page active' : '' ?>">
                            <a href="/admin/m1"><i class="fa fa-heartbeat"></i> M1 Suite</a>
                        </li>
                    </ul>
                </div>
                <div class="menu_section">
                    <h3>Logs &amp; Data</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/admin/test-logs') ? 'current-page active' : '' ?>">
                            <a href="/admin/test-logs"><i class="fa fa-flask"></i> Test Logs</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/logs') ? 'current-page active' : '' ?>">
                            <a href="/admin/logs"><i class="fa fa-list-alt"></i> Request Logs</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/audit') ? 'current-page active' : '' ?>">
                            <a href="/admin/audit"><i class="fa fa-shield"></i> Audit Trail</a>
                        </li>
                        <li class="<?= str_contains(current_url(), '/admin/bundles') ? 'current-page active' : '' ?>">
                            <a href="/admin/bundles"><i class="fa fa-archive"></i> Bundles</a>
                        </li>
                    </ul>
                </div>
                <div class="menu_section">
                    <h3>Support</h3>
                    <ul class="nav side-menu">
                        <li class="<?= str_contains(current_url(), '/admin/support') ? 'current-page active' : '' ?>">
                            <a href="/admin/support"><i class="fa fa-ticket"></i> Support Tickets</a>
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
                        <a href="/auth/logout" style="line-height:55px;padding:0 20px;color:#ECF0F1;">
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

    <!-- ============ FOOTER ============ -->
    <footer>
        <div class="pull-right" style="color:#aaa;font-size:12px;">
            ABDM Bridge Gateway &copy; <?= date('Y') ?>
        </div>
        <div class="clearfix"></div>
    </footer>

</div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<!-- Gentelella JS -->
<script src="https://cdn.jsdelivr.net/npm/gentelella@1.4.0/build/js/custom.min.js"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>

