<?php
use App\Http\View;

/**
 * @var array[] $rides       initial ride rows (joined data)
 * @var int     $todayCount
 * @var int     $weekCount
 * @var int     $driverId
 * @var string  $userName
 */
$firstName    = explode(' ', trim($userName))[0];
$userPhotoSrc = isset($_SESSION['profile_photo_path']) && $_SESSION['profile_photo_path'] !== ''
    ? '/SRMT/' . ltrim((string) $_SESSION['profile_photo_path'], '/')
    : '/SRMT/public/assets/images/icons/SyncRide.png';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Rides — SyncRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        :root {
            --font-primary: 'Inter', sans-serif;
            --font-display: 'Poppins', sans-serif;
            --bg-body: #f3f4f6; --bg-card: #ffffff;
            --text-main: #111827; --text-muted: #6b7280;
            --primary-accent: #4f46e5; --primary-hover: #4338ca;
            --border-color: #e5e7eb; --shadow-sm: 0 1px 3px rgba(0,0,0,.1);
            --radius-md: 16px;
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        [data-bs-theme="dark"] {
            --bg-body: #0f172a; --bg-card: #1e293b;
            --text-main: #f9fafb; --text-muted: #94a3b8;
            --primary-accent: #6366f1; --primary-hover: #818cf8;
            --border-color: #334155;
        }
        body { font-family: var(--font-primary); background-color: var(--bg-body); color: var(--text-main); padding-bottom: calc(80px + var(--safe-bottom)); margin: 0; min-height: 100vh; }
        .app-header { background-color: var(--bg-card); border-bottom: 1px solid var(--border-color); padding: calc(15px + var(--safe-top)) 20px 15px; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; z-index: 1080; }
        .brand-logo  { height: 30px; width: auto; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); }
        .stat-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 15px; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow-sm); }
        .stat-icon { width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
        .bg-indigo-soft  { background: rgba(79,70,229,.1); color: var(--primary-accent); }
        .bg-emerald-soft { background: rgba(16,185,129,.1); color: #10b981; }
        .stat-info h3 { font-family: var(--font-display); font-weight: 700; font-size: 1.5rem; margin: 0; line-height: 1; }
        .stat-info p  { margin: 0; font-size: .8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }
        .filter-container { background-color: var(--bg-card); padding: 5px; border-radius: 50px; display: flex; justify-content: space-between; border: 1px solid var(--border-color); margin-bottom: 20px; }
        .filter-btn { flex: 1; text-align: center; padding: 8px 0; border-radius: 50px; border: none; background: transparent; color: var(--text-muted); font-size: .9rem; font-weight: 500; transition: all .2s; }
        .filter-btn.active { background-color: var(--primary-accent); color: white; box-shadow: 0 2px 5px rgba(79,70,229,.3); }
        .ride-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); margin-bottom: 15px; padding: 16px; position: relative; transition: transform .1s; box-shadow: var(--shadow-sm); }
        .ride-card:active { transform: scale(.98); }
        .ride-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .ride-time   { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
        .ride-badges-container { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
        .ride-badge  { font-size: .7rem; font-weight: 600; padding: 4px 10px; border-radius: 50px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px; }
        .badge-private { background: rgba(79,70,229,.1); color: var(--primary-accent); border: 1px solid rgba(79,70,229,.2); }
        .badge-shared  { background: rgba(245,158,11,.1); color: #d97706;             border: 1px solid rgba(245,158,11,.2); }
        .card-timeline { position: relative; padding-left: 20px; border-left: 2px dashed var(--border-color); margin-left: 6px; }
        .ct-point { position: relative; margin-bottom: 15px; }
        .ct-point:last-child { margin-bottom: 0; }
        .ct-dot { width: 12px; height: 12px; border-radius: 50%; position: absolute; left: -27px; top: 4px; border: 2px solid var(--bg-card); }
        .dot-pickup  { background-color: #10b981; }
        .dot-dropoff { background-color: #ef4444; }
        .ct-text  { font-size: .95rem; color: var(--text-main); line-height: 1.3; }
        .price-tag { position: absolute; bottom: 16px; right: 16px; background-color: #10b981; color: white; font-weight: 700; font-size: .85rem; padding: 4px 10px; border-radius: 8px; display: flex; align-items: center; gap: 4px; }
        .modal-content { background-color: var(--bg-card); border-radius: 24px; border: none; }
        .modal-header  { border-bottom: 1px solid var(--border-color); padding: 1rem 1.2rem; }
        .modal-title   { font-family: var(--font-display); font-weight: 700; color: var(--text-main); font-size: 1.1rem; }
        .modal-body    { padding: 1rem; }
        .info-box   { background-color: var(--bg-body); border-radius: 12px; padding: 10px; border: 1px solid var(--border-color); margin-bottom: 8px; }
        .info-label { font-size: .65rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 2px; }
        .info-value { font-size: .95rem; color: var(--text-main); font-weight: 600; line-height: 1.2; }
        .btn-dynamic-action { width: 100%; padding: 12px; font-size: 1rem; font-weight: 700; font-family: var(--font-display); border-radius: 12px; border: none; color: white; text-transform: uppercase; letter-spacing: .5px; box-shadow: 0 4px 12px rgba(0,0,0,.15); transition: transform .2s; margin-bottom: 10px; }
        .btn-dynamic-action:active { transform: scale(.97); }
        .status-btn-0 { background: var(--primary-accent); }
        .status-btn-1 { background: #f59e0b; }
        .status-btn-2 { background: #3b82f6; }
        .status-btn-5 { background: #10b981; }
        .status-btn-3 { background: #ef4444; }
        .status-btn-4 { background: #6b7280; opacity: .7; }
        .btn-whatsapp { background-color: #25D366; color: white; border: none; border-radius: 8px; padding: 8px; width: 100%; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; margin-top: 8px; font-size: .9rem; }
        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; height: calc(70px + var(--safe-bottom)); background-color: var(--bg-card); border-top: 1px solid var(--border-color); display: flex; justify-content: space-around; align-items: flex-start; z-index: 1080; padding-bottom: var(--safe-bottom); padding-top: 10px; }
        .nav-item-mobile { display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-muted); text-decoration: none; font-size: .75rem; font-weight: 500; width: 100%; height: 50px; transition: color .2s; }
        .nav-item-mobile i { font-size: 1.5rem; margin-bottom: 4px; }
        .nav-item-mobile.active { color: var(--primary-accent); }
        /* Airport overlay */
        #airportOverlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #000; z-index: 2000; display: none; flex-direction: column; justify-content: center; align-items: center; overflow: hidden; color: white; }
        #airportContentWrapper { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; overflow: hidden; }
        #airportOverlay.landscape-mode #airportContentWrapper { position: absolute; top: 50%; left: 50%; width: 100vh; height: 100vw; transform: translate(-50%,-50%) rotate(90deg); }
        #airportClientName { font-family: var(--font-display); font-weight: 900; line-height: .9; text-transform: uppercase; width: 100%; margin: 0; padding: 0 4vw; font-size: 15vw; word-wrap: normal; display: block; }
        .name-part { display: inline-block; white-space: nowrap; }
        #airportFlight { font-family: var(--font-primary); font-weight: 600; color: #FFD700; margin-top: 2vh; font-size: 6vw; letter-spacing: 2px; }
        .airport-controls { position: absolute; top: 20px; right: 20px; display: flex; gap: 25px; z-index: 2001; opacity: .2; transition: opacity .3s; }
        .airport-controls:hover { opacity: 1; }
        .airport-zoom-controls { position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%); display: flex; gap: 20px; z-index: 2002; opacity: .2; transition: opacity .3s; }
        .airport-zoom-controls:hover { opacity: 1; }
        .zoom-btn { width: 60px; height: 60px; border-radius: 50%; background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; backdrop-filter: blur(5px); cursor: pointer; }
        /* Camera overlay */
        #cameraOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; z-index: 99999; display: none; flex-direction: column; }
        #cameraViewArea { flex: 1; position: relative; overflow: hidden; background: #000; }
        #cameraStream, #photoCanvas { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; }
        .camera-ui-controls { position: absolute; bottom: 0; left: 0; width: 100%; padding: 40px 20px calc(40px + var(--safe-bottom)) 20px; background: linear-gradient(to top, #000, transparent); display: flex; justify-content: center; gap: 20px; align-items: center; }
        .camera-btn { width: 70px; height: 70px; border-radius: 50%; border: 4px solid white; background: transparent; display: flex; align-items: center; justify-content: center; padding: 0; }
        .camera-btn-inner { width: 56px; height: 56px; background: white; border-radius: 50%; transition: transform .1s; }
        .btn-circle-action { width: 50px; height: 50px; border-radius: 50%; border: none; background: rgba(255,255,255,.2); color: white; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }
    </style>
</head>
<body>

<div id="airportOverlay">
    <div class="airport-controls">
        <i class="bi bi-arrow-repeat text-white fs-1" id="rotateScreenBtn"></i>
        <i class="bi bi-x-lg text-white fs-1" id="closeAirportMode"></i>
    </div>
    <div id="airportContentWrapper">
        <h1 id="airportClientName">NOME</h1>
        <h2 id="airportFlight"></h2>
    </div>
    <div class="airport-zoom-controls">
        <div class="zoom-btn" id="btnZoomOut"><i class="bi bi-dash-lg"></i></div>
        <div class="zoom-btn" id="btnZoomIn"><i class="bi bi-plus-lg"></i></div>
    </div>
</div>

<div id="cameraOverlay">
    <div style="position:absolute;top:max(20px,env(safe-area-inset-top));left:0;width:100%;text-align:center;color:white;z-index:10;font-weight:600;text-shadow:0 2px 4px rgba(0,0,0,.8);" id="cameraInstruction">Photograph</div>
    <div id="cameraViewArea">
        <div id="cameraLoading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:white;display:none;text-align:center;">
            <div class="spinner-border mb-2"></div><div>Starting…</div>
        </div>
        <video id="cameraStream" autoplay playsinline style="transform-origin:center center;"></video>
        <canvas id="photoCanvas" style="display:none;"></canvas>
        <div id="cameraZoomStrip" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:12px;z-index:10;opacity:.7;">
            <button class="btn-circle-action" id="btnCameraZoomIn"><i class="bi bi-zoom-in"></i></button>
            <button class="btn-circle-action" id="btnCameraZoomOut"><i class="bi bi-zoom-out"></i></button>
        </div>
    </div>
    <div class="camera-ui-controls">
        <div id="stepCaptureControls" class="d-flex align-items-center gap-4">
            <button class="btn-circle-action" onclick="closeCameraOverlay()"><i class="bi bi-x-lg"></i></button>
            <button class="camera-btn" id="btnCapture"><div class="camera-btn-inner"></div></button>
            <button class="btn-circle-action" id="btnRotateCamera"><i class="bi bi-arrow-repeat"></i></button>
        </div>
        <div id="stepConfirmControls" class="d-none d-flex gap-3 w-100 justify-content-center">
            <button class="btn btn-light rounded-pill px-4 py-2 fw-bold" id="btnRetake">Retake</button>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold" id="btnConfirmSend">Send</button>
        </div>
    </div>
</div>

<header class="app-header">
    <img src="/SRMT/public/assets/images/icons/SyncRide.png" alt="SyncRide" class="brand-logo" id="driver-logo">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-link text-muted p-0 border-0" id="theme-toggle">
            <i class="bi bi-moon-stars-fill fs-5" id="theme-icon"></i>
        </button>
        <img src="<?= View::e($userPhotoSrc) ?>" class="user-avatar shadow-sm" alt="" data-bs-toggle="modal" data-bs-target="#photoModal">
    </div>
</header>

<div class="container-fluid px-3 pt-3">
    <div class="mb-4">
        <h4 class="fw-bold mb-3">Hello, <?= View::e($firstName) ?>!</h4>
        <div class="row g-3">
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon bg-indigo-soft"><i class="bi bi-calendar-check"></i></div>
                    <div class="stat-info"><h3><?= $todayCount ?></h3><p>Today</p></div>
                </div>
            </div>
            <div class="col-6">
                <div class="stat-card">
                    <div class="stat-icon bg-emerald-soft"><i class="bi bi-calendar-week"></i></div>
                    <div class="stat-info"><h3><?= $weekCount ?></h3><p>This Week</p></div>
                </div>
            </div>
        </div>
    </div>

    <div class="filter-container">
        <button class="filter-btn" data-filter="yesterday">Yesterday</button>
        <button class="filter-btn active" data-filter="today">Today</button>
        <button class="filter-btn" data-filter="tomorrow">Tomorrow</button>
    </div>

    <div id="rideList">
        <div class="text-center py-5 text-muted">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-2 small">Loading rides…</div>
        </div>
    </div>
</div>

<!-- Details modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Details</h5>
                    <small class="text-muted">ID #<span id="modalIdDisplay"></span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="info-box d-flex align-items-start gap-3">
                    <div class="bg-body-secondary rounded-circle p-2 mt-1"><i class="bi bi-person-fill fs-5 text-primary"></i></div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="info-label">Client</span>
                                <div id="modalClient" class="info-value text-break"></div>
                                <div id="modalClientNumber" class="small text-muted mt-1"></div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2" id="modalPaxBadge">
                                    <i class="bi bi-people-fill"></i> <span id="modalADT"></span>+<span id="modalCHD"></span>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-2" id="modalBadgesContainer"></div>
                        <div id="whatsappContainer" style="display:none;"></div>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-6"><button class="btn btn-sm btn-dark w-100 py-2 rounded-3 fw-bold" id="btnAirportMode"><i class="bi bi-signpost-2-fill me-1"></i> Sign</button></div>
                    <div class="col-6"><a href="#" id="trackFlightLink" target="_blank" class="btn btn-sm btn-info w-100 py-2 rounded-3 fw-bold text-white" style="display:none;"><i class="bi bi-airplane-fill me-1"></i> <span id="modalFlight"></span></a></div>
                </div>

                <div class="card p-2 border border-color shadow-none mb-3">
                    <div class="card-timeline" style="margin-left:0;padding-left:20px;">
                        <div class="ct-point"><div class="ct-dot dot-pickup"></div><span class="info-label">Pickup</span><div id="modalPickup" class="ct-text fw-bold lh-sm"></div></div>
                        <div class="ct-point mb-0"><div class="ct-dot dot-dropoff"></div><span class="info-label">Dropoff</span><div id="modalDropoff" class="ct-text fw-bold lh-sm"></div></div>
                    </div>
                </div>

                <button id="btnDynamicAction" class="btn-dynamic-action status-btn-0">START PICKUP</button>

                <div class="row g-2">
                    <div class="col-6"><button class="btn btn-sm btn-outline-secondary w-100 py-2 rounded-pill fw-bold" id="uploadVoucher"><i class="bi bi-ticket-perforated"></i> Voucher</button></div>
                    <div class="col-6"><button class="btn btn-sm btn-outline-danger w-100 py-2 rounded-pill fw-bold" id="uploadNoShow"><i class="bi bi-camera"></i> No-Show</button></div>
                </div>

                <a href="#" id="whatsappAlojamento" target="_blank" class="btn-whatsapp mt-2" style="display:none;">
                    <i class="bi bi-whatsapp"></i> Leaving airport
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Profile photo modal -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0"><h5 class="modal-title">Profile Photo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center">
                <form action="/SRMT/public/save-profile-photo.php" method="POST" enctype="multipart/form-data">
                    <img id="currentProfilePhoto" src="<?= View::e($userPhotoSrc) ?>" class="rounded-circle shadow mb-4" style="width:120px;height:120px;object-fit:cover;">
                    <input type="file" name="profile_photo" id="profilePhotoInput" class="form-control mb-3" accept="image/*" required>
                    <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-bold">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<nav class="bottom-nav">
    <a href="/SRMT/public/driver/"           class="nav-item-mobile active"><i class="bi bi-car-front-fill"></i><span>Rides</span></a>
    <a href="/SRMT/public/driver/agenda.php" class="nav-item-mobile"><i class="bi bi-calendar3"></i><span>Agenda</span></a>
    <a href="/SRMT/public/driver/stats.php"  class="nav-item-mobile"><i class="bi bi-bar-chart-fill"></i><span>Stats</span></a>
    <a href="/SRMT/public/auth/logout.php"   class="nav-item-mobile text-danger"><i class="bi bi-box-arrow-right"></i><span>Logout</span></a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Initial data from PHP
var viagens = <?= json_encode($rides, JSON_UNESCAPED_UNICODE) ?>;
var currentDriverId = <?= $driverId ?>;

// Theme
(function () {
    const html   = document.documentElement;
    const toggle = document.getElementById('theme-toggle');
    const icon   = document.getElementById('theme-icon');
    const logo   = document.getElementById('driver-logo');
    function applyTheme(t) {
        html.setAttribute('data-bs-theme', t);
        icon.className = t === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
        if (logo) logo.src = t === 'dark' ? '/SRMT/public/assets/images/icons/Syncridewhite.png' : '/SRMT/public/assets/images/icons/SyncRide.png';
    }
    applyTheme(localStorage.getItem('theme') || 'light');
    toggle.addEventListener('click', () => {
        const next = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', next);
        applyTheme(next);
    });
})();

// State
let backgroundWatcherId = null, trackingInterval = null, currentRideId = null, currentRideData = null;
let localTripStatus = {}, currentFilter = 'today', stream = null, currentMode = 'noshow';
let currentFacingMode = 'environment', locationWatcher = null, currentLat = null, currentLng = null;
let cameraZoomLevel = 1, pinchInitialDistance = 0, wakeLock = null;
viagens.forEach(v => { localTripStatus[String(v.ServiceID)] = parseInt(v.status_id) || 0; });

// Auto-refresh
function fetchLatestRides() {
    fetch('/SRMT/public/driver/?api=refresh').then(r => r.json()).then(data => {
        if (!Array.isArray(data)) return;
        viagens = data;
        viagens.forEach(v => { if (localTripStatus[String(v.ServiceID)] === undefined) localTripStatus[String(v.ServiceID)] = parseInt(v.status_id) || 0; });
        filterTrips(currentFilter);
    }).catch(() => {});
}
setInterval(fetchLatestRides, 15000);

// GPS
function sendPosition(position) {
    const finishBg = () => { if (window.Capacitor?.Plugins?.BackgroundGeolocation) Capacitor.Plugins.BackgroundGeolocation.finish(); };
    if (!currentRideId) { finishBg(); return; }
    const lat = position.latitude  ?? position.coords?.latitude;
    const lng = position.longitude ?? position.coords?.longitude;
    if (lat === undefined || lng === undefined) { finishBg(); return; }
    fetch('/SRMT/public/api/location-update.php', {
        method: 'POST',
        body: JSON.stringify({ ride_id: currentRideId, driver_id: currentDriverId, lat, lng, speed: position.speed ?? position.coords?.speed ?? 0, heading: position.bearing ?? position.coords?.heading ?? 0 }),
        headers: {'Content-Type': 'application/json'}
    }).catch(() => {}).finally(finishBg);
}
function startLiveTracking(rideId) {
    currentRideId = rideId;
    sessionStorage.setItem('activeRideId', rideId);
    if (window.Capacitor?.Plugins?.BackgroundGeolocation) {
        const BGeo = Capacitor.Plugins.BackgroundGeolocation;
        if (backgroundWatcherId) return;
        BGeo.addWatcher({ backgroundTitle: 'SyncRide in Service', backgroundMessage: 'Location is being shared', requestAllowAlwaysLocation: true, distanceFilter: 10, staleLocationThreshold: 30, radius: 20 },
            (loc, err) => { if (loc) sendPosition(loc); }).then(id => { backgroundWatcherId = id; });
    } else if ('geolocation' in navigator) {
        if (trackingInterval) clearInterval(trackingInterval);
        navigator.geolocation.getCurrentPosition(p => sendPosition(p));
        trackingInterval = setInterval(() => navigator.geolocation.getCurrentPosition(p => sendPosition(p)), 5000);
    }
}
function stopLiveTracking() {
    if (!currentRideId) return;
    sessionStorage.removeItem('activeRideId');
    fetch('/SRMT/public/api/tracking-stop.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ ride_id: currentRideId, driver_id: currentDriverId }) });
    currentRideId = null;
    if (window.Capacitor?.Plugins?.BackgroundGeolocation && backgroundWatcherId) { Capacitor.Plugins.BackgroundGeolocation.removeWatcher({ id: backgroundWatcherId }); backgroundWatcherId = null; }
    if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
}

// DOMContentLoaded
document.addEventListener('DOMContentLoaded', async () => {
    if (window.Capacitor?.isNativePlatform()) {
        const { Geolocation, Camera, BackgroundGeolocation } = Capacitor.Plugins;
        await Geolocation.requestPermissions(); await Camera.requestPermissions();
        if (BackgroundGeolocation.requestPermissions) await BackgroundGeolocation.requestPermissions();
    }
    const savedRideId = sessionStorage.getItem('activeRideId');
    if (savedRideId && !currentRideId) {
        fetch('/SRMT/public/driver/?api=refresh').then(r => r.json()).then(data => {
            if (!Array.isArray(data)) return;
            const ride = data.find(v => String(v.ServiceID) === savedRideId);
            if (ride && [1,2,5,3].includes(parseInt(ride.status_id))) { startLiveTracking(savedRideId); }
            else { sessionStorage.removeItem('activeRideId'); }
        }).catch(() => {});
    }
});
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        const savedRideId = sessionStorage.getItem('activeRideId');
        if (savedRideId && !currentRideId) startLiveTracking(savedRideId);
    }
});

// External links
async function openExternal(url) {
    try { if (window.Capacitor?.Plugins?.App?.openUrl) { await Capacitor.Plugins.App.openUrl({ url }); return; } } catch(e) {}
    window.open(url, '_system');
}
function openWaze(address) { openExternal('waze://?q=' + encodeURIComponent(address) + '&navigate=yes'); }
document.addEventListener('click', e => {
    const a = e.target.closest('a[target="_blank"]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href === '#') return;
    if (window.Capacitor?.isNativePlatform?.()) { e.preventDefault(); openExternal(href); }
});

// Status UI
function updateButtonUI(status) {
    const btn = document.getElementById('btnDynamicAction'); if (!btn) return;
    btn.className = 'btn-dynamic-action';
    const map = {
        0: ['status-btn-0', '<i class="bi bi-car-front-fill me-2"></i> START PICKUP',    false],
        1: ['status-btn-1', '<i class="bi bi-geo-alt-fill me-2"></i> ARRIVED',           false],
        2: ['status-btn-2', '<i class="bi bi-person-check-fill me-2"></i> WITH CLIENT',  false],
        5: ['status-btn-5', '<i class="bi bi-play-circle-fill me-2"></i> START TRIP',    false],
        3: ['status-btn-3', '<i class="bi bi-stop-circle-fill me-2"></i> FINISH',        false],
    };
    const def = ['status-btn-4', '<i class="bi bi-check-circle-fill me-2"></i> COMPLETED', true];
    const [cls, html, dis] = map[parseInt(status)] ?? def;
    btn.classList.add(cls); btn.innerHTML = html; btn.disabled = dis;
}
function updateStatusBackend(rideId, nextStatus) {
    const fd = new FormData(); fd.append('ride_id', rideId); fd.append('status', nextStatus);
    fetch('/SRMT/public/api/status-update.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (!d.success) return;
            localTripStatus[rideId] = nextStatus; updateButtonUI(nextStatus);
            if (parseInt(nextStatus) === 4) {
                fetch('/SRMT/public/api/final-trip-report.php?ride_id=' + rideId);
                setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide(); fetchLatestRides(); }, 1000);
            }
        });
}
document.getElementById('btnDynamicAction').addEventListener('click', function () {
    if (!currentRideData) return;
    const rideId = currentRideData.id;
    const cur = parseInt(localTripStatus[rideId]);
    const nextMap = {0:1, 1:2, 2:5, 5:3, 3:4};
    const nextStatus = nextMap[cur];
    if (nextStatus === undefined) return;
    if (cur === 0) {
        if (!confirm('Start pickup and open GPS?')) return;
        if (currentRideData.clientnumber) {
            const cNum = currentRideData.clientnumber.replace(/[^0-9]/g, '');
            if (cNum.length > 7) {
                const trackLink = window.location.origin + '/track.php?id=' + rideId;
                openExternal('https://wa.me/' + cNum + '?text=' + encodeURIComponent('Hello! Your driver is on the way. Track here: ' + trackLink));
            }
        }
        startLiveTracking(rideId); openWaze(currentRideData.start);
    } else if (cur === 1) { if (!confirm('Confirm you have arrived?')) return; }
    else if (cur === 2) { if (!confirm('Confirm you are with the client?')) return; }
    else if (cur === 5) { if (!confirm('Start trip to destination?')) return; openWaze(currentRideData.end); }
    else if (cur === 3) { if (!confirm('Finish service?')) return; stopLiveTracking(); }
    updateStatusBackend(rideId, nextStatus);
});

// Render
function formatDate(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
function filterTrips(filter) {
    currentFilter = filter;
    const t = new Date(), y = new Date(t), tm = new Date(t);
    y.setDate(t.getDate()-1); tm.setDate(t.getDate()+1);
    const map = { yesterday: formatDate(y), today: formatDate(t), tomorrow: formatDate(tm) };
    renderList(viagens.filter(v => v.serviceDate === map[filter]));
}
function renderList(data) {
    const el = document.getElementById('rideList'); if (!el) return; el.innerHTML = '';
    if (data.length === 0) { el.innerHTML = "<div class='text-center py-5 text-muted'><i class='bi bi-calendar-x fs-1 opacity-50'></i><p class='mt-2'>No services.</p></div>"; return; }
    data.forEach(v => {
        const isPriv = v.serviceType == 1;
        const badgeClass = isPriv ? 'badge-private' : 'badge-shared';
        let extraBadges = '';
        if (v.partner_id && v.partner_id > 0) {
            if (v.AgencyName) extraBadges += `<span class="ride-badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><i class="bi bi-building-fill"></i> ${v.AgencyName}</span>`;
            extraBadges += `<span class="ride-badge ${v.has_key == 1 ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25'}"><i class="bi bi-key-fill"></i> ${v.has_key == 1 ? 'Key' : 'No Key'}</span>`;
        }
        el.innerHTML += `<div class="ride-card open-modal" data-id="${v.ServiceID}" data-start="${v.serviceStartPoint}" data-end="${v.serviceTargetPoint}" data-time="${v.serviceStartTime.substr(0,5)}" data-date="${v.serviceDate}" data-paxadt="${v.paxADT||0}" data-paxchd="${v.paxCHD||0}" data-flight="${v.FlightNumber||''}" data-client="${v.NomeCliente||''}" data-clientnumber="${v.ClientNumber||''}" data-price="${v.total_price||''}" data-haskey="${v.has_key||0}" data-partnerid="${v.partner_id||0}" data-agencyname="${v.AgencyName||''}" data-agencyphone="${v.AgencyPhone||''}">
            <div class="ride-header"><div class="ride-time">${v.serviceStartTime.substr(0,5)}</div><div class="ride-badges-container"><span class="ride-badge ${badgeClass}">${isPriv?'Private':'Shared'}</span>${extraBadges}</div></div>
            <div class="card-timeline"><div class="ct-point"><div class="ct-dot dot-pickup"></div><span class="ct-text">${v.serviceStartPoint}</span></div><div class="ct-point"><div class="ct-dot dot-dropoff"></div><span class="ct-text">${v.serviceTargetPoint}</span></div></div>
            ${v.total_price > 0 ? `<div class="price-tag"><i class="bi bi-cash"></i> ${parseFloat(v.total_price).toFixed(2)}€</div>` : ''}</div>`;
    });
    document.querySelectorAll('.open-modal').forEach(card => {
        card.addEventListener('click', () => {
            const d = card.dataset; currentRideData = d;
            const m = document.getElementById('detailsModal');
            m.querySelector('#modalIdDisplay').textContent = d.id;
            m.querySelector('#modalPickup').textContent   = d.start;
            m.querySelector('#modalDropoff').textContent  = d.end;
            m.querySelector('#modalADT').textContent      = d.paxadt;
            m.querySelector('#modalCHD').textContent      = d.paxchd;
            m.querySelector('#modalClient').textContent   = d.client || 'Client';
            m.querySelector('#modalClientNumber').textContent = d.clientnumber;
            const wa = document.getElementById('whatsappContainer'); wa.innerHTML = ''; wa.style.display = 'none';
            if (d.clientnumber && d.clientnumber.replace(/[^0-9]/g,'').length > 7) { wa.style.display = 'block'; wa.innerHTML = `<a href="https://wa.me/${d.clientnumber.replace(/[^0-9]/g,'')}" target="_blank" class="btn-whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</a>`; }
            const waAloj = document.getElementById('whatsappAlojamento');
            if (d.partnerid && d.partnerid > 0 && d.agencyphone) {
                const ph = '351' + String(d.agencyphone).replace(/[^0-9]/g,'');
                waAloj.href = 'https://wa.me/' + ph + '?text=' + encodeURIComponent(`Leaving the airport 🛬\nClient: ${d.client}\nDestination: ${d.end}`);
                waAloj.style.display = 'flex';
            } else { waAloj.style.display = 'none'; }
            const bc = document.getElementById('modalBadgesContainer'); bc.innerHTML = '';
            if (d.price && parseFloat(d.price) > 0) bc.innerHTML += `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2"><i class="bi bi-currency-euro"></i> ${parseFloat(d.price).toFixed(2)}</span>`;
            if (d.partnerid && d.partnerid > 0) {
                if (d.agencyname) bc.innerHTML += `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2"><i class="bi bi-building"></i> ${d.agencyname}</span>`;
                bc.innerHTML += `<span class="badge bg-${d.haskey==1?'success':'danger'} bg-opacity-10 text-${d.haskey==1?'success':'danger'} border border-${d.haskey==1?'success':'danger'} border-opacity-25 rounded-pill px-2"><i class="bi bi-key-fill"></i> ${d.haskey==1?'With Key':'No Key'}</span>`;
            }
            const tl = document.getElementById('trackFlightLink');
            if (d.flight && d.flight.trim()) { tl.style.display = 'inline-flex'; document.getElementById('modalFlight').textContent = d.flight; tl.href = 'https://www.flightradar24.com/data/flights/' + d.flight.replace(/\s/g,''); }
            else { tl.style.display = 'none'; }
            updateButtonUI(localTripStatus[d.id]); new bootstrap.Modal(m).show();
        });
    });
}
document.querySelectorAll('.filter-btn').forEach(b => b.addEventListener('click', function () {
    document.querySelectorAll('.filter-btn').forEach(x => x.classList.remove('active'));
    this.classList.add('active'); filterTrips(this.dataset.filter);
}));
filterTrips('today');

// Airport sign
const airportOverlay = document.getElementById('airportOverlay');
const nameEl = document.getElementById('airportClientName');
let currentFontSize = 15;
document.getElementById('btnAirportMode').onclick = async () => {
    const rawName = currentRideData?.client || 'CLIENT';
    nameEl.innerHTML = rawName.trim().split(/\s+/).map(w => `<span class="name-part">${w}</span>`).join(' ');
    document.getElementById('airportFlight').textContent = currentRideData?.flight || '';
    currentFontSize = (rawName.trim().split(/\s+/).length <= 2) ? 18 : 12;
    nameEl.style.fontSize = currentFontSize + 'vw';
    airportOverlay.style.display = 'flex';
    if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    if ('wakeLock' in navigator) { try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {} }
};
function updateZoom(delta) {
    currentFontSize = Math.min(Math.max(currentFontSize + delta, 5), 50);
    const unit = airportOverlay.classList.contains('landscape-mode') ? 'vh' : 'vw';
    nameEl.style.fontSize = currentFontSize + unit;
}
document.getElementById('btnZoomIn').onclick  = e => { e.stopPropagation(); updateZoom(2); };
document.getElementById('btnZoomOut').onclick = e => { e.stopPropagation(); updateZoom(-2); };
document.getElementById('closeAirportMode').onclick = () => {
    airportOverlay.style.display = 'none';
    if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
    if (wakeLock) { wakeLock.release().catch(() => {}); wakeLock = null; }
};
document.getElementById('rotateScreenBtn').onclick = () => {
    airportOverlay.classList.toggle('landscape-mode');
    const isLandscape = airportOverlay.classList.contains('landscape-mode');
    nameEl.style.fontSize = currentFontSize + (isLandscape ? 'vh' : 'vw');
};
document.addEventListener('visibilitychange', async () => {
    if (wakeLock === null && document.visibilityState === 'visible' && airportOverlay.style.display !== 'none') {
        if ('wakeLock' in navigator) { try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {} }
    }
});

// Camera
const video = document.getElementById('cameraStream'), canvas = document.getElementById('photoCanvas');
const camOverlay = document.getElementById('cameraOverlay'), loading = document.getElementById('cameraLoading');
const btnSend = document.getElementById('btnConfirmSend');
function stopCameraStream() { if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; } if (locationWatcher) navigator.geolocation.clearWatch(locationWatcher); }
function closeCameraOverlay() { stopCameraStream(); camOverlay.style.display = 'none'; cameraZoomLevel = 1; video.style.transform = 'scale(1)'; }
function applyCameraZoom(delta) { cameraZoomLevel = Math.min(Math.max(cameraZoomLevel + delta, 1), 8); video.style.transform = `scale(${cameraZoomLevel})`; }
function updateCameraUI(state) {
    const cCap = document.getElementById('stepCaptureControls'), cConf = document.getElementById('stepConfirmControls');
    if (state==='loading')  { loading.style.display='block'; video.style.display='none'; canvas.style.display='none'; cCap.classList.add('d-none'); cConf.classList.add('d-none'); }
    else if (state==='capture') { loading.style.display='none'; video.style.display='block'; canvas.style.display='none'; cCap.classList.remove('d-none'); cCap.classList.add('d-flex'); cConf.classList.add('d-none'); }
    else if (state==='review')  { loading.style.display='none'; video.style.display='none'; canvas.style.display='block'; cCap.classList.add('d-none'); cConf.classList.remove('d-none'); cConf.classList.add('d-flex'); btnSend.disabled=false; }
}
async function startCamera() {
    stopCameraStream(); cameraZoomLevel = 1; video.style.transform = 'scale(1)';
    camOverlay.style.display = 'flex'; updateCameraUI('loading');
    try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: currentFacingMode } }); video.srcObject = stream; video.onloadedmetadata = () => updateCameraUI('capture'); }
    catch(e) { alert('Camera error: ' + e.message); closeCameraOverlay(); }
    if ('geolocation' in navigator) locationWatcher = navigator.geolocation.watchPosition(p => { currentLat = p.coords.latitude; currentLng = p.coords.longitude; });
}
document.getElementById('uploadNoShow').onclick = () => { currentMode = 'noshow'; document.getElementById('cameraInstruction').textContent = 'Photograph No-Show'; startCamera(); };
document.getElementById('uploadVoucher').onclick = () => { currentMode = 'voucher'; document.getElementById('cameraInstruction').textContent = 'Photograph Voucher'; startCamera(); };
document.getElementById('btnRotateCamera').onclick = () => { currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment'; startCamera(); };
document.getElementById('btnCapture').onclick = () => {
    const vw = video.videoWidth, vh = video.videoHeight;
    const sw = vw/cameraZoomLevel, sh = vh/cameraZoomLevel;
    canvas.width = vw; canvas.height = vh;
    canvas.getContext('2d').drawImage(video, (vw-sw)/2, (vh-sh)/2, sw, sh, 0, 0, vw, vh);
    updateCameraUI('review');
};
document.getElementById('btnRetake').onclick = () => updateCameraUI('capture');
document.getElementById('btnCameraZoomIn').onclick  = e => { e.stopPropagation(); applyCameraZoom(.5); };
document.getElementById('btnCameraZoomOut').onclick = e => { e.stopPropagation(); applyCameraZoom(-.5); };
const cameraViewArea = document.getElementById('cameraViewArea');
cameraViewArea.addEventListener('touchstart', e => { if (e.touches.length === 2) pinchInitialDistance = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY); }, { passive: true });
cameraViewArea.addEventListener('touchmove', e => {
    if (e.touches.length === 2 && pinchInitialDistance > 0) {
        const dist = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
        cameraZoomLevel = Math.min(Math.max(cameraZoomLevel + (dist - pinchInitialDistance) * .01, 1), 8);
        video.style.transform = `scale(${cameraZoomLevel})`; pinchInitialDistance = dist;
    }
}, { passive: true });
btnSend.onclick = () => {
    btnSend.disabled = true;
    const img = canvas.toDataURL('image/jpeg');
    const url = currentMode === 'voucher' ? '/SRMT/public/admin/upload-voucher.php' : '/SRMT/public/admin/upload-no-show.php';
    fetch(url, { method: 'POST', body: JSON.stringify({ trip_id: currentRideData.id, image_data: img, lat: currentLat, lng: currentLng }) })
        .then(r => r.json()).then(d => {
            if (d.success) { alert(d.message); closeCameraOverlay(); if (currentMode === 'noshow') updateStatusBackend(currentRideData.id, 4); }
            else { btnSend.disabled = false; }
        });
};
document.getElementById('profilePhotoInput').onchange = e => { const [f] = e.target.files; if (f) document.getElementById('currentProfilePhoto').src = URL.createObjectURL(f); };
</script>
</body>
</html>
