<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - ABDM Gateway</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
        }
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .register-header h1 {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .register-header p {
            color: #6b7280;
            font-size: 14px;
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
        input[type="email"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        select:focus {
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
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .password-help {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>ABDM Gateway</h1>
            <p>Create Account</p>
        </div>

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

        <form method="post" action="/auth/register">
            <?= csrf_field() ?>

            <div class="form-group">
                <label for="hospital_id">Hospital</label>
                <select id="hospital_id" name="hospital_id" required>
                    <option value="">-- Select Hospital --</option>
                    <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= $hospital->id ?>">
                            <?= esc($hospital->hospital_name) ?> (<?= esc($hospital->hfr_id) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
                <div class="password-help">Minimum 6 characters</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm password" required>
            </div>

            <button type="submit">Create Account</button>

            <div class="login-link">
                Already have an account? <a href="/auth/login">Login here</a>
            </div>
        </form>
    </div>
</body>
</html>
