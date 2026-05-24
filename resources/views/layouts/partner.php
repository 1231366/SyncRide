<?php
/**
 * Partner layout.
 *
 * Placeholder — the rich chrome from `public/partner/index.php` will be
 * lifted here verbatim when that page is migrated (Phase 5).
 *
 * Variables: $title, $content, $active, $extraHead, $extraScripts.
 */

use App\Http\View;

/** @var string $content */
$title        = $title        ?? 'SyncRide — Partner Portal';
$active       = $active       ?? '';
$extraHead    = $extraHead    ?? '';
$extraScripts = $extraScripts ?? '';

$userName = isset($_SESSION['name']) ? explode(' ', (string) $_SESSION['name'])[0] : 'Partner';

$navClass = static fn(string $id): string => $id === $active ? 'active' : '';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f5f7fb; color: #1a1d26; margin: 0; }
        .navbar { background: #fff; border-bottom: 1px solid #e8eaf0; padding: 12px 20px; }
        .navbar-brand { font-weight: 700; font-size: 18px; }
        .container { padding: 24px 20px; max-width: 1200px; margin: 0 auto; }
        .bottom-nav { display: none; }
        @media (max-width: 768px) {
            .bottom-nav { display: flex; position: fixed; bottom: 0; left: 0; right: 0; background: #fff; border-top: 1px solid #e8eaf0; padding: 10px; justify-content: space-around; }
            .bottom-nav a { color: #6c7080; text-decoration: none; display: flex; flex-direction: column; align-items: center; gap: 2px; font-size: 11px; font-weight: 600; }
            .bottom-nav a.active { color: #3b82f6; }
            .container { padding-bottom: 90px; }
        }
    </style>
    <?= $extraHead ?>
</head>
<body>
    <nav class="navbar d-flex justify-content-between align-items-center">
        <span class="navbar-brand">SyncRide <span style="color:#3b82f6;">Partner</span></span>
        <span style="font-size:14px;color:#6c7080;">Hi, <?= View::e($userName) ?> <a href="/SRMT/public/auth/logout.php" style="margin-left:12px;color:#dc2626;text-decoration:none;">Logout</a></span>
    </nav>

    <main class="container"><?= $content ?></main>

    <nav class="bottom-nav">
        <a href="/SRMT/public/partner/" class="<?= $navClass('dashboard') ?>"><i data-lucide="home" style="width:20px;height:20px;"></i>Home</a>
    </nav>

    <script>lucide.createIcons();</script>
    <?= $extraScripts ?>
</body>
</html>
