<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - ABDM Gateway</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header .brand-logo {
            display: block;
            height: 46px;
            width: auto;
            margin: 0 auto 10px;
        }
        .login-header .brand-divider {
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg,#667eea,#764ba2);
            border-radius: 2px;
            margin: 8px auto 10px;
        }
        .login-header h1 {
            font-size: 20px;
            color: #1f2937;
            margin-bottom: 4px;
        }
        .login-header p {
            color: #6b7280;
            font-size: 13px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        button:hover {
            background: #5568d3;
        }
        button:active {
            transform: scale(0.98);
        }
        .alert {
            padding: 12px;
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
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php
        $portal = $portal ?? 'hospital';
        $formAction = $formAction ?? '/auth/login';
        $isAdminPortal = $portal === 'admin';
        $title = $isAdminPortal ? 'ABDM Admin Portal' : 'ABDM Gateway';
        $subtitle = $isAdminPortal ? 'Internal Team Access' : 'Hospital Management System Access';
    ?>
    <div class="login-container">
        <div class="login-header">
            <img src="/assets/img/e-atria-logo.png" alt="E-Atria" class="brand-logo">
            <div class="brand-divider"></div>
            <h1><?= esc($title) ?></h1>
            <p><?= esc($subtitle) ?></p>
        </div>

        <?php if (session()->has('error')): ?>
            <div class="alert alert-error">
                <?= esc((string) session()->getFlashdata('error')) ?>
            </div>
        <?php endif; ?>

        <?php if (session()->has('message')): ?>
            <div class="alert alert-message">
                <?= esc((string) session()->getFlashdata('message')) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= esc($formAction) ?>">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <?php if (!$isAdminPortal): ?>
                <div class="form-group">
                    <label for="hfr_id">HFR ID / Hospital ID</label>
                    <input type="text" id="hfr_id" name="hfr_id" placeholder="Enter registered HFR ID (e.g., TH-2026-001)" required>
                </div>
            <?php endif; ?>

            <button type="submit">Login</button>

            <?php if (!$isAdminPortal): ?>
                <div class="register-link">
                    Don't have an account? <a href="/auth/register">Register here</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
