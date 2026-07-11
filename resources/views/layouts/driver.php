<?php
/**
 * Driver layout — Bootstrap 5 + light/dark + Inter/Poppins.
 *
 * Variables: $title, $content, $active, $extraHead, $extraScripts.
 */
use App\Http\View;

/** @var string $content */
$title        = $title        ?? 'SyncRide — Driver';
$active       = $active       ?? '';
$extraHead    = $extraHead    ?? '';
$extraScripts = $extraScripts ?? '';

$userName  = isset($_SESSION['name'])  ? explode(' ', (string) $_SESSION['name'])[0] : 'Driver';
$userPhoto = $_SESSION['profile_photo_path'] ?? null;
$userPhotoSrc = $userPhoto
    ? '/SRMT/' . ltrim((string) $userPhoto, '/')
    : '/SRMT/public/assets/images/icons/Syncride.png';

$navClass = static fn(string $id): string => $id === $active ? 'active' : '';
$headerLabel = [
    'agenda'   => t('drv.hdr_agenda'),
    'stats'    => t('drv.hdr_stats'),
    'settings' => t('drv.hdr_settings'),
][$active] ?? 'SyncRide';
?><!DOCTYPE html>
<html lang="en" translate="no" data-bs-theme="light" style="background:#f8fafc;">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title><?= View::e($title) ?></title>
    <meta name="csrf-token" content="<?= \App\Support\Session::csrfToken() ?>">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        :root {
            --font-primary: 'Inter', sans-serif;
            --font-display: 'Poppins', sans-serif;
            --bg-body:        #f8fafc;
            --bg-card:        #ffffff;
            --bg-raised:      #f1f5f9;
            --text-main:      #0f172a;
            --text-muted:     #475569;
            --text-faint:     #94a3b8;
            --primary-accent: #2563eb;
            --accent-soft:    #eff6ff;
            --border-color:   #e2e8f0;
            --shadow-sm:      0 1px 3px rgb(0 0 0 / .08);
            --radius-md:      14px;
            --safe-top:    env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        [data-bs-theme="dark"] {
            --bg-body:        #0f172a;
            --bg-card:        #1e293b;
            --bg-raised:      #293548;
            --text-main:      #f1f5f9;
            --text-muted:     #94a3b8;
            --text-faint:     #64748b;
            --primary-accent: #3b82f6;
            --accent-soft:    rgba(59,130,246,.12);
            --border-color:   #334155;
            --shadow-sm:      0 1px 3px rgb(0 0 0 / .4);
        }
        body {
            font-family: var(--font-primary);
            background-color: var(--bg-body);
            color: var(--text-main);
            padding-bottom: calc(94px + var(--safe-bottom));
            margin: 0;
            -webkit-tap-highlight-color: transparent;
        }
        .app-header {
            position: sticky; top: 0; z-index: 1020;
            display: flex; justify-content: space-between; align-items: center;
            padding: calc(16px + var(--safe-top)) 20px 12px;
            background: var(--bg-body);
            background: color-mix(in srgb, var(--bg-body) 82%, transparent);
            backdrop-filter: blur(16px) saturate(180%); -webkit-backdrop-filter: blur(16px) saturate(180%);
        }
        .hdr-title { font-family: var(--font-display); font-size: 1.35rem; font-weight: 800; color: var(--text-main); letter-spacing: -.01em; }
        .brand-logo { height: 28px; width: auto; }
        .theme-btn {
            width: 38px; height: 38px; border-radius: 50%;
            background: var(--bg-raised); border: 1px solid var(--border-color);
            color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); display: block; }
        .user-avatar-default {
            display: flex; align-items: center; justify-content: center;
            background: var(--accent-soft); color: var(--primary-accent); font-size: 1.1rem;
        }
        .bottom-nav {
            position: fixed; bottom: calc(12px + var(--safe-bottom)); left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 28px); max-width: 440px; height: 62px;
            background: rgba(255,255,255,0.82);
            backdrop-filter: blur(22px) saturate(180%); -webkit-backdrop-filter: blur(22px) saturate(180%);
            border: 1px solid rgba(0,0,0,0.06); border-radius: 24px;
            box-shadow: 0 10px 34px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.06);
            display: flex; align-items: stretch; gap: 2px; padding: 6px; z-index: 1030;
        }
        [data-bs-theme="dark"] .bottom-nav {
            background: rgba(20,28,46,0.86); border-color: rgba(255,255,255,0.08);
            box-shadow: 0 10px 34px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
        }
        .nav-item-mobile {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px; border-radius: 18px;
            color: var(--text-faint); text-decoration: none;
            font-size: .64rem; font-weight: 600; transition: color .2s, background .2s;
        }
        .nav-item-mobile i { font-size: 1.25rem; }
        .nav-item-mobile:active { transform: scale(.94); }
        .nav-item-mobile.active { color: var(--primary-accent); background: var(--accent-soft); }
    </style>
    <?= $extraHead ?>
</head>
<body>
    <header class="app-header">
        <div class="hdr-title" id="driver-logo"><?= View::e($headerLabel) ?></div>
        <div class="d-flex align-items-center gap-2">
            <button class="theme-btn" id="theme-toggle"><i class="bi bi-moon-stars-fill" id="theme-icon"></i></button>
            <a href="/SRMT/public/driver/settings.php" aria-label="Definições">
                <?php if ($userPhoto): ?>
                    <img src="<?= View::e($userPhotoSrc) ?>" class="user-avatar" alt="">
                <?php else: ?>
                    <span class="user-avatar user-avatar-default"><i class="bi bi-person-fill"></i></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <main class="container-fluid px-3 pt-3">
        <?= $content ?>
    </main>

    <nav class="bottom-nav">
        <a href="/SRMT/public/driver/"            class="nav-item-mobile <?= $navClass('dashboard') ?>"><i class="bi bi-car-front-fill"></i><span><?= t('drv.nav_rides') ?></span></a>
        <a href="/SRMT/public/driver/agenda.php"  class="nav-item-mobile <?= $navClass('agenda') ?>"><i class="bi bi-calendar3"></i><span><?= t('drv.nav_agenda') ?></span></a>
        <a href="/SRMT/public/driver/stats.php"   class="nav-item-mobile <?= $navClass('stats') ?>"><i class="bi bi-bar-chart-fill"></i><span><?= t('drv.nav_stats') ?></span></a>
        <a href="/SRMT/public/driver/settings.php" class="nav-item-mobile <?= $navClass('settings') ?>"><i class="bi bi-gear-fill"></i><span><?= t('drv.nav_settings') ?></span></a>
    </nav>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            const html = document.documentElement;
            const toggle = document.getElementById('theme-toggle');
            const icon   = document.getElementById('theme-icon');
            const logo   = document.getElementById('driver-logo');
            const logoDark  = '/SRMT/public/assets/images/icons/Syncride.png';
            const logoLight = '/SRMT/public/assets/images/icons/Syncridewhite.png';

            function applyTheme(t) {
                html.setAttribute('data-bs-theme', t);
                icon.className = t === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
                if (logo) logo.src = t === 'dark' ? logoLight : logoDark;
                document.body.style.backgroundColor = '';
            }
            applyTheme(localStorage.getItem('theme') || 'light');
            toggle.addEventListener('click', () => {
                const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                localStorage.setItem('theme', next);
                applyTheme(next);
            });
        })();
    </script>
    <?php include __DIR__ . '/_csrf.php'; ?>
    <?= $extraScripts ?>
    <?php include __DIR__ . '/_sr_toast.php'; ?>
    <?php include __DIR__ . '/_change_password.php'; ?>
    <script>
    // Show instant loading overlay when navigating between driver pages (Capacitor only)
    (function () {
        if (!window.Capacitor?.isNativePlatform()) return;
        const overlay = document.createElement('div');
        overlay.id = 'nav-loading';
        overlay.style.cssText = 'display:none;position:fixed;inset:0;z-index:99999;background:var(--bg,#f8fafc);align-items:center;justify-content:center;flex-direction:column;gap:14px;';
        overlay.innerHTML = '<div style="width:36px;height:36px;border:3px solid #e2e8f0;border-top-color:#2563eb;border-radius:50%;animation:spin .7s linear infinite;"></div>'
            + '<style>@keyframes spin{to{transform:rotate(360deg)}}</style>';
        document.body.appendChild(overlay);
        document.querySelectorAll('.bottom-nav a[href]').forEach(a => {
            a.addEventListener('click', function (e) {
                if (this.classList.contains('active')) return;
                overlay.style.display = 'flex';
            });
        });
        window.addEventListener('pageshow', () => { overlay.style.display = 'none'; });
    })();
    </script>
</body>
</html>
