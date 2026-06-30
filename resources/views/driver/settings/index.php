<?php
use App\Http\View;

/** @var string $userName  @var string $lang */
View::layout('layouts.driver', [
    'title'  => 'Definições — SyncRide',
    'active' => 'settings',
]);

$first    = explode(' ', trim($userName))[0];
$photo    = $_SESSION['profile_photo_path'] ?? null;
$photoSrc = $photo ? '/SRMT/' . ltrim((string) $photo, '/') : '';
?>
<style>
    .settings-wrap { max-width: 560px; margin: 0 auto; }
    .set-profile {
        display: flex; align-items: center; gap: 14px;
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: 18px; padding: 16px; margin-bottom: 6px;
    }
    .set-profile .avatar {
        width: 58px; height: 58px; border-radius: 50%; flex-shrink: 0;
        object-fit: cover; border: 2px solid var(--border-color);
        display: flex; align-items: center; justify-content: center;
        background: var(--accent-soft); color: var(--primary-accent); font-size: 1.6rem;
    }
    .set-profile .name { font-family: var(--font-display); font-size: 1.15rem; font-weight: 700; color: var(--text-main); }
    .set-profile .role { font-size: .8rem; color: var(--text-muted); }
    .set-label {
        font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
        color: var(--text-faint); padding: 18px 4px 8px;
    }
    .set-card { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 18px; overflow: hidden; }
    .set-row {
        display: flex; align-items: center; gap: 13px;
        width: 100%; padding: 14px 15px; text-decoration: none;
        color: var(--text-main); background: none; border: none; cursor: pointer;
        font-size: .95rem; font-family: inherit; text-align: left;
        -webkit-tap-highlight-color: transparent; transition: background .12s;
    }
    .set-row:active { background: var(--bg-raised); }
    .set-row .ico {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1rem;
        background: var(--accent-soft); color: var(--primary-accent);
    }
    .set-row .grow { flex: 1; }
    .set-row .chev { color: var(--text-faint); font-size: .85rem; }
    .set-row .check { color: var(--primary-accent); font-size: 1.1rem; }
    .set-divider { height: 1px; background: var(--border-color); margin: 0 15px; }
    .set-row-danger { color: #ef4444; }
    .set-row-danger .ico { background: rgba(239,68,68,.12); color: #ef4444; }
    /* theme switch */
    .sw { width: 46px; height: 28px; border-radius: 20px; background: var(--border-color); position: relative; transition: background .2s; flex-shrink: 0; }
    .sw::after { content: ''; position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; border-radius: 50%; background: #fff; transition: transform .2s; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
    .sw.on { background: var(--primary-accent); }
    .sw.on::after { transform: translateX(18px); }
</style>

<div class="settings-wrap">
    <!-- Profile -->
    <div class="set-profile">
        <?php if ($photoSrc !== ''): ?>
            <img class="avatar" src="<?= View::e($photoSrc) ?>" alt="">
        <?php else: ?>
            <span class="avatar"><i class="bi bi-person-fill"></i></span>
        <?php endif; ?>
        <div>
            <div class="name"><?= View::e($first) ?></div>
            <div class="role"><i class="bi bi-car-front-fill"></i> Condutor</div>
        </div>
    </div>

    <!-- Language -->
    <div class="set-label">Idioma · Language</div>
    <div class="set-card">
        <a class="set-row" href="/SRMT/public/driver/set-lang.php?lang=pt">
            <span class="ico">🇵🇹</span>
            <span class="grow">Português</span>
            <?php if ($lang === 'pt'): ?><i class="bi bi-check-lg check"></i><?php endif; ?>
        </a>
        <div class="set-divider"></div>
        <a class="set-row" href="/SRMT/public/driver/set-lang.php?lang=en">
            <span class="ico">🇬🇧</span>
            <span class="grow">English</span>
            <?php if ($lang === 'en'): ?><i class="bi bi-check-lg check"></i><?php endif; ?>
        </a>
    </div>

    <!-- Account -->
    <div class="set-label">Conta · Account</div>
    <div class="set-card">
        <button class="set-row" type="button" onclick="openChangePassword()">
            <span class="ico"><i class="bi bi-key-fill"></i></span>
            <span class="grow">Mudar password</span>
            <i class="bi bi-chevron-right chev"></i>
        </button>
        <div class="set-divider"></div>
        <button class="set-row" type="button" id="themeRow">
            <span class="ico"><i class="bi bi-moon-stars-fill"></i></span>
            <span class="grow">Modo escuro</span>
            <span class="sw" id="themeSwitch"></span>
        </button>
    </div>

    <!-- Logout -->
    <div class="set-card" style="margin-top:18px;">
        <a class="set-row set-row-danger" href="/SRMT/public/auth/logout.php" id="settingsLogout">
            <span class="ico"><i class="bi bi-box-arrow-right"></i></span>
            <span class="grow">Terminar sessão</span>
        </a>
    </div>

    <p style="text-align:center;color:var(--text-faint);font-size:.72rem;margin-top:22px;">SyncRide · Driver</p>
</div>

<script>
(function () {
    const html  = document.documentElement;
    const sw    = document.getElementById('themeSwitch');
    const row   = document.getElementById('themeRow');
    function sync() { sw.classList.toggle('on', html.getAttribute('data-bs-theme') === 'dark'); }
    sync();
    row.addEventListener('click', () => {
        const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem('theme', next);
        html.setAttribute('data-bs-theme', next);
        // keep the layout header toggle icon in sync if present
        const icon = document.getElementById('theme-icon');
        if (icon) icon.className = next === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
        sync();
    });

    // Logout: stop native GPS tracking before signing out
    document.getElementById('settingsLogout')?.addEventListener('click', async function (e) {
        e.preventDefault();
        const dest = this.getAttribute('href');
        try { await window.Capacitor?.Plugins?.BackgroundGeolocation?.stopTracking?.(); } catch (_) {}
        sessionStorage.removeItem('activeRideId');
        window.location.href = dest;
    });
})();
</script>
