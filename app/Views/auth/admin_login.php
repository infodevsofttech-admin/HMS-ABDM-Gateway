<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login - ABDM Bridge Gateway</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1e3a5f 0%, #2563eb 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 14px;
            box-shadow: 0 24px 64px rgba(0,0,0,0.35);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 28px;
        }
        .login-header .brand-logo {
            display: block;
            height: 42px;
            width: auto;
            margin: 0 auto 10px;
        }
        .login-header .brand-divider {
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg,#667eea,#764ba2);
            border-radius: 2px;
            margin: 0 auto 12px;
        }
        .login-header .badge {
            display: inline-block;
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            padding: 3px 12px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        .login-header h1 {
            font-size: 24px;
            color: #111827;
            margin-bottom: 6px;
        }
        .login-header p {
            color: #6b7280;
            font-size: 13px;
        }
        .form-group { margin-bottom: 18px; }
        label {
            display: block;
            margin-bottom: 6px;
            color: #374151;
            font-weight: 600;
            font-size: 13px;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #111827;
            transition: border-color .2s, box-shadow .2s;
        }
        input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 6px;
            transition: background .2s;
        }
        button[type="submit"]:hover { background: #1d4ed8; }
        .alert {
            padding: 11px 14px;
            border-radius: 8px;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
        .divider { text-align: center; margin-top: 22px; padding-top: 18px; border-top: 1px solid #f3f4f6; }
        .divider a { color: #6b7280; font-size: 12px; text-decoration: none; }
        .divider a:hover { color: #2563eb; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="/assets/img/e-atria-logo.png" alt="E-Atria" class="brand-logo">
            <div class="brand-divider"></div>
            <div class="badge">Admin Panel</div>
            <h1>ABDM Bridge Gateway</h1>
            <p>Sign in with your admin credentials</p>
        </div>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error"><?= esc((string) session()->getFlashdata('error')) ?></div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('message')): ?>
            <div class="alert alert-success"><?= esc((string) session()->getFlashdata('message')) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" autocomplete="username"
                       value="<?= esc((string) old('username')) ?>" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" autocomplete="current-password" required>
            </div>
            <button type="submit">Sign In</button>
        </form>

        <div class="divider">
            <a href="/">Hospital / Clinic portal →</a>
        </div>
    </div>
</body>
</html>
