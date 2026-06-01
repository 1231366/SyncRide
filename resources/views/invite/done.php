<?php
/** @var string $name */
use App\Http\View;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#f1f5f9">
<title><?= t('invite.done_title') ?> — SyncRide</title>
<link rel="icon" type="image/png" href="/SRMT/public/assets/images/icons/Syncride.png"/>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        background: radial-gradient(circle at 50% -10%, #bfdbfe 0%, #f1f5f9 65%);
        background-attachment: fixed; color: #0f172a; -webkit-font-smoothing: antialiased;
    }
    .accent { position: fixed; border-radius: 50%; filter: blur(8px); z-index: 0; pointer-events: none; }
    .accent-1 { width: 320px; height: 320px; top: -90px; left: -80px; background: rgba(37,99,235,0.10); }
    .accent-2 { width: 380px; height: 380px; bottom: -120px; right: -110px; background: rgba(37,99,235,0.07); }
    .card {
        position: relative; z-index: 10; width: 100%; max-width: 420px; text-align: center;
        background: rgba(255,255,255,0.72); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255,255,255,0.8); border-radius: 28px; padding: 46px 32px;
        box-shadow: 0 24px 64px rgba(15,23,42,0.12), 0 2px 8px rgba(15,23,42,0.04);
    }
    .check { width: 76px; height: 76px; border-radius: 50%; background: rgba(16,185,129,0.12);
        display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
    .check i { font-size: 40px; color: #10b981; }
    h1 { font-size: 21px; font-weight: 800; margin-bottom: 8px; }
    p { color: #64748b; font-size: 14px; font-weight: 600; margin-bottom: 26px; }
    a.btn {
        display: inline-block; text-decoration: none; padding: 14px 30px; border-radius: 14px;
        background: #2563eb; color: #fff; font-size: 14px; font-weight: 700;
        box-shadow: 0 10px 24px rgba(37,99,235,0.28); transition: all .2s;
    }
    a.btn:hover { background: #1d4ed8; transform: translateY(-1px); }
</style>
</head>
<body>
<div class="accent accent-1"></div>
<div class="accent accent-2"></div>

<div class="card">
    <div class="check"><i class="bi bi-check-lg"></i></div>
    <h1><?= t('invite.done_title') ?>, <?= View::e($name) ?>!</h1>
    <p><?= t('invite.done_desc') ?></p>
    <a class="btn" href="/SRMT/public/"><?= t('invite.go_login') ?></a>
</div>
</body>
</html>
