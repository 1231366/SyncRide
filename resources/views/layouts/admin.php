<?php
/**
 * Admin layout — SyncRide OS.
 * Mobile: 5-item pill + More overlay.
 * Desktop (md+): expanded scrollable pill with all items.
 *
 * Variables:
 *   string $title, $content, $active, $extraHead, $extraScripts
 */

use App\Http\View;

$title        = $title        ?? 'SyncRide OS';
$active       = $active       ?? '';
$extraHead    = $extraHead    ?? '';
$extraScripts = $extraScripts ?? '';

$userName  = isset($_SESSION['name']) ? explode(' ', (string) $_SESSION['name'])[0] : 'Admin';
$rawPhoto  = $_SESSION['profile_photo_path'] ?? null;
if ($rawPhoto !== null && $rawPhoto !== '') {
    $rawPhoto = str_replace('Includes/dist/pages/', '', $rawPhoto);
    $userPhoto = str_starts_with($rawPhoto, '/') || str_starts_with($rawPhoto, 'http')
        ? $rawPhoto
        : '/SRMT/public/' . $rawPhoto;
} else {
    $userPhoto = '';
}
$initial        = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
$svgAvatar      = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20" fill="#2563eb"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="17" font-weight="bold" font-family="system-ui">' . htmlspecialchars($initial) . '</text></svg>';
$avatarFallback = 'data:image/svg+xml;base64,' . base64_encode($svgAvatar);

$navClass = static function (string $id) use ($active): string {
    return $id === $active ? 'sr-nav-active' : '';
};
?><!DOCTYPE html>
<html lang="en" translate="no" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" id="themeColor" content="#f1f5f9">
<title><?= View::e($title) ?></title>
<meta name="csrf-token" content="<?= \App\Support\Session::csrfToken() ?>">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root { --safe-bottom: env(safe-area-inset-bottom, 0px); }

    html, body { height: 100%; overflow: hidden; }
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        -webkit-font-smoothing: antialiased;
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .bg-main { height: 100%; }
    #app-container { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }

    /* ── Page background ────────────────────────────────────── */
    .bg-main {
        background: radial-gradient(circle at 50% -10%, #bfdbfe 0%, #f1f5f9 65%);
        background-attachment: fixed;
        min-height: 100vh;
    }
    [data-theme="dark"] .bg-main {
        background: radial-gradient(circle at 50% -10%, #1e3a8a 0%, #020617 70%);
        background-color: #020617;
    }
    [data-theme="dark"] body { background-color: #020617; color: #f1f5f9; }

    /* ── Sticky app header: "Hi" + hamburger stay reachable; condenses
          (thinner, animated) as soon as the page is scrolled. ─────────── */
    #sr-app-header {
        position: sticky;
        top: 0;
        z-index: 40;
        border-bottom: 1px solid transparent;
        transition: padding .22s ease, background .22s ease, border-color .22s ease;
    }
    #sr-app-header .sr-hdr-avatar { transition: width .22s ease, height .22s ease; }
    #sr-app-header .sr-hdr-sub   { transition: opacity .18s ease; }
    #app-container.scrolled #sr-app-header {
        padding-top: calc(env(safe-area-inset-top, 0px) + 8px);
        padding-bottom: 8px;
        background: rgba(241,245,249,0.82);
        backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
        border-bottom-color: rgba(0,0,0,0.06);
    }
    [data-theme="dark"] #app-container.scrolled #sr-app-header {
        background: rgba(2,6,23,0.82);
        border-bottom-color: rgba(255,255,255,0.06);
    }
    #app-container.scrolled #sr-app-header .sr-hdr-avatar { width: 32px; height: 32px; }
    #app-container.scrolled #sr-app-header .sr-hdr-sub   { opacity: 0; }

    /* ── Glass ──────────────────────────────────────────────── */
    .glass {
        background: rgba(255,255,255,0.62);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(0,0,0,0.08);
    }
    [data-theme="dark"] .glass {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.10);
    }

    /* ══════════════════════════════════════════════════════════
       SHARED NAV PILL BASE
    ══════════════════════════════════════════════════════════ */
    .nav-pill-base {
        position: fixed;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        height: 66px;
        margin-bottom: calc(10px + var(--safe-bottom));
        background: rgba(255,255,255,0.90);
        backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(0,0,0,0.07);
        border-radius: 26px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
        display: flex; align-items: stretch;
        z-index: 1000; overflow: hidden;
    }
    [data-theme="dark"] .nav-pill-base {
        background: rgba(10,14,30,0.95);
        border: 1px solid rgba(255,255,255,0.09);
        box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
    }

    /* ── Mobile nav (5 items + More) ───────────────────────── */
    .nav-mobile {
        width: calc(100% - 24px);
        max-width: 480px;
    }
    .nav-mobile a,
    .nav-mobile button {
        flex: 1; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 3px;
        font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #94a3b8;
        background: none; border: none; cursor: pointer;
        text-decoration: none; transition: color .15s; padding: 0;
    }
    .nav-mobile a i, .nav-mobile button i { width: 20px; height: 20px; display: block; }
    .nav-mobile a:hover, .nav-mobile button:hover { color: #64748b; }
    [data-theme="dark"] .nav-mobile a,
    [data-theme="dark"] .nav-mobile button { color: #475569; }
    [data-theme="dark"] .nav-mobile a:hover,
    [data-theme="dark"] .nav-mobile button:hover { color: #94a3b8; }
    .nav-mobile a.sr-nav-active { color: #2563eb; }
    [data-theme="dark"] .nav-mobile a.sr-nav-active { color: #60a5fa; }

    /* ── Desktop nav (all items, scroll) ────────────────────── */
    .nav-desktop {
        display: none;
        width: calc(100% - 40px);
        max-width: 1400px;
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: none;
    }
    .nav-desktop::-webkit-scrollbar { display: none; }
    .nav-desktop-inner {
        display: flex; align-items: stretch;
        height: 100%; min-width: max-content;
        padding: 0 8px;
    }
    .nav-desktop a,
    .nav-desktop button {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 3px;
        min-width: 72px; padding: 0 10px;
        font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #94a3b8;
        background: none; border: none; cursor: pointer;
        text-decoration: none; transition: color .15s;
        white-space: nowrap;
    }
    .nav-desktop a i, .nav-desktop button i { width: 20px; height: 20px; display: block; }
    .nav-desktop a:hover, .nav-desktop button:hover { color: #64748b; }
    [data-theme="dark"] .nav-desktop a,
    [data-theme="dark"] .nav-desktop button { color: #475569; }
    [data-theme="dark"] .nav-desktop a:hover,
    [data-theme="dark"] .nav-desktop button:hover { color: #94a3b8; }
    .nav-desktop a.sr-nav-active { color: #2563eb; }
    [data-theme="dark"] .nav-desktop a.sr-nav-active { color: #60a5fa; }
    .nav-desktop-sep {
        width: 1px; flex-shrink: 0;
        background: rgba(0,0,0,0.07);
        margin: 14px 6px;
    }
    [data-theme="dark"] .nav-desktop-sep { background: rgba(255,255,255,0.08); }
    .nav-desktop .nav-danger { color: #ef4444 !important; }
    [data-theme="dark"] .nav-desktop .nav-danger { color: #f87171 !important; }
    .nav-desktop .nav-danger:hover { color: #dc2626 !important; }

    /* ── Responsive: swap navs ───────────────────────────────── */
    @media (min-width: 768px) {
        .nav-mobile  { display: none !important; }
        .nav-desktop { display: flex; }
        #fullMenu    { display: none !important; }
        #hamburger-btn { display: none !important; }
    }

    /* ══════════════════════════════════════════════════════════
       MORE OVERLAY — settings-style (mobile only)
    ══════════════════════════════════════════════════════════ */
    .more-overlay {
        position: fixed; inset: 0; z-index: 2000;
        overflow-y: auto; -webkit-overflow-scrolling: touch;
        background: #f1f5f9;
    }
    [data-theme="dark"] .more-overlay { background: #020617; }

    .more-sticky-hdr {
        position: sticky; top: 0; z-index: 10;
        display: flex; justify-content: space-between; align-items: center;
        padding: calc(env(safe-area-inset-top, 0px) + 40px) 20px 12px;
        background: rgba(241,245,249,0.95);
        backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    [data-theme="dark"] .more-sticky-hdr {
        background: rgba(2,6,23,0.95);
        border-bottom-color: rgba(255,255,255,0.05);
    }
    .more-profile {
        margin: 14px 16px 0;
        padding: 14px 16px;
        border-radius: 18px;
        display: flex; align-items: center; gap: 13px;
        background: rgba(255,255,255,0.75);
        border: 1px solid rgba(0,0,0,0.07);
    }
    [data-theme="dark"] .more-profile {
        background: rgba(255,255,255,0.07);
        border-color: rgba(255,255,255,0.09);
    }
    .more-sec-label {
        font-size: 10px; font-weight: 800; text-transform: uppercase;
        letter-spacing: .1em; color: #94a3b8;
        padding: 20px 20px 6px;
    }
    .more-card {
        margin: 0 16px;
        border-radius: 18px; overflow: hidden;
        background: rgba(255,255,255,0.75);
        border: 1px solid rgba(0,0,0,0.07);
    }
    [data-theme="dark"] .more-card {
        background: rgba(255,255,255,0.07);
        border-color: rgba(255,255,255,0.09);
    }
    .more-row {
        display: flex; align-items: center; gap: 13px;
        padding: 13px 14px; text-decoration: none;
        color: #0f172a;
        -webkit-tap-highlight-color: transparent;
        transition: background 0.1s;
    }
    [data-theme="dark"] .more-row { color: #f1f5f9; }
    .more-row:active { background: rgba(0,0,0,0.04); }
    [data-theme="dark"] .more-row:active { background: rgba(255,255,255,0.05); }
    .more-row.more-active { color: #2563eb; }
    [data-theme="dark"] .more-row.more-active { color: #60a5fa; }
    .more-row-danger { color: #ef4444 !important; }
    .more-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
    }
    .more-divider { height: 1px; background: rgba(0,0,0,0.06); margin: 0 14px; }
    [data-theme="dark"] .more-divider { background: rgba(255,255,255,0.07); }

    /* ── Shared modal ─────────────────────────────────────────── */
    .modal-os {
        position: fixed; top: 50%; left: 50%;
        transform: translate(-50%,-50%) scale(0.9);
        width: 90%; max-width: 460px;
        visibility: hidden; opacity: 0;
        backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
        border-radius: 28px; z-index: 4000;
        transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
        padding: 24px; max-height: 85vh; overflow-y: auto;
    }
    .modal-os.active { visibility: visible; opacity: 1; transform: translate(-50%,-50%) scale(1); }
    [data-theme="dark"] .modal-os { background: rgba(10,12,20,0.97); border: 1px solid rgba(255,255,255,0.12); }
    [data-theme="light"] .modal-os { background: rgba(255,255,255,0.96); border: 1px solid rgba(0,0,0,0.10); box-shadow: 0 24px 64px rgba(0,0,0,0.14); }
    .modal-overlay {
        position: fixed; inset: 0; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s;
    }
    .modal-overlay.active { visibility: visible; opacity: 1; }
    [data-theme="dark"]  .modal-overlay { background: rgba(0,0,0,0.6); }
    [data-theme="light"] .modal-overlay { background: rgba(0,0,0,0.22); }

    /* ── Office chat console (admin) — ports the approved prototype 1:1 ─────── */
    .chat-header-dot {
        position: absolute; top: -3px; right: -3px; min-width: 16px; height: 16px;
        padding: 0 3px; border-radius: 999px; background: #dc2626; color: #fff;
        font-size: 9px; font-weight: 800; line-height: 16px; text-align: center;
        border: 2px solid rgba(255,255,255,0.9);
    }
    [data-theme="dark"] .chat-header-dot { border-color: #0a0c14; }
    /* Fixed height on the outer modal itself — the console/canvas below just
       inherit 100% of it. Relying only on an inner grid child's height to push
       the whole modal's size (auto height + max-height cap) is fragile: with a
       short thread the box shrank to content, shoving the composer off entirely. */
    #chatModalOS { max-width: 1180px; width: 94vw; height: min(78vh, 680px); max-height: min(78vh, 680px); padding: 0; overflow: hidden; }

    .chat-console {
        display: grid; grid-template-columns: 280px 1fr; grid-template-rows: minmax(0, 1fr); gap: 0;
        height: 100%;
    }
    .chat-rail { background: rgba(0,0,0,0.02); border-right: 1px solid rgba(0,0,0,0.08); display: flex; flex-direction: column; }
    [data-theme="dark"] .chat-rail { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
    .chat-rail-head { padding: 16px 16px 8px; font-size: 11px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; color: #94a3b8; }
    .chat-rail-list { flex: 1; overflow-y: auto; padding: 4px 8px 12px; }
    .chat-rail-item { display: flex; align-items: center; gap: 10px; padding: 10px 8px; border-radius: 12px; cursor: pointer; }
    .chat-rail-item:hover { background: rgba(37,99,235,0.06); }
    .chat-rail-item.active { background: rgba(37,99,235,0.1); }
    .chat-rail-info { min-width: 0; flex: 1; }
    .chat-rail-name { font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
    .chat-rail-preview { font-size: 11.5px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }

    .chat-canvas { display: flex; flex-direction: column; min-width: 0; height: 100%; }
    .chat-canvas-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 16px 20px; border-bottom: 1px solid rgba(0,0,0,0.08); flex-shrink: 0; }
    [data-theme="dark"] .chat-canvas-head { border-color: rgba(255,255,255,0.08); }
    .chat-canvas-title-wrap { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .chat-canvas-title { font-size: 14.5px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .chat-canvas-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
    .chat-icon-btn {
        width: 32px; height: 32px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.08); background: none;
        display: flex; align-items: center; justify-content: center; cursor: pointer; color: inherit; font-size: 14px;
    }
    [data-theme="dark"] .chat-icon-btn { border-color: rgba(255,255,255,0.1); }
    .chat-icon-btn:hover { background: rgba(0,0,0,0.05); }
    [data-theme="dark"] .chat-icon-btn:hover { background: rgba(255,255,255,0.06); }

    .chat-avatar {
        width: 34px; height: 34px; border-radius: 999px; flex-shrink: 0;
        background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff;
        display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px;
    }
    .chat-rail-item .chat-avatar { width: 36px; height: 36px; font-size: 13px; }

    /* Topic strip */
    .chat-topic-strip { display: flex; align-items: center; gap: 8px; padding: 12px 20px; border-bottom: 1px solid rgba(0,0,0,0.08); overflow-x: auto; flex-shrink: 0; }
    [data-theme="dark"] .chat-topic-strip { border-color: rgba(255,255,255,0.08); }
    .chat-topic-pill {
        display: inline-flex; align-items: center; gap: 6px; padding: 7px 12px; border-radius: 999px;
        font-size: 12px; font-weight: 700; white-space: nowrap; cursor: pointer; flex-shrink: 0;
        border: 1px solid rgba(0,0,0,0.08); background: none; color: #64748b;
    }
    [data-theme="dark"] .chat-topic-pill { border-color: rgba(255,255,255,0.1); }
    .chat-topic-pill.active { background: #0f172a; color: #fff; border-color: #0f172a; }
    .chat-topic-pill.general { background: rgba(37,99,235,0.1); color: #2563eb; border-color: transparent; }
    .chat-topic-pill.general.active { background: #2563eb; color: #fff; }
    .chat-topic-pill .dot { width: 6px; height: 6px; border-radius: 999px; background: #16a34a; flex-shrink: 0; }
    .chat-topic-pill.is-closed .dot { background: #94a3b8; }
    .chat-topic-pill.new-topic { border-style: dashed; background: none; color: #94a3b8; }
    .chat-topic-pill.new-topic:hover { border-color: #2563eb; color: #2563eb; }

    /* Thread */
    .chat-thread-wrap { flex: 1; overflow-y: auto; padding: 18px 20px; display: flex; flex-direction: column; gap: 12px; min-height: 0; }
    .chat-system-note { align-self: center; font-size: 11px; color: #94a3b8; background: rgba(0,0,0,0.04); padding: 5px 12px; border-radius: 999px; font-weight: 600; }
    [data-theme="dark"] .chat-system-note { background: rgba(255,255,255,0.06); }
    .chat-bubble-row { display: flex; flex-direction: column; max-width: 72%; position: relative; }
    .chat-bubble-row.me   { align-self: flex-end; align-items: flex-end; }
    .chat-bubble-row.them { align-self: flex-start; align-items: flex-start; }
    .chat-sender-name { font-size: 10.5px; font-weight: 800; color: #2563eb; margin-bottom: 2px; padding: 0 4px; }
    .chat-quote-block {
        font-size: 11.5px; color: #94a3b8; background: rgba(0,0,0,0.04); border-left: 3px solid #2563eb;
        padding: 5px 9px; border-radius: 8px; margin-bottom: 4px; max-width: 100%; overflow: hidden;
        text-overflow: ellipsis; white-space: nowrap;
    }
    [data-theme="dark"] .chat-quote-block { background: rgba(255,255,255,0.06); }
    .chat-bubble { padding: 9px 13px; border-radius: 16px; font-size: 13.5px; line-height: 1.45; position: relative; animation: chatIn .18s ease-out; }
    .chat-bubble-me   { background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff; border-bottom-right-radius: 4px; }
    .chat-bubble-them { background: rgba(0,0,0,0.05); color: inherit; border-bottom-left-radius: 4px; }
    [data-theme="dark"] .chat-bubble-them { background: rgba(255,255,255,0.06); }
    .chat-bubble-time { font-size: 10px; opacity: .65; margin-top: 3px; padding: 0 4px; }
    .chat-bubble-photo { max-width: 220px; max-height: 220px; border-radius: 12px; margin-bottom: 6px; display: block; cursor: pointer; }
    @keyframes chatIn { from { opacity: 0; transform: translateY(4px); } to { opacity: 1; transform: translateY(0); } }

    .chat-bubble-actions {
        position: absolute; top: -12px; display: none; gap: 3px; background: #fff; border: 1px solid rgba(0,0,0,0.08);
        border-radius: 999px; padding: 3px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 2;
    }
    [data-theme="dark"] .chat-bubble-actions { background: #1e293b; border-color: rgba(255,255,255,0.1); }
    .chat-bubble-row.me .chat-bubble-actions   { right: 0; }
    .chat-bubble-row.them .chat-bubble-actions { left: 0; }
    .chat-bubble-row:hover .chat-bubble-actions { display: flex; }
    .chat-bubble-actions button {
        border: none; background: none; font-size: 11px; padding: 4px 8px; border-radius: 999px; cursor: pointer;
        color: #94a3b8; font-weight: 700; white-space: nowrap;
    }
    .chat-bubble-actions button:hover { background: rgba(0,0,0,0.06); color: inherit; }

    /* Composer */
    .chat-composer-wrap { border-top: 1px solid rgba(0,0,0,0.08); padding: 12px 20px 16px; flex-shrink: 0; }
    [data-theme="dark"] .chat-composer-wrap { border-color: rgba(255,255,255,0.08); }
    .chat-reply-preview, .chat-search-bar {
        display: flex; align-items: center; justify-content: space-between; gap: 8px;
        background: rgba(0,0,0,0.04); border-radius: 10px; padding: 7px 10px; margin-bottom: 8px; font-size: 12px;
    }
    [data-theme="dark"] .chat-reply-preview, [data-theme="dark"] .chat-search-bar { background: rgba(255,255,255,0.06); }
    .chat-reply-preview .rp-text { color: #94a3b8; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .chat-reply-preview button, .chat-search-bar button { border: none; background: none; cursor: pointer; color: #94a3b8; font-size: 13px; }
    .chat-closed-banner {
        display: flex; align-items: center; gap: 8px; justify-content: center; padding: 8px; margin-bottom: 8px;
        background: rgba(100,116,139,0.12); color: #64748b; border-radius: 10px; font-size: 12px; font-weight: 700;
    }
    .chat-compose-row { display: flex; gap: 8px; align-items: center; }
    #chatInput {
        flex: 1; height: 42px; border-radius: 999px; padding: 0 16px; font-size: 13.5px;
        border: 1px solid rgba(0,0,0,0.1); background: rgba(0,0,0,0.03); color: inherit; font-family: inherit;
    }
    [data-theme="dark"] #chatInput { border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); }
    #chatInput:focus { outline: none; border-color: #2563eb; }
    .chat-send-btn {
        width: 42px; height: 42px; flex-shrink: 0; border-radius: 999px; border: none; cursor: pointer;
        background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff;
        display: flex; align-items: center; justify-content: center; box-shadow: 0 8px 20px rgba(37,99,235,0.3);
    }
    .chat-search-input { border: none; background: none; font-size: 12.5px; flex: 1; font-family: inherit; color: inherit; }
    .chat-search-input:focus { outline: none; }
    .chat-thread-wrap mark { background: rgba(217,119,6,0.2); color: #b45309; border-radius: 3px; padding: 0 1px; }

    /* Geral temp banner */
    .chat-temp-banner {
        display: none; align-items: center; flex-wrap: wrap; gap: 8px; margin: 0 20px 12px; padding: 8px 12px;
        background: rgba(217,119,6,0.12); color: #b45309; border-radius: 10px; font-size: 11.5px; font-weight: 600; flex-shrink: 0;
    }
    .chat-temp-banner.show { display: flex; }
    .chat-temp-banner button {
        border: none; background: rgba(255,255,255,0.6); color: #b45309; font-size: 10.5px; font-weight: 800;
        padding: 5px 10px; border-radius: 999px; cursor: pointer; white-space: nowrap; flex-shrink: 0; margin-left: auto;
    }

    /* Popover (new topic / close / convert) */
    .chat-popover-backdrop { position: fixed; inset: 0; background: rgba(20,20,15,0.35); display: none; align-items: center; justify-content: center; z-index: 6000; }
    .chat-popover-backdrop.open { display: flex; }
    .chat-popover { background: #fff; border-radius: 16px; padding: 20px; width: min(320px, 90vw); box-shadow: 0 24px 64px rgba(0,0,0,0.24); }
    [data-theme="dark"] .chat-popover { background: #1e293b; }
    .chat-popover h3 { margin: 0 0 4px; font-size: 15px; font-weight: 800; }
    .chat-popover p.hint { margin: 0 0 14px; font-size: 12px; color: #94a3b8; }
    .chat-popover label { font-size: 10.5px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; display: block; margin: 12px 0 5px; }
    .chat-popover input[type="text"], .chat-popover select {
        width: 100%; height: 38px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); padding: 0 10px; font-size: 13px;
        font-family: inherit; background: rgba(0,0,0,0.02); color: inherit;
    }
    [data-theme="dark"] .chat-popover input[type="text"], [data-theme="dark"] .chat-popover select {
        border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.05);
    }
    .chat-popover-actions { display: flex; gap: 8px; margin-top: 18px; }
    .chat-popover-actions button { flex: 1; height: 38px; border-radius: 10px; border: none; font-size: 12.5px; font-weight: 700; cursor: pointer; }
    .chat-btn-ghost { background: rgba(0,0,0,0.06); color: inherit; }
    [data-theme="dark"] .chat-btn-ghost { background: rgba(255,255,255,0.08); }
    .chat-btn-primary { background: #2563eb; color: #fff; }
    .chat-btn-primary.danger { background: #dc2626; }

    /* Ride picker (search + card list, inside the new-topic popover) */
    .chat-ride-picker-search {
        width: 100%; height: 38px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.12); padding: 0 10px; font-size: 13px;
        font-family: inherit; background: rgba(0,0,0,0.02); color: inherit; margin-bottom: 6px;
    }
    [data-theme="dark"] .chat-ride-picker-search { border-color: rgba(255,255,255,0.12); background: rgba(255,255,255,0.05); }
    .chat-ride-picker-selected {
        font-size: 11.5px; font-weight: 700; color: #16a34a; background: rgba(22,163,74,0.1);
        border-radius: 8px; padding: 6px 9px; margin-bottom: 6px;
    }
    .chat-ride-picker-list { max-height: 140px; overflow-y: auto; display: flex; flex-direction: column; gap: 3px; }
    .chat-ride-picker-item {
        font-size: 12px; padding: 7px 9px; border-radius: 8px; cursor: pointer; border: 1px solid transparent;
    }
    .chat-ride-picker-item:hover { background: rgba(37,99,235,0.08); border-color: rgba(37,99,235,0.2); }

    /* Pinned ride banner above the thread */
    .chat-ride-banner {
        display: none; align-items: center; gap: 8px; margin: 0 20px 8px; padding: 7px 12px;
        background: rgba(37,99,235,0.1); color: #2563eb; border-radius: 10px; font-size: 11.5px; font-weight: 700;
    }

    .chat-mobile-rail-toggle { display: none !important; }
    @media (max-width: 780px) {
        #chatModalOS { width: 94vw; height: 88dvh; max-height: 720px; border-radius: 20px; }
        .chat-console { grid-template-columns: 1fr; grid-template-rows: minmax(0, 1fr); height: 100%; position: relative; border-radius: 20px; overflow: hidden; }
        .chat-rail {
            position: absolute; inset: 0; z-index: 5; width: 85%; background: #fff;
            transform: translateX(-100%); transition: transform .25s ease; border-right: none;
            box-shadow: 12px 0 32px rgba(0,0,0,0.18);
        }
        [data-theme="dark"] .chat-rail { background: #0f172a; }
        .chat-console.rail-open .chat-rail { transform: translateX(0); }
        .chat-console.rail-mode .chat-mobile-rail-toggle { display: flex !important; }
        .chat-canvas-head { padding: 12px 14px; }
        .chat-topic-strip { padding: 10px 14px; }
        .chat-thread-wrap { padding: 14px; }
        .chat-bubble-row { max-width: 88%; }
        .chat-composer-wrap { padding: 10px 14px 14px; }
        #chatInput { font-size: 16px; }
    }

    /* ── Light-mode overrides ─────────────────────────────────── */
    [data-theme="light"] .text-white      { color: #0f172a !important; }
    [data-theme="light"] .text-zinc-200,
    [data-theme="light"] .text-zinc-300   { color: #475569 !important; }
    [data-theme="light"] .text-zinc-400   { color: #64748b !important; }
    [data-theme="light"] .text-zinc-800   { color: #334155 !important; }
    [data-theme="light"] .bg-white\/5     { background: rgba(0,0,0,0.04) !important; }
    [data-theme="light"] .bg-white\/8     { background: rgba(0,0,0,0.05) !important; }
    [data-theme="light"] .bg-white\/10    { background: rgba(0,0,0,0.06) !important; }
    [data-theme="light"] .border-white\/10  { border-color: rgba(0,0,0,0.08) !important; }
    [data-theme="light"] .border-white\/15  { border-color: rgba(0,0,0,0.10) !important; }
    [data-theme="light"] .action-circle { background: rgba(0,0,0,0.05) !important; border-color: rgba(0,0,0,0.08) !important; }
    [data-theme="light"] .text-indigo-100 { color: #4338ca !important; }
    [data-theme="light"] .text-indigo-400 { color: #6366f1 !important; }
    [data-theme="light"] .modal-os .text-white { color: #0f172a !important; }
    [data-theme="light"] .modal-os input,
    [data-theme="light"] .modal-os select,
    [data-theme="light"] .modal-os textarea {
        color: #0f172a !important; background: rgba(0,0,0,0.04) !important;
        border-color: rgba(0,0,0,0.12) !important;
    }

    .no-scrollbar::-webkit-scrollbar { display: none; }
</style>
<?= $extraHead ?>
</head>
<body>
<?php
// Trial banner — fetch company billing status for the current session
$_trialBanner   = null;
$_trialDaysLeft = 0;
if (\App\Support\Session::companyId() !== null) {
    $_trialCo = \App\Repositories\CompanyRepository::default()->find(\App\Support\Session::companyId());
    // Only show banner for Stripe-backed trials (stripeSubscriptionId set) — not for 'none' status
    if ($_trialCo && $_trialCo->subStatus === 'trialing' && $_trialCo->subCurrentPeriodEnd !== null && $_trialCo->stripeSubscriptionId !== null) {
        $_trialDaysLeft = max(0, (int) ceil((strtotime($_trialCo->subCurrentPeriodEnd) - time()) / 86400));
        $_trialBanner   = true;
    }
}
?>
<?php if ($_trialBanner): ?>
<div id="trial-banner" style="
    position: fixed; top: 0; left: 0; right: 0; z-index: 9999;
    background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 50%, #7c3aed 100%);
    color: #fff; font-family: 'Plus Jakarta Sans', sans-serif;
    padding: 9px 16px; display: flex; align-items: center; justify-content: center; gap: 10px;
    font-size: 13px; font-weight: 600;
    box-shadow: 0 2px 12px rgba(37,99,235,0.28);
">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
    <?php if ($_trialDaysLeft <= 1): ?>
        <span>Último dia de trial — subscreve hoje para manter o acesso.</span>
    <?php elseif ($_trialDaysLeft <= 3): ?>
        <span>O teu trial termina em <strong><?= $_trialDaysLeft ?> dias</strong> — subscreve antes de perder o acesso.</span>
    <?php else: ?>
        <span>Trial ativo — <strong><?= $_trialDaysLeft ?> dias restantes</strong></span>
    <?php endif; ?>
    <a href="/SRMT/public/admin/billing.php" style="
        background: rgba(255,255,255,0.18); border: 1.5px solid rgba(255,255,255,0.3);
        color: #fff; text-decoration: none; border-radius: 20px;
        padding: 4px 12px; font-size: 12px; font-weight: 700;
        transition: background .2s; white-space: nowrap;
    " onmouseover="this.style.background='rgba(255,255,255,0.28)'" onmouseout="this.style.background='rgba(255,255,255,0.18)'">
        Subscrever agora →
    </a>
</div>
<div style="height: 37px;"></div>
<?php endif; ?>
<div class="bg-main">
<div id="app-container">
    <div style="padding-bottom: calc(66px + var(--safe-bottom) + 24px)">

        <header id="sr-app-header" class="px-6 pt-10 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="<?= View::e($userPhoto) ?>" onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'" class="sr-hdr-avatar w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover" alt="">
                <div>
                    <h2 class="text-[15px] font-extrabold leading-tight">Hi, <?= View::e($userName) ?></h2>
                    <p class="sr-hdr-sub text-[8px] text-zinc-500 font-black tracking-widest uppercase italic"><?= t('nav.system_admin') ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button id="chatHeaderBtn" onclick="openChatInbox()" title="<?= t('chat.header_title') ?>"
                    class="glass w-10 h-10 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0" style="position:relative;">
                    <i data-lucide="message-circle" class="w-4 h-4"></i>
                    <span id="chatHeaderDot" class="chat-header-dot" style="display:none;"></span>
                </button>
                <button id="hamburger-btn" onclick="toggleMenu()" class="glass w-10 h-10 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0">
                    <i data-lucide="menu" class="w-4 h-4"></i>
                </button>
            </div>
        </header>

        <?= $content ?>

    </div>
</div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MOBILE NAV — 5 items + More  (hidden md+)
════════════════════════════════════════════════════════════ -->
<nav class="nav-pill-base nav-mobile">
    <a href="/SRMT/public/admin/"                   class="<?= $navClass('dashboard') ?>"><i data-lucide="home"></i><?= t('nav.home') ?></a>
    <a href="/SRMT/public/admin/rides.php"          class="<?= $navClass('rides') ?>"><i data-lucide="calendar"></i><?= t('nav.rides') ?></a>
    <a href="/SRMT/public/admin/schedule-board.php" class="<?= $navClass('board') ?>"><i data-lucide="calendar-days"></i><?= t('nav.board') ?></a>
    <a href="/SRMT/public/admin/live-map.php"       class="<?= $navClass('live-map') ?>"><i data-lucide="locate-fixed"></i><?= t('nav.live') ?></a>
    <button onclick="toggleMenu()"><i data-lucide="grid-2x2"></i><?= t('nav.more') ?></button>
</nav>

<!-- ════════════════════════════════════════════════════════════
     DESKTOP NAV — all items, scrollable  (hidden < md)
════════════════════════════════════════════════════════════ -->
<nav class="nav-pill-base nav-desktop">
    <div class="nav-desktop-inner">
        <a href="/SRMT/public/admin/"                    class="<?= $navClass('dashboard') ?>"><i data-lucide="home"></i><?= t('nav.home') ?></a>
        <a href="/SRMT/public/admin/rides.php"           class="<?= $navClass('rides') ?>"><i data-lucide="calendar"></i><?= t('nav.rides') ?></a>
        <a href="/SRMT/public/admin/schedule-board.php"  class="<?= $navClass('board') ?>"><i data-lucide="calendar-days"></i><?= t('nav.board') ?></a>
        <a href="/SRMT/public/admin/live-map.php"        class="<?= $navClass('live-map') ?>"><i data-lucide="locate-fixed"></i><?= t('nav.live') ?></a>

        <div class="nav-desktop-sep"></div>

        <a href="/SRMT/public/admin/financial.php"       class="<?= $navClass('financial') ?>"><i data-lucide="wallet"></i><?= t('nav.cash') ?></a>
        <a href="/SRMT/public/admin/import.php"          class="<?= $navClass('import') ?>"><i data-lucide="file-spreadsheet"></i><?= t('nav.import') ?></a>

        <div class="nav-desktop-sep"></div>

        <a href="/SRMT/public/admin/users.php"           class="<?= $navClass('users') ?>"><i data-lucide="users"></i><?= t('nav.team') ?></a>
        <a href="/SRMT/public/admin/fleet.php"           class="<?= $navClass('fleet') ?>"><i data-lucide="truck"></i><?= t('nav.fleet') ?></a>
        <a href="/SRMT/public/admin/pricing.php"         class="<?= $navClass('pricing') ?>"><i data-lucide="tag"></i><?= t('nav.pricing') ?></a>

        <div class="nav-desktop-sep"></div>

        <a href="/SRMT/public/admin/driver-stats.php"    class="<?= $navClass('stats') ?>"><i data-lucide="bar-chart-3"></i><?= t('nav.stats') ?></a>
        <a href="/SRMT/public/admin/no-shows.php"        class="<?= $navClass('no-shows') ?>"><i data-lucide="alert-triangle"></i><?= t('nav.noshows') ?></a>
        <a href="/SRMT/public/admin/vouchers.php"        class="<?= $navClass('vouchers') ?>"><i data-lucide="ticket"></i>Vouchers</a>
        <a href="/SRMT/public/admin/partnerships.php"    class="<?= $navClass('partnerships') ?>"><i data-lucide="handshake"></i><?= t('nav.partnerships') ?></a>

        <div class="nav-desktop-sep"></div>

        <a href="/SRMT/public/admin/storage.php"         class="<?= $navClass('storage') ?>"><i data-lucide="database"></i><?= t('nav.storage') ?></a>
        <a href="/SRMT/public/admin/settings.php"        class="<?= $navClass('settings') ?>"><i data-lucide="mail"></i><?= t('nav.automations') ?></a>
        <div class="nav-desktop-sep"></div>

        <a href="/SRMT/public/auth/logout.php" class="nav-danger"><i data-lucide="log-out"></i><?= t('nav.logout') ?></a>
    </div>
</nav>

<!-- ════════════════════════════════════════════════════════════
     MORE OVERLAY — settings-style  (mobile only, hidden md+)
════════════════════════════════════════════════════════════ -->
<div id="fullMenu" class="more-overlay hidden">

    <div class="more-sticky-hdr">
        <h1 class="text-2xl font-black"><?= t('nav.more') ?></h1>
        <button onclick="toggleMenu()" class="glass w-10 h-10 rounded-full flex items-center justify-center border-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>

    <div class="more-profile">
        <img src="<?= View::e($userPhoto) ?>" onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'"
             class="w-14 h-14 rounded-full border-2 border-blue-500/20 object-cover flex-shrink-0" alt="">
        <div class="flex-1 min-w-0">
            <h3 class="font-bold text-[16px] leading-snug truncate"><?= View::e($userName) ?></h3>
            <p class="text-[11px] text-zinc-500 font-semibold uppercase tracking-wider mt-0.5"><?= t('nav.system_admin') ?></p>
        </div>
    </div>

    <!-- Operações -->
    <p class="more-sec-label"><?= t('nav.sec_operations') ?></p>
    <div class="more-card">
        <a href="/SRMT/public/admin/financial.php" onclick="toggleMenu()" class="more-row <?= $active==='financial' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(37,99,235,.12)"><i data-lucide="wallet" class="w-[17px] h-[17px] text-blue-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.financial') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
    </div>

    <!-- Equipa & Frota -->
    <p class="more-sec-label"><?= t('nav.sec_team') ?></p>
    <div class="more-card">
        <a href="/SRMT/public/admin/users.php" onclick="toggleMenu()" class="more-row <?= $active==='users' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(139,92,246,.12)"><i data-lucide="users" class="w-[17px] h-[17px] text-violet-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.team') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/admin/fleet.php" onclick="toggleMenu()" class="more-row <?= $active==='fleet' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(249,115,22,.12)"><i data-lucide="truck" class="w-[17px] h-[17px] text-orange-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.fleet') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/admin/pricing.php" onclick="toggleMenu()" class="more-row <?= $active==='pricing' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(6,182,212,.12)"><i data-lucide="tag" class="w-[17px] h-[17px] text-cyan-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.pricing') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
    </div>

    <!-- Relatórios -->
    <p class="more-sec-label"><?= t('nav.sec_reports') ?></p>
    <div class="more-card">
        <a href="/SRMT/public/admin/driver-stats.php" onclick="toggleMenu()" class="more-row <?= $active==='stats' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(37,99,235,.12)"><i data-lucide="bar-chart-3" class="w-[17px] h-[17px] text-blue-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.stats') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/admin/no-shows.php" onclick="toggleMenu()" class="more-row <?= $active==='no-shows' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(245,158,11,.12)"><i data-lucide="alert-triangle" class="w-[17px] h-[17px] text-amber-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.noshows') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/admin/vouchers.php" onclick="toggleMenu()" class="more-row <?= $active==='vouchers' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(96,165,250,.12)"><i data-lucide="ticket" class="w-[17px] h-[17px] text-blue-400"></i></div>
            <span class="flex-1 text-[15px] font-semibold">Vouchers</span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/admin/partnerships.php" onclick="toggleMenu()" class="more-row <?= $active==='partnerships' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(34,197,94,.12)"><i data-lucide="handshake" class="w-[17px] h-[17px] text-green-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.partnerships') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
    </div>

    <!-- Sistema -->
    <p class="more-sec-label"><?= t('nav.sec_system') ?></p>
    <div class="more-card">
        <a href="/SRMT/public/admin/storage.php" onclick="toggleMenu()" class="more-row <?= $active==='storage' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(100,116,139,.12)"><i data-lucide="database" class="w-[17px] h-[17px] text-slate-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.storage') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/admin/settings.php" onclick="toggleMenu()" class="more-row <?= $active==='settings' ? 'more-active' : '' ?>">
            <div class="more-icon" style="background:rgba(251,146,60,.12)"><i data-lucide="mail" class="w-[17px] h-[17px] text-orange-400"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.automations') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
    </div>

    <!-- Subscrição -->
    <p class="more-sec-label">Plano & Faturação</p>
    <div class="more-card">
        <a href="/SRMT/public/admin/billing.php" class="more-row" onclick="toggleMenu()">
            <div class="more-icon" style="background:rgba(139,92,246,.12)"><i data-lucide="credit-card" class="w-[17px] h-[17px]" style="color:#8b5cf6"></i></div>
            <span class="flex-1 text-[15px] font-semibold">Subscrição</span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
    </div>

    <!-- Conta -->
    <p class="more-sec-label"><?= t('nav.sec_account') ?></p>
    <div class="more-card">
        <a href="#" onclick="event.preventDefault();toggleMenu();openChangePassword();" class="more-row">
            <div class="more-icon" style="background:rgba(113,113,122,.12)"><i data-lucide="key-round" class="w-[17px] h-[17px] text-zinc-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('pwd.title') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
        </a>
        <div class="more-divider"></div>
        <a href="/SRMT/public/auth/logout.php" class="more-row more-row-danger">
            <div class="more-icon" style="background:rgba(239,68,68,.12)"><i data-lucide="log-out" class="w-[17px] h-[17px] text-red-500"></i></div>
            <span class="flex-1 text-[15px] font-semibold"><?= t('nav.logout') ?></span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-red-300"></i>
        </a>
    </div>

    <div style="height: 48px"></div>
</div>

<?php include __DIR__ . '/_change_password.php'; ?>
<?php include __DIR__ . '/_admin_chat.php'; ?>
<?php include __DIR__ . '/_csrf.php'; ?>

<script>
    lucide.createIcons();

    (function () {
        var saved = localStorage.getItem('sr-theme') || 'light';
        applyTheme(saved, false);
    })();

    function applyTheme(t, save) {
        document.documentElement.dataset.theme = t;
        var mc = document.getElementById('themeColor');
        if (mc) mc.content = t === 'dark' ? '#020617' : '#f1f5f9';
        if (save) localStorage.setItem('sr-theme', t);
    }

    function toggleMenu() {
        var m   = document.getElementById('fullMenu');
        var app = document.getElementById('app-container');
        m.classList.toggle('hidden');
        if (app) app.style.overflow = m.classList.contains('hidden') ? '' : 'hidden';
    }

    // Condense the app header on scroll and publish its height so page-level
    // sticky bars (e.g. the rides filters) can pin right beneath it.
    (function () {
        var app = document.getElementById('app-container');
        var hdr = document.getElementById('sr-app-header');
        if (!app || !hdr) return;
        function sync() {
            app.classList.toggle('scrolled', app.scrollTop > 8);
            document.documentElement.style.setProperty('--sr-header-h', hdr.offsetHeight + 'px');
            // Pill "concluídas": esconder quando já estamos no topo (rides visíveis)
            var pill = document.getElementById('completedStackedPill');
            if (pill) pill.style.visibility = app.scrollTop > 40 ? 'visible' : 'hidden';
        }
        app.addEventListener('scroll', sync, { passive: true });
        hdr.addEventListener('transitionend', sync);
        window.addEventListener('resize', sync);
        sync();
    })();

    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.parentElement !== document.body) {
            document.body.appendChild(e.target);
        }
    });
</script>

<!-- ── SyncRide Toast System ────────────────────────────────────────────── -->
<style>
#sr-toasts{position:fixed;bottom:24px;right:20px;z-index:999999;display:flex;flex-direction:column;gap:10px;max-width:360px;width:calc(100vw - 40px);pointer-events:none;}
.sr-t{pointer-events:all;display:flex;align-items:flex-start;gap:12px;padding:14px 16px 17px;border-radius:18px;
    background:rgba(255,255,255,.97);border:1px solid rgba(0,0,0,.06);
    box-shadow:0 12px 40px rgba(0,0,0,.13),0 2px 8px rgba(0,0,0,.07);
    backdrop-filter:blur(24px);-webkit-backdrop-filter:blur(24px);
    position:relative;overflow:hidden;
    animation:sr-in .38s cubic-bezier(.34,1.56,.64,1) forwards;}
[data-theme="dark"] .sr-t{background:rgba(15,23,42,.96);border-color:rgba(255,255,255,.08);box-shadow:0 12px 40px rgba(0,0,0,.5),0 2px 8px rgba(0,0,0,.3);}
.sr-t.sr-out{animation:sr-out .28s ease forwards;}
@keyframes sr-in{from{opacity:0;transform:translateX(110%) scale(.9)}to{opacity:1;transform:none}}
@keyframes sr-out{from{opacity:1;transform:none}to{opacity:0;transform:translateX(110%) scale(.88)}}
.sr-t-icon{width:38px;height:38px;border-radius:11px;display:flex;align-items:center;justify-content:center;font-size:1.05rem;flex-shrink:0;margin-top:1px;}
.sr-t-body{flex:1;min-width:0;}
.sr-t-title{font-size:.8rem;font-weight:800;color:#0f172a;line-height:1.2;margin-bottom:2px;letter-spacing:.01em;}
[data-theme="dark"] .sr-t-title{color:#f1f5f9;}
.sr-t-msg{font-size:.73rem;color:#64748b;line-height:1.45;}
[data-theme="dark"] .sr-t-msg{color:#94a3b8;}
.sr-t-x{flex-shrink:0;width:22px;height:22px;display:flex;align-items:center;justify-content:center;
    background:none;border:none;cursor:pointer;color:#94a3b8;font-size:.8rem;padding:0;border-radius:7px;margin-top:-1px;}
.sr-t-x:hover{background:rgba(0,0,0,.07);color:#475569;}
[data-theme="dark"] .sr-t-x:hover{background:rgba(255,255,255,.1);}
.sr-t-bar{position:absolute;bottom:0;left:0;height:3px;border-radius:0 0 18px 18px;animation:sr-bar linear forwards;}
@keyframes sr-bar{from{width:100%}to{width:0%}}
.sr-t.sr-success .sr-t-icon{background:rgba(16,185,129,.12);color:#10b981;}
.sr-t.sr-success .sr-t-bar{background:linear-gradient(90deg,#10b981,#34d399);}
.sr-t.sr-error   .sr-t-icon{background:rgba(239,68,68,.12);color:#ef4444;}
.sr-t.sr-error   .sr-t-bar{background:linear-gradient(90deg,#ef4444,#f87171);}
.sr-t.sr-warning .sr-t-icon{background:rgba(245,158,11,.12);color:#f59e0b;}
.sr-t.sr-warning .sr-t-bar{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.sr-t.sr-info    .sr-t-icon{background:rgba(37,99,235,.12);color:#2563eb;}
.sr-t.sr-info    .sr-t-bar{background:linear-gradient(90deg,#2563eb,#3b82f6);}
</style>
<div id="sr-toasts"></div>
<script>
(function(){
  const IC={'success':'bi-check-circle-fill','error':'bi-x-circle-fill','warning':'bi-exclamation-triangle-fill','info':'bi-info-circle-fill'};
  const TT={'success':'Sucesso','error':'Erro','warning':'Aviso','info':'Info'};
  function show(type,msg,title,ms){
    ms=ms||4000;
    const c=document.getElementById('sr-toasts');
    if(!c)return;
    const el=document.createElement('div');
    el.className='sr-t sr-'+type;
    el.innerHTML=
      '<div class="sr-t-icon"><i class="bi '+(IC[type]||IC.info)+'"></i></div>'+
      '<div class="sr-t-body">'+
        '<div class="sr-t-title">'+(title||TT[type]||'')+'</div>'+
        (msg?'<div class="sr-t-msg">'+msg+'</div>':'')+
      '</div>'+
      '<button class="sr-t-x" onclick="srToastDismiss(this.closest(\'.sr-t\'))"><i class="bi bi-x"></i></button>'+
      '<div class="sr-t-bar" style="animation-duration:'+ms+'ms"></div>';
    c.appendChild(el);
    setTimeout(()=>srToastDismiss(el),ms);
  }
  window.srToastDismiss=function(el){
    if(!el||el.classList.contains('sr-out'))return;
    el.classList.add('sr-out');
    setTimeout(()=>el.remove(),280);
  };
  window.srToast=show;
  // Drop-in toastr compat — toastr.success(message, title)
  window.toastr={
    options:{},
    success:(m,t)=>show('success',m,t),
    error:  (m,t)=>show('error',  m,t),
    warning:(m,t)=>show('warning',m,t),
    info:   (m,t)=>show('info',   m,t),
  };
})();
</script>
<?= $extraScripts ?>
<script>
(function() {
    const BGeo = window.Capacitor?.Plugins?.BackgroundGeolocation;
    if (BGeo?.registerFcmToken) BGeo.registerFcmToken().catch(() => {});
})();
</script>
</body>
</html>
