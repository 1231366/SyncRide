<?php
/**
 * @var array<string,mixed>|null $invite
 * @var string                   $token
 * @var string|null              $error
 */
use App\Http\View;
use App\Models\User;

$roleLabel = $invite !== null ? match ((int) $invite['role']) {
    User::ROLE_DRIVER  => t('invite.role_driver'),
    User::ROLE_PARTNER => t('invite.role_partner'),
    User::ROLE_ADMIN   => t('invite.role_admin'),
    default            => 'User',
} : '';

$errors = [
    'invalid' => t('invite.err_invalid'),
    'missing' => t('invite.err_missing'),
    'email'   => t('invite.err_email'),
    'weak'    => t('invite.err_weak'),
    'exists'  => t('invite.err_exists'),
];
$errMsg = $error !== null ? ($errors[$error] ?? '') : '';
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="theme-color" content="#f1f5f9">
<title><?= t('invite.title') ?> — SyncRide</title>
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
        position: relative; z-index: 10; width: 100%; max-width: 420px;
        background: rgba(255,255,255,0.72); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255,255,255,0.8); border-radius: 28px; padding: 38px 32px;
        box-shadow: 0 24px 64px rgba(15,23,42,0.12), 0 2px 8px rgba(15,23,42,0.04);
    }
    .header { text-align: center; margin-bottom: 24px; }
    .header img { width: 76px; margin-bottom: 12px; filter: drop-shadow(0 6px 12px rgba(37,99,235,0.18)); }
    .brand { font-size: 21px; font-weight: 800; letter-spacing: -0.5px; }
    .brand span { color: #2563eb; }
    .sub { font-size: 12px; color: #64748b; font-weight: 600; margin-top: 4px; }
    .role-chip {
        display: flex; align-items: center; justify-content: center; gap: 6px;
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
        background: rgba(37,99,235,0.10); color: #2563eb; border: 1px solid rgba(37,99,235,0.2);
        padding: 8px 12px; border-radius: 12px; margin-bottom: 22px;
    }
    label { display: block; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #64748b; margin-bottom: 6px; }
    .field { margin-bottom: 15px; }
    input {
        width: 100%; height: 48px; border-radius: 14px; padding: 0 16px;
        background: rgba(255,255,255,0.7); border: 1.5px solid rgba(15,23,42,0.10);
        color: #0f172a; font-size: 14px; font-weight: 600; font-family: inherit; transition: all .2s;
    }
    input:focus { outline: none; border-color: #2563eb; background: #fff; box-shadow: 0 4px 16px rgba(37,99,235,0.12); }
    .btn {
        width: 100%; height: 50px; border: none; border-radius: 14px; margin-top: 8px;
        background: #2563eb; color: #fff; font-size: 15px; font-weight: 700; cursor: pointer;
        transition: all .2s; font-family: inherit; box-shadow: 0 10px 24px rgba(37,99,235,0.28);
    }
    .btn:hover { background: #1d4ed8; transform: translateY(-1px); }
    .alert {
        background: rgba(239,68,68,0.10); border: 1px solid rgba(239,68,68,0.25); color: #dc2626;
        padding: 11px 14px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-bottom: 18px;
    }
    .invalid-box { text-align: center; padding: 16px 0; }
    .invalid-box i { font-size: 48px; color: #ef4444; margin-bottom: 14px; display: block; }
    .invalid-box p { color: #64748b; font-size: 14px; font-weight: 600; }
</style>
</head>
<body>
<div class="accent accent-1"></div>
<div class="accent accent-2"></div>

<div class="card">
    <div class="header">
        <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide">
        <div class="brand">SyncRide<span> OS</span></div>
        <div class="sub"><?= t('invite.subtitle') ?></div>
    </div>

    <?php if ($invite === null): ?>
        <div class="invalid-box">
            <i class="bi bi-x-octagon-fill"></i>
            <p><?= t('invite.invalid_link') ?></p>
        </div>
    <?php else: ?>
        <div class="role-chip"><i class="bi bi-person-badge"></i> <?= View::e($roleLabel) ?></div>

        <?php if ($errMsg !== ''): ?>
            <div class="alert"><?= View::e($errMsg) ?></div>
        <?php endif; ?>

        <form method="POST" action="/SRMT/public/invite-complete.php">
            <input type="hidden" name="token" value="<?= View::e($token) ?>">
            <div class="field">
                <label><?= t('invite.full_name') ?></label>
                <input type="text" name="name" required autofocus>
            </div>
            <div class="field">
                <label><?= t('invite.email') ?></label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label><?= t('invite.phone') ?></label>
                <input type="text" name="phone">
            </div>
            <div class="field">
                <label><?= t('invite.password') ?></label>
                <input type="password" name="password" required minlength="6">
            </div>
            <button type="submit" class="btn"><?= t('invite.finish') ?></button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
