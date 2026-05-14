<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $title ?? 'ABDM Gateway' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8fafc;
        }
        .navbar {
            background: #1f2937;
            color: white;
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 64px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .user-info {
            text-align: right;
            font-size: 13px;
        }
        .user-info .username {
            font-weight: 600;
            display: block;
        }
        .user-info .hospital {
            color: #d1d5db;
            font-size: 12px;
        }
        .logout-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .logout-btn:hover {
            background: #dc2626;
        }
        .sidebar {
            background: #f3f4f6;
            border-right: 1px solid #e5e7eb;
            width: 250px;
            min-height: calc(100vh - 64px);
            padding: 20px 0;
            position: fixed;
            left: 0;
            top: 64px;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin: 0;
        }
        .sidebar-menu a {
            display: block;
            padding: 12px 24px;
            color: #374151;
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: all 0.2s;
            font-size: 14px;
        }
        .sidebar-menu a:hover {
            background: #e5e7eb;
            border-left-color: #1d4ed8;
        }
        .sidebar-menu a.active {
            background: #dbeafe;
            color: #1d4ed8;
            border-left-color: #1d4ed8;
            font-weight: 600;
        }
        .main-content {
            margin-left: 250px;
            margin-top: 64px;
            padding: 24px;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-message {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <div class="navbar-brand">ABDM Gateway</div>
        <div class="navbar-user">
            <div class="user-info">
                <span class="username"><?= esc(session()->get('username')) ?></span>
                <span class="hospital" id="hospital-info"></span>
            </div>
            <a href="/auth/logout" class="logout-btn">Logout</a>
        </div>
    </div>

    <div class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="/admin/dashboard" class="<?= current_url() === base_url('admin/dashboard') ? 'active' : '' ?>">Dashboard</a></li>
            <li><a href="/admin/hospitals" class="<?= str_contains(current_url(), '/admin/hospitals') ? 'active' : '' ?>">Hospitals</a></li>
            <li><a href="/admin/users" class="<?= current_url() === base_url('admin/users') ? 'active' : '' ?>">Hospital Users</a></li>
            <li><a href="/admin/hms-access" class="<?= str_contains(current_url(), '/admin/hms') ? 'active' : '' ?>">HMS Access</a></li>
            <li><a href="/admin/m1-module" class="<?= current_url() === base_url('admin/m1-module') ? 'active' : '' ?>">M1 Module</a></li>
            <li><a href="/admin/test-logs" class="<?= current_url() === base_url('admin/test-logs') ? 'active' : '' ?>">Test Logs</a></li>
            <li><a href="/admin/logs" class="<?= current_url() === base_url('admin/logs') ? 'active' : '' ?>">Request Logs</a></li>
            <li><a href="/admin/audit" class="<?= current_url() === base_url('admin/audit') ? 'active' : '' ?>">Audit Trail</a></li>
            <li><a href="/admin/bundles" class="<?= current_url() === base_url('admin/bundles') ? 'active' : '' ?>">Bundles</a></li>
        </ul>
    </div>

    <div class="main-content">
        <?php if (session()->has('error')): ?>
            <div class="alert alert-error">
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <?php if (session()->has('message')): ?>
            <div class="alert alert-message">
                <?= esc(session()->getFlashdata('message')) ?>
            </div>
        <?php endif; ?>

        <?= $this->renderSection('content') ?>
    </div>

    <script>
        // Load hospital name from session/API if needed
        document.addEventListener('DOMContentLoaded', function() {
            const hospitalInfo = document.getElementById('hospital-info');
            if (hospitalInfo) {
                // You can add logic here to fetch and display hospital name
                // For now, we'll leave it as a placeholder
                hospitalInfo.textContent = 'Hospital Admin';
            }
        });
    </script>
</body>
</html>
