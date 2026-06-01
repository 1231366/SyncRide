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
            padding-bottom: calc(80px + var(--safe-bottom));
            margin: 0;
        }
        .app-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: calc(15px + var(--safe-top)) 20px 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1020;
        }
        .brand-logo { height: 30px; width: auto; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); }
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%;
            height: calc(70px + var(--safe-bottom));
            background-color: var(--bg-card); border-top: 1px solid var(--border-color);
            display: flex; justify-content: space-around; align-items: flex-start;
            z-index: 1030; padding-bottom: var(--safe-bottom); padding-top: 10px;
        }
        .nav-item-mobile {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: var(--text-muted); text-decoration: none; font-size: 0.75rem; font-weight: 500;
            width: 100%; height: 50px; transition: color 0.2s;
        }
        .nav-item-mobile i { font-size: 1.5rem; margin-bottom: 4px; }
        .nav-item-mobile.active { color: var(--primary-accent); }
        body { background-color: var(--bg-body); color: var(--text-main); }
    </style>
    <?= $extraHead ?>
</head>
<body>
    <header class="app-header">
        <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide" class="brand-logo" id="driver-logo">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-link text-muted p-0 border-0" id="theme-toggle">
                <i class="bi bi-moon-stars-fill fs-5" id="theme-icon"></i>
            </button>
            <img src="<?= View::e($userPhotoSrc) ?>" class="user-avatar shadow-sm" alt="" style="cursor:pointer" onclick="openChangePassword()" title="<?= t('pwd.title') ?>">
        </div>
    </header>

    <main class="container-fluid px-3 pt-3">
        <?= $content ?>
    </main>

    <nav class="bottom-nav">
        <a href="/SRMT/public/driver/"           class="nav-item-mobile <?= $navClass('dashboard') ?>"><i class="bi bi-car-front-fill"></i><span>Rides</span></a>
        <a href="/SRMT/public/driver/agenda.php" class="nav-item-mobile <?= $navClass('agenda') ?>">  <i class="bi bi-calendar3"></i><span>Agenda</span></a>
        <a href="/SRMT/public/driver/stats.php"  class="nav-item-mobile <?= $navClass('stats') ?>">   <i class="bi bi-bar-chart-fill"></i><span>Stats</span></a>
        <a href="/SRMT/public/auth/logout.php"   class="nav-item-mobile text-danger"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
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
    <?php include __DIR__ . '/_change_password.php'; ?>
</body>
</html>
