<?php
use App\Http\View;
/** @var string $userPhoto */
$safePhoto = View::e($userPhoto);
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>Live Radar — SyncRide OS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #000; margin: 0; overflow: hidden; height: 100vh; color: #fff; }

        #adminMap { position: absolute; top: 0; bottom: 0; left: 0; right: 0; z-index: 1; background: #0b1220; }
        .glass { background: rgba(255,255,255,0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.1); }

        .map-header {
            position: absolute; top: 30px; left: 15px; right: 15px;
            z-index: 1000; height: 64px; display: flex; align-items: center;
            padding: 0 16px; justify-content: space-between; border-radius: 24px;
        }

        .radar-pill {
            position: absolute; top: 110px; left: 50%; transform: translateX(-50%);
            z-index: 1000; padding: 8px 16px; border-radius: 99px;
            display: flex; align-items: center; gap: 10px;
            font-size: 10px; font-weight: 800; letter-spacing: 0.1em; color: white;
            background: rgba(255,255,255,0.05); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .pulse-dot {
            width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;
            box-shadow: 0 0 12px #3b82f6; animation: radar-pulse 2s infinite;
        }
        @keyframes radar-pulse {
            0%   { opacity: 1; transform: scale(1);   }
            50%  { opacity: 0.4; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1);   }
        }

        .driver-sheet {
            position: fixed; bottom: calc(100px + var(--safe-bottom)); left: 16px; right: 16px;
            z-index: 2000; padding: 24px; border-radius: 32px; transform: translateY(150%);
            transition: transform 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            background: rgba(20,20,20,0.95); backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .driver-sheet.active { transform: translateY(0); }

        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
            padding: 4px 10px; border-radius: 999px; margin-top: 6px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .status-pill::before { content: ""; width: 6px; height: 6px; border-radius: 999px; }
        .status-pill.s-pending   { color: #d4d4d8; background: rgba(255,255,255,0.04); }
        .status-pill.s-pending::before  { background: #d4d4d8; }
        .status-pill.s-onway    { color: #60a5fa; background: rgba(59,130,246,0.10); border-color: rgba(59,130,246,0.25); }
        .status-pill.s-onway::before    { background: #60a5fa; box-shadow: 0 0 8px #60a5fa; }
        .status-pill.s-arrived  { color: #fbbf24; background: rgba(251,191,36,0.10); border-color: rgba(251,191,36,0.25); }
        .status-pill.s-arrived::before  { background: #fbbf24; box-shadow: 0 0 8px #fbbf24; }
        .status-pill.s-withclient { color: #34d399; background: rgba(16,185,129,0.10); border-color: rgba(16,185,129,0.25); }
        .status-pill.s-withclient::before { background: #34d399; box-shadow: 0 0 8px #34d399; }
        .status-pill.s-intrip   { color: #a5b4fc; background: rgba(99,102,241,0.10); border-color: rgba(99,102,241,0.30); }
        .status-pill.s-intrip::before   { background: #a5b4fc; box-shadow: 0 0 8px #a5b4fc; }

        .fit-btn {
            position: absolute; right: 20px; bottom: calc(180px + var(--safe-bottom));
            z-index: 1000; width: 44px; height: 44px; border-radius: 14px;
            background: rgba(20,20,20,0.92); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.12); color: #fff;
            display: flex; align-items: center; justify-content: center; transition: all .2s;
        }
        .fit-btn:hover { background: rgba(40,40,40,0.95); }
        .fit-btn:active { transform: scale(0.92); }

        .nav-float {
            position: fixed; bottom: calc(16px + var(--safe-bottom)); left: 50%; transform: translateX(-50%);
            width: calc(100% - 32px); max-width: 400px; height: 72px;
            background: rgba(18,18,18,0.95); backdrop-filter: blur(25px);
            border-radius: 26px; border: 1px solid rgba(255,255,255,0.1);
            display: flex; justify-content: space-around; align-items: center; z-index: 3000;
        }
        .nav-float a { flex: 1; }
        .nav-float .nav-extra { display: none !important; }
        @media (min-width: 992px) {
            .nav-float { max-width: 720px; height: 78px; border-radius: 32px; }
            .nav-float .nav-extra { display: flex !important; }
            .nav-float a span { font-size: 8px !important; }
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }

        .leaflet-vignette { position: absolute; inset: 0; pointer-events: none; z-index: 500; background: radial-gradient(circle, transparent 30%, black 150%); }
        .leaflet-control-zoom { border: none !important; margin-right: 20px !important; margin-bottom: 110px !important; }
        .leaflet-bar a { background: rgba(20,20,20,0.9) !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 12px !important; }
        .leaflet-routing-container, .leaflet-routing-alt { display: none !important; }
        .car-marker { filter: drop-shadow(0 4px 8px rgba(0,0,0,0.8)); transition: all 1s ease; }

        .dest-pin { position: relative; width: 32px; height: 32px; pointer-events: none; }
        .dest-pin .pin-core {
            position: absolute; inset: 10px; background: #10b981; border-radius: 50%;
            border: 2px solid #050505;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.28), 0 6px 14px rgba(0,0,0,0.55), inset 0 1px 1px rgba(255,255,255,0.35);
        }
        .dest-pin .pin-ring {
            position: absolute; inset: 0; border-radius: 50%;
            border: 1.5px solid rgba(16,185,129,0.55);
            animation: pin-pulse 2.4s ease-out infinite;
        }
        .dest-pin.dropoff .pin-core {
            background: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.28), 0 6px 14px rgba(0,0,0,0.55), inset 0 1px 1px rgba(255,255,255,0.35);
        }
        .dest-pin.dropoff .pin-ring { border-color: rgba(239,68,68,0.55); }
        @keyframes pin-pulse { 0% { transform: scale(0.55); opacity: 1; } 100% { transform: scale(1.7); opacity: 0; } }
    </style>
</head>
<body>

    <div id="adminMap"></div>
    <div class="leaflet-vignette"></div>

    <header class="map-header glass text-white">
        <div class="flex items-center gap-3">
            <img src="<?= $safePhoto ?>" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover" alt="">
            <div>
                <h2 class="text-[15px] font-extrabold leading-tight">SyncRide <span class="text-blue-500">Radar</span></h2>
                <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">Active Intelligence</p>
            </div>
        </div>
        <button onclick="toggleMenu()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform" aria-label="Menu">
            <i data-lucide="menu" class="w-4 h-4 text-white"></i>
        </button>
    </header>

    <div class="radar-pill">
        <div class="pulse-dot"></div>
        <span id="activeCount" class="font-black">0</span>
        <span class="opacity-70">ACTIVE RIDES</span>
    </div>

    <button class="fit-btn" onclick="fitAllDrivers()" aria-label="Fit all drivers">
        <i data-lucide="maximize-2" class="w-5 h-5"></i>
    </button>

    <div id="driverSheet" class="driver-sheet">
        <div class="w-12 h-1 bg-zinc-800 rounded-full mx-auto mb-6 cursor-pointer" onclick="closeSheet()"></div>
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 id="sDriver" class="text-lg font-black text-white" data-did="">Driver</h3>
                <p id="sVehicle" class="text-[9px] text-blue-500 font-bold uppercase tracking-widest">Vehicle</p>
                <span id="sStatus" class="status-pill s-pending">—</span>
            </div>
            <div class="text-right">
                <p class="text-zinc-500 text-[8px] font-bold uppercase">Speed</p>
                <p id="sSpeed" class="text-xl font-black text-white">0 km/h</p>
                <p id="sLastUpdate" class="text-zinc-500 text-[8px] font-bold uppercase mt-1">Ping —</p>
            </div>
        </div>
        <div class="space-y-4">
            <div class="bg-white/5 p-4 rounded-2xl space-y-3">
                <div class="flex items-center gap-3">
                    <i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i>
                    <p id="sDest" class="text-[10px] font-medium text-zinc-300 truncate">---</p>
                </div>
                <div class="flex items-center gap-3">
                    <i data-lucide="user" class="w-3 h-3 text-blue-500"></i>
                    <p id="sClient" class="text-[10px] font-medium text-zinc-300">---</p>
                </div>
            </div>
            <button onclick="closeSheet()" class="w-full py-3 bg-white/5 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-white/10 transition-all">
                Close Details
            </button>
        </div>
    </div>

    <nav class="nav-float">
        <a href="/SRMT/public/admin/" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="home" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Home</span></a>
        <a href="/SRMT/public/admin/rides.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="calendar" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Rides</span></a>
        <a href="/SRMT/public/admin/live-map.php" class="flex flex-col items-center gap-1 text-blue-500"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Live</span></a>
        <a href="/SRMT/public/admin/financial.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="wallet" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Cash</span></a>
        <a href="/SRMT/public/admin/fleet.php"   class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="truck" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Fleet</span></a>
        <a href="/SRMT/public/admin/users.php"   class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="users" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Team</span></a>
        <a href="/SRMT/public/admin/driver-stats.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Stats</span></a>
        <a href="/SRMT/public/admin/no-shows.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="alert-triangle" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">No Show</span></a>
        <a href="/SRMT/public/admin/storage.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="database" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Storage</span></a>
    </nav>

    <div id="fullMenu" class="fixed inset-0 z-[2000] hidden">
        <div class="absolute inset-0 bg-black/98 backdrop-blur-2xl" onclick="toggleMenu()"></div>
        <div class="relative h-full flex flex-col p-10 text-white overflow-y-auto no-scrollbar">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span class="text-blue-600">OS</span></h2>
                <button onclick="toggleMenu()" class="w-12 h-12 glass rounded-full flex items-center justify-center"><i data-lucide="x"></i></button>
            </div>
            <nav class="grid grid-cols-1 gap-6 text-xl font-bold">
                <a href="/SRMT/public/admin/"               class="flex items-center gap-4"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="/SRMT/public/admin/rides.php"      class="flex items-center gap-4"><i data-lucide="navigation"></i> Rides</a>
                <a href="/SRMT/public/admin/live-map.php"   class="flex items-center gap-4 text-blue-500"><i data-lucide="map"></i> Live Map</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/admin/users.php"      class="flex items-center gap-4"><i data-lucide="users"></i> Team</a>
                <a href="/SRMT/public/admin/fleet.php"      class="flex items-center gap-4"><i data-lucide="truck"></i> Fleet</a>
                <a href="/SRMT/public/admin/financial.php"  class="flex items-center gap-4"><i data-lucide="banknote"></i> Financial</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/admin/driver-stats.php" class="flex items-center gap-4"><i data-lucide="bar-chart-3"></i> Stats</a>
                <a href="/SRMT/public/admin/no-shows.php"   class="flex items-center gap-4"><i data-lucide="alert-triangle"></i> No-shows</a>
                <a href="/SRMT/public/admin/storage.php"    class="flex items-center gap-4"><i data-lucide="database"></i> Storage</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/auth/logout.php"      class="flex items-center gap-4 text-red-500 mt-4"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function toggleMenu() { document.getElementById('fullMenu').classList.toggle('hidden'); }

        const map = L.map('adminMap', { zoomControl: false, attributionControl: false, center: [41.15, -8.62], zoom: 13 });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19, keepBuffer: 8, updateWhenIdle: false, updateWhenZooming: false
        }).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        const carIconSvg = (heading = 0) => `
            <svg width="40" height="40" viewBox="0 0 100 100" class="car-marker" style="transform:rotate(${heading}deg)">
                <circle cx="50" cy="50" r="45" fill="#3b82f6" fill-opacity="0.2" stroke="#3b82f6" stroke-width="2"/>
                <circle cx="50" cy="50" r="10" fill="#3b82f6"/>
                <path d="M50 10 L50 35" stroke="#fff" stroke-width="8" stroke-linecap="round"/>
            </svg>`;

        const geoCache = {};
        async function getCoords(address) {
            if (!address || address === 'N/A') return null;
            if (geoCache[address]) return geoCache[address];
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&countrycodes=pt&q=${encodeURIComponent(address + ', Portugal')}`);
                const data = await res.json();
                if (data.length) { const ll = L.latLng(data[0].lat, data[0].lon); geoCache[address] = ll; return ll; }
            } catch (e) {}
            return null;
        }

        function destIconFor(pinType) {
            return L.divIcon({
                html: `<div class="dest-pin ${pinType}"><div class="pin-ring"></div><div class="pin-core"></div></div>`,
                className: '', iconSize: [32, 32], iconAnchor: [16, 16]
            });
        }

        function targetAddressFor(d) {
            return parseInt(d.status_id) === 3 ? d.serviceTargetPoint : d.serviceStartPoint;
        }

        async function ensureRoute(entry) {
            const d = entry.data;
            const status = parseInt(d.status_id);
            const targetAddr = targetAddressFor(d);
            if (status === 4 || !targetAddr) {
                if (entry.routingControl) { map.removeControl(entry.routingControl); entry.routingControl = null; entry.lastTarget = null; }
                if (entry.destMarker)     { map.removeLayer(entry.destMarker); entry.destMarker = null; entry.destPinType = null; }
                return;
            }
            const targetCoords = await getCoords(targetAddr);
            if (!targetCoords) return;
            const driverPos = entry.marker.getLatLng();
            const pinType = status === 3 ? 'dropoff' : 'pickup';
            if (!entry.destMarker) {
                entry.destMarker = L.marker(targetCoords, { icon: destIconFor(pinType), interactive: false }).addTo(map);
                entry.destPinType = pinType;
            } else {
                entry.destMarker.setLatLng(targetCoords);
                if (entry.destPinType !== pinType) { entry.destMarker.setIcon(destIconFor(pinType)); entry.destPinType = pinType; }
            }
            if (!entry.routingControl || entry.lastTarget !== targetAddr) {
                if (entry.routingControl) map.removeControl(entry.routingControl);
                entry.routingControl = L.Routing.control({
                    waypoints: [driverPos, targetCoords],
                    routeWhileDragging: false, addWaypoints: false, show: false,
                    lineOptions: { styles: [{ color: '#3b82f6', opacity: 0.85, weight: 5 }] },
                    createMarker: () => null, fitSelectedRoutes: false
                }).addTo(map);
                entry.lastTarget = targetAddr;
            } else {
                entry.routingControl.setWaypoints([driverPos, targetCoords]);
            }
        }

        function formatAgo(lastUpdate) {
            if (!lastUpdate) return "—";
            const ts = new Date(String(lastUpdate).replace(' ', 'T')).getTime();
            if (isNaN(ts)) return "—";
            const diff = Math.max(0, Date.now() - ts);
            const s = Math.floor(diff / 1000);
            if (s < 3)  return "just now";
            if (s < 60) return s + "s ago";
            const m = Math.floor(s / 60);
            if (m < 60) return m + "m ago";
            return Math.floor(m / 60) + "h ago";
        }

        function setStatusPill(d) {
            const el = document.getElementById('sStatus');
            if (!el) return;
            const s = parseInt(d.status_id);
            el.className = 'status-pill';
            let cls = 's-pending', label = 'Awaiting';
            if (s === 1) { cls = 's-onway';      label = 'On the way'; }
            else if (s === 2) { cls = 's-arrived';    label = 'Arrived'; }
            else if (s === 5) { cls = 's-withclient'; label = 'With client'; }
            else if (s === 3) { cls = 's-intrip';     label = 'In trip'; }
            el.classList.add(cls);
            el.textContent = label;
        }

        let drivers = {}, isFetching = false;
        const today = new Date().toISOString().split('T')[0];
        let lastUpdateTimer = null, didInitialFit = false;

        function startLastUpdateTimer() {
            stopLastUpdateTimer();
            lastUpdateTimer = setInterval(() => {
                const did = document.getElementById('sDriver').dataset.did;
                if (!did || !drivers[did]) return;
                const el = document.getElementById('sLastUpdate');
                if (el) el.textContent = "Ping " + formatAgo(drivers[did].data.last_update);
            }, 1000);
        }
        function stopLastUpdateTimer() { if (lastUpdateTimer) { clearInterval(lastUpdateTimer); lastUpdateTimer = null; } }

        function fitAllDrivers() {
            const pos = Object.values(drivers).map(e => e.marker.getLatLng()).filter(Boolean);
            if (!pos.length) return;
            if (pos.length === 1) { map.setView(pos[0], 15, { animate: true }); return; }
            map.fitBounds(L.latLngBounds(pos), { padding: [80, 80], maxZoom: 14, animate: true });
        }

        function refresh() {
            if (isFetching) return;
            isFetching = true;
            fetch('/SRMT/public/api/tracking-get.php').then(r => r.json()).then(res => {
                isFetching = false;
                if (!res.success || !res.data) return;
                const NOW = Date.now(), STALE = 10 * 60 * 1000;
                let active = res.data.filter(d => {
                    if (d.serviceDate !== today) return false;
                    if (parseInt(d.status_id) === 4) return false;
                    if (d.last_update) {
                        const ts = new Date(String(d.last_update).replace(' ', 'T')).getTime();
                        if (!isNaN(ts) && (NOW - ts) > STALE) return false;
                    }
                    return true;
                });
                const grouped = {};
                active.forEach(d => { if (!grouped[d.driver_id] || parseInt(d.ride_id) > parseInt(grouped[d.driver_id].ride_id)) grouped[d.driver_id] = d; });
                const processed = Object.values(grouped);
                document.getElementById('activeCount').textContent = processed.length;
                const activeIds = [];

                processed.forEach(d => {
                    const id = String(d.driver_id);
                    activeIds.push(id);
                    const pos = [parseFloat(d.latitude), parseFloat(d.longitude)];
                    if (!drivers[id]) {
                        const icon = L.divIcon({ className: 'custom-car', html: carIconSvg(d.heading), iconSize: [40, 40], iconAnchor: [20, 20] });
                        const m = L.marker(pos, { icon }).addTo(map);
                        m.on('click', () => openSheet(id));
                        drivers[id] = { marker: m, data: d, routingControl: null, lastTarget: null, destMarker: null, destPinType: null };
                    } else {
                        drivers[id].data = d;
                        drivers[id].marker.setLatLng(pos);
                        const el = drivers[id].marker.getElement();
                        if (el) { const svg = el.querySelector('svg'); if (svg) svg.style.transform = `rotate(${d.heading || 0}deg)`; }
                    }
                    ensureRoute(drivers[id]);
                    if (isSheetOpen(id)) updateSheetData(d);
                });

                Object.keys(drivers).forEach(eid => {
                    if (!activeIds.includes(eid)) {
                        if (drivers[eid].routingControl) map.removeControl(drivers[eid].routingControl);
                        if (drivers[eid].destMarker)     map.removeLayer(drivers[eid].destMarker);
                        map.removeLayer(drivers[eid].marker);
                        if (isSheetOpen(eid)) closeSheet();
                        delete drivers[eid];
                    }
                });

                if (!didInitialFit && processed.length > 0) { didInitialFit = true; fitAllDrivers(); }
            }).catch(() => isFetching = false);
        }

        function openSheet(id) {
            const d = drivers[id].data;
            document.getElementById('sDriver').textContent = d.driver_name;
            document.getElementById('sDriver').dataset.did = id;
            document.getElementById('sSpeed').textContent = Math.round(d.speed || 0) + " km/h";
            document.getElementById('sLastUpdate').textContent = "Ping " + formatAgo(d.last_update);
            document.getElementById('sDest').textContent = d.serviceTargetPoint || "En route…";
            document.getElementById('sClient').textContent = d.NomeCliente || "—";
            document.getElementById('sVehicle').textContent = "Vehicle " + (d.vehicle_plate || "Sync-X");
            setStatusPill(d);
            document.getElementById('driverSheet').classList.add('active');
            startLastUpdateTimer();
            map.flyTo(drivers[id].marker.getLatLng(), 16, { duration: 1 });
        }

        function updateSheetData(d) {
            document.getElementById('sSpeed').textContent = Math.round(d.speed || 0) + " km/h";
            document.getElementById('sLastUpdate').textContent = "Ping " + formatAgo(d.last_update);
            setStatusPill(d);
        }

        function closeSheet() {
            document.getElementById('driverSheet').classList.remove('active');
            document.getElementById('sDriver').dataset.did = "";
            stopLastUpdateTimer();
        }

        function isSheetOpen(id) {
            return document.getElementById('driverSheet').classList.contains('active') &&
                   document.getElementById('sDriver').dataset.did === id;
        }

        setInterval(refresh, 3000);
        refresh();
        window.addEventListener('resize', () => map.invalidateSize());
    </script>
</body>
</html>
