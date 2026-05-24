<?php
/** @var string|null $errorCode */
$errorCode = $errorCode ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — SyncRide</title>
    <link rel="icon" type="image/png" href="/SRMT/public/assets/images/icons/Syncride.png"/>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" type="text/css" href="/SRMT/public/assets/vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="/SRMT/public/assets/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="/SRMT/public/assets/fonts/iconic/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" type="text/css" href="/SRMT/public/assets/css/util.css">
    <link rel="stylesheet" type="text/css" href="/SRMT/public/assets/css/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

    <style>
        * { box-sizing: border-box; }
        body, html { height: 100%; font-family: 'Poppins', sans-serif !important; }
        .limiter {
            width: 100%; margin: 0 auto; display: flex;
            justify-content: center; align-items: center; height: 100vh;
            background: linear-gradient(135deg, #00C6FF 0%, #0072FF 100%);
            position: relative; overflow: hidden;
        }
        .bg-shape { position: absolute; background: rgba(255,255,255,0.1); border-radius: 50%; backdrop-filter: blur(5px); }
        .shape-1 { width: 300px; height: 300px; top: -50px; left: -50px; }
        .shape-2 { width: 400px; height: 400px; bottom: -100px; right: -100px; background: rgba(255,255,255,0.05); }
        .wrap-login100 {
            width: 420px; background: #fff; border-radius: 20px;
            padding: 50px 40px; box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative; z-index: 10;
        }
        .login-header { text-align: center; margin-bottom: 40px; }
        .login-header img { width: 120px; margin-bottom: 15px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }
        .login-header h3 { font-size: 24px; font-weight: 700; color: #333; margin: 0; }
        .login-header p { font-size: 14px; color: #888; margin-top: 5px; }
        .wrap-input100 {
            width: 100%; position: relative; background-color: #f7f9fc;
            border-radius: 50px; margin-bottom: 25px;
            border: 2px solid transparent; transition: all 0.3s;
        }
        .wrap-input100:focus-within {
            background-color: #fff; border-color: #0072FF;
            box-shadow: 0 5px 15px rgba(0,114,255,0.15);
        }
        .input100 {
            font-family: 'Poppins', sans-serif; font-size: 15px; color: #333;
            display: block; width: 100%; height: 55px;
            background: transparent; padding: 0 30px 0 50px;
        }
        .input-icon {
            position: absolute; left: 20px; top: 50%;
            transform: translateY(-50%); font-size: 18px; color: #aaa;
            transition: color 0.3s;
        }
        .wrap-input100:focus-within .input-icon { color: #0072FF; }
        .btn-show-pass { top: 50%; transform: translateY(-50%); right: 20px; color: #aaa; cursor: pointer; }
        .focus-input100 { display: none; }
        .remember-me-wrapper { padding: 0 10px 25px 10px; display: flex; align-items: center; }
        .remember-check input { accent-color: #0072FF; width: 16px; height: 16px; cursor: pointer; }
        .remember-check label { margin-left: 8px; font-size: 14px; color: #666; cursor: pointer; user-select: none; }
        .container-login100-form-btn { width: 100%; display: flex; justify-content: center; }
        .login100-form-btn {
            font-family: 'Poppins', sans-serif; font-size: 16px; color: #fff;
            text-transform: uppercase; font-weight: 600; letter-spacing: 1px;
            width: 100%; height: 55px; border-radius: 50px;
            background: linear-gradient(90deg, #00C6FF 0%, #0072FF 100%);
            border: none; cursor: pointer;
            box-shadow: 0 10px 20px rgba(0,114,255,0.3);
            transition: all 0.3s;
        }
        .login100-form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(0,114,255,0.4);
            background: linear-gradient(90deg, #0072FF 0%, #00C6FF 100%);
        }
        @media (max-width: 576px) {
            .wrap-login100 { padding: 40px 25px; width: 90%; }
            .shape-1, .shape-2 { display: none; }
        }
        #toast-container > div {
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            opacity: 1 !important; border-radius: 10px !important;
        }
    </style>
</head>
<body>
    <div class="limiter">
        <div class="bg-shape shape-1"></div>
        <div class="bg-shape shape-2"></div>

        <div class="container-login100" style="min-height: auto; background: transparent;">
            <div class="wrap-login100">
                <form class="login100-form validate-form" method="POST" action="/SRMT/public/auth/login.php">

                    <div class="login-header">
                        <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide">
                        <h3>Welcome back</h3>
                        <p>Sign in to continue</p>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Invalid email">
                        <i class="fa fa-envelope input-icon"></i>
                        <input class="input100" type="text" name="email" placeholder="Email" required>
                    </div>

                    <div class="wrap-input100 validate-input" data-validate="Enter your password">
                        <i class="fa fa-lock input-icon"></i>
                        <span class="btn-show-pass"><i class="zmdi zmdi-eye"></i></span>
                        <input class="input100" type="password" name="pass" placeholder="Password" required>
                    </div>

                    <div class="remember-me-wrapper">
                        <div class="remember-check">
                            <input type="checkbox" name="remember" id="rememberBox">
                            <label for="rememberBox">Keep me signed in</label>
                        </div>
                    </div>

                    <div class="container-login100-form-btn">
                        <button class="login100-form-btn">Sign in</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script src="/SRMT/public/assets/vendor/jquery/jquery-3.2.1.min.js"></script>
    <script src="/SRMT/public/assets/vendor/bootstrap/js/popper.js"></script>
    <script src="/SRMT/public/assets/vendor/bootstrap/js/bootstrap.min.js"></script>
    <script src="/SRMT/public/assets/js/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <?php if ($errorCode !== null): ?>
    <script>
        toastr.options = {
            "closeButton": true, "progressBar": true,
            "positionClass": "toast-top-center", "timeOut": "5000"
        };
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
