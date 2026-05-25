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
    : '/SRMT/public/assets/images/icons/SyncRide.png';

$navClass = static fn(string $id): string => $id === $active ? 'active' : '';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#ffffff">
    <title><?= View::e($title) ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

    <style>
        :root {
            --font-primary: 'Inter', sans-serif;
            --font-display: 'Poppins', sans-serif;
            --bg-body: #f3f4f6;
            --bg-card: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --primary-accent: #4f46e5;
            --border-color: #e5e7eb;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --radius-md: 16px;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        [data-bs-theme="dark"] {
            --bg-body: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f9fafb;
            --text-muted: #94a3b8;
            --primary-accent: #6366f1;
            --border-color: #334155;
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
    </style>
    <?= $extraHead ?>
</head>
<body>
    <header class="app-header">
        <img src="/SRMT/public/assets/images/icons/SyncRide.png" alt="SyncRide" class="brand-logo" id="driver-logo">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-link text-muted p-0 border-0" id="theme-toggle">
                <i class="bi bi-moon-stars-fill fs-5" id="theme-icon"></i>
            </button>
            <img src="<?= View::e($userPhotoSrc) ?>" class="user-avatar shadow-sm" alt="">
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
            const logoDark  = '/SRMT/public/assets/images/icons/SyncRide.png';
            const logoLight = '/SRMT/public/assets/images/icons/Syncridewhite.png';

            function applyTheme(t) {
                html.setAttribute('data-bs-theme', t);
                icon.className = t === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
                if (logo) logo.src = t === 'dark' ? logoLight : logoDark;
            }
            applyTheme(localStorage.getItem('theme') || 'light');
            toggle.addEventListener('click', () => {
                const next = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
                localStorage.setItem('theme', next);
                applyTheme(next);
            });
        })();
    </script>
    <?= $extraScripts ?>
</body>
</html>
