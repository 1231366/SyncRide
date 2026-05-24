<?php
/**
 * Driver layout.
 *
 * Placeholder — the rich chrome from `public/driver/index.php` will be
 * lifted here verbatim when that page is migrated (Phase 5).
 *
 * Variables: $title, $content, $active, $extraHead, $extraScripts.
 */

use App\Http\View;

/** @var string $content */
$title        = $title        ?? 'SyncRide — Driver';
$active       = $active       ?? '';
$extraHead    = $extraHead    ?? '';
$extraScripts = $extraScripts ?? '';

$userName = isset($_SESSION['name']) ? explode(' ', (string) $_SESSION['name'])[0] : 'Driver';

$navClass = static fn(string $id): string => $id === $active ? 'active' : '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= View::e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #0b1020; color: #fff; margin: 0; padding-bottom: 96px; }
        .app-header { padding: 24px 20px 12px; display: flex; justify-content: space-between; align-items: center; }
        .app-header h1 { font-size: 18px; font-weight: 800; margin: 0; }
        .bottom-nav { position: fixed; bottom: calc(12px + var(--safe-bottom)); left: 12px; right: 12px;
            background: rgba(18,18,18,0.95); backdrop-filter: blur(20px); border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1); display: flex; justify-content: space-around; padding: 10px; z-index: 50; }
        .bottom-nav a { color: #888; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 2px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .bottom-nav a.active { color: #3b82f6; }
    </style>
    <?= $extraHead ?>
</head>
<body>
    <header class="app-header">
        <div>
            <h1>Hi, <?= View::e($userName) ?></h1>
            <p style="font-size:10px;color:#888;margin:0;text-transform:uppercase;letter-spacing:2px;">Driver</p>
        </div>
        <a href="/SRMT/public/auth/logout.php" style="color:#888;"><i data-lucide="log-out"></i></a>
    </header>

    <main><?= $content ?></main>

    <nav class="bottom-nav">
        <a href="/SRMT/public/driver/"           class="<?= $navClass('dashboard') ?>"><i data-lucide="home" style="width:20px;height:20px;"></i>Home</a>
        <a href="/SRMT/public/driver/agenda.php" class="<?= $navClass('agenda') ?>"   ><i data-lucide="calendar" style="width:20px;height:20px;"></i>Agenda</a>
        <a href="/SRMT/public/driver/stats.php"  class="<?= $navClass('stats') ?>"    ><i data-lucide="bar-chart-3" style="width:20px;height:20px;"></i>Stats</a>
    </nav>

    <script>lucide.createIcons();</script>
    <?= $extraScripts ?>
</body>
</html>
