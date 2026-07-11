<?php
/** @var string|null $errorCode */
$errorCode = $errorCode ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f1f5f9">
    <title>Sign in — SyncRide</title>
    <link rel="icon" type="image/png" href="/SRMT/public/assets/images/icons/Syncride.png"/>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
            padding: 20px;
            background: radial-gradient(circle at 50% -10%, #bfdbfe 0%, #f1f5f9 65%);
            background-attachment: fixed;
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }
        /* Soft floating accents */
        .accent { position: fixed; border-radius: 50%; filter: blur(8px); z-index: 0; pointer-events: none; }
        .accent-1 { width: 320px; height: 320px; top: -90px; left: -80px; background: rgba(37,99,235,0.10); }
        .accent-2 { width: 380px; height: 380px; bottom: -120px; right: -110px; background: rgba(37,99,235,0.07); }

        .card {
            position: relative; z-index: 10;
            width: 100%; max-width: 410px;
            background: rgba(255,255,255,0.72);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.8);
            border-radius: 28px; padding: 40px 34px;
            box-shadow: 0 24px 64px rgba(15,23,42,0.12), 0 2px 8px rgba(15,23,42,0.04);
        }
        .header { text-align: center; margin-bottom: 32px; }
        .header img { width: 88px; margin-bottom: 14px; filter: drop-shadow(0 6px 12px rgba(37,99,235,0.18)); }
        .brand { font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
        .brand span { color: #2563eb; }
        .welcome { font-size: 14px; font-weight: 700; margin-top: 18px; }
        .welcome-sub { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 2px; }

        .field { margin-bottom: 16px; }
        .field label { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #64748b; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-wrap i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; transition: color .2s; }
        .input-wrap input {
            width: 100%; height: 50px; border-radius: 14px; padding: 0 44px 0 44px;
            background: rgba(255,255,255,0.7); border: 1.5px solid rgba(15,23,42,0.10);
            color: #0f172a; font-size: 14px; font-weight: 600; font-family: inherit;
            transition: all .2s;
        }
        .input-wrap input:focus {
            outline: none; border-color: #2563eb; background: #fff;
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        .input-wrap input:focus + .toggle-pass,
        .input-wrap input:focus ~ i { color: #2563eb; }
        .toggle-pass {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            color: #94a3b8; cursor: pointer; font-size: 16px; background: none; border: none;
        }
        .remember { display: flex; align-items: center; gap: 8px; margin: 4px 2px 24px; }
        .remember input { width: 16px; height: 16px; accent-color: #2563eb; cursor: pointer; }
        .remember label { font-size: 13px; color: #475569; font-weight: 600; cursor: pointer; user-select: none; }
        .btn {
            width: 100%; height: 52px; border: none; border-radius: 14px;
            background: #2563eb; color: #fff; font-size: 15px; font-weight: 700;
            cursor: pointer; transition: all .2s; font-family: inherit;
            box-shadow: 0 10px 24px rgba(37,99,235,0.28);
        }
        .btn:hover { background: #1d4ed8; transform: translateY(-1px); box-shadow: 0 14px 30px rgba(37,99,235,0.34); }
        .btn:active { transform: translateY(0); }
        #toast-container > div { box-shadow: 0 10px 24px rgba(0,0,0,0.12) !important; opacity: 1 !important; border-radius: 12px !important; }
        @media (max-width: 480px) { .card { padding: 34px 24px; } }
    </style>
</head>
<body>
    <div class="accent accent-1"></div>
    <div class="accent accent-2"></div>

    <div class="card">
        <div class="header">
            <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide">
            <div class="brand">SyncRide<span> OS</span></div>
            <div class="welcome">Welcome back</div>
            <div class="welcome-sub">Sign in to continue</div>
        </div>

        <form method="POST" action="/SRMT/public/auth/login.php">
            <div class="field">
                <label>Email</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope-fill"></i>
                    <input type="email" name="email" placeholder="you@example.com" required autofocus>
                </div>
            </div>

            <div class="field">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="pass" id="passInput" placeholder="••••••••" required>
                    <button type="button" class="toggle-pass" onclick="togglePass()"><i class="bi bi-eye" id="passIcon"></i></button>
                </div>
            </div>

            <div class="remember">
                <input type="checkbox" name="remember" id="rememberBox" checked>
                <label for="rememberBox">Keep me signed in</label>
            </div>

            <button type="submit" class="btn">Sign in</button>
        </form>

        <div style="text-align:center;margin-top:20px;font-size:13px;color:#64748b;font-weight:500;">
            Ainda não tens conta?
            <a href="/SRMT/public/auth/register.php" style="color:#2563eb;font-weight:700;text-decoration:none;">
                Criar conta grátis →
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        function togglePass() {
            const inp = document.getElementById('passInput');
            const ico = document.getElementById('passIcon');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            ico.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
    </script>

    <?php if ($errorCode !== null): ?>
    <script>
        toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-center', timeOut: '5000' };
        const messages = {
            invalid_credentials: 'Invalid email or password.',
            user_not_found:      'Account not found.',
            empty_fields:        'Please fill in every field.',
            invalid_role:        'Your account has no assigned role.',
            server_error:        'Server error. Please try again.'
        };
        toastr.error(messages[<?= json_encode($errorCode) ?>] || 'Something went wrong.', 'Sign-in');
    </script>
    <?php endif; ?>
</body>
</html>
