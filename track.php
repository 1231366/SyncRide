<?php
session_start();
require 'auth/dbconfig.php';

$rideId = $_GET['id'] ?? null;
if (!$rideId) die("Error: Ride not specified.");

// Fetch ride info
$stmt = $pdo->prepare("
    SELECT t.*, s.serviceTargetPoint, s.NomeCliente, u.name AS driver_name
    FROM RideTracking t
    LEFT JOIN Services s ON t.ride_id = s.ID
    LEFT JOIN Users u ON t.driver_id = u.id
    WHERE t.ride_id = ?
");
$stmt->execute([$rideId]);
$trip = $stmt->fetch(PDO::FETCH_ASSOC);

$driverName = $trip['driver_name'] ?? 'SyncRide Driver';
$lat = $trip['latitude'] ?? 41.15;
$lng = $trip['longitude'] ?? -8.62;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Ride | SyncRide</title>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

<style>
html, body { margin:0; padding:0; height:100%; font-family:sans-serif; }
#map { height:100%; width:100%; }

.info-bar {
    position:absolute; top:20px; left:50%; transform:translateX(-50%);
    background: rgba(0,0,0,0.6); color:#fff;
    padding:12px 20px; border-radius:25px;
    font-size:16px; font-weight:600; z-index:9999;
    display:flex; flex-direction:column; align-items:center; gap:5px;
    min-width:200px; text-align:center;
}
.eta { font-size:22px; font-weight:900; color:#0d6efd; }

.driver-sheet {
    position: absolute; left: 50%; bottom: 20px; transform: translateX(-50%) translateY(120%);
    z-index: 9999; width: 90%; max-width: 380px; background:#fff; border-radius:20px;
    padding:16px; box-shadow: 0 15px 40px rgba(0,0,0,0.25);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.driver-sheet.active { transform: translateX(-50%) translateY(0); }

.sheet-top { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
.sheet-avatar { width:46px; height:46px; border-radius:50%; background:#f3f4f6;
    display:flex; align-items:center; justify-content:center; font-size:22px; color:#6b7280; flex-shrink:0;
}
.sheet-info h4 { margin:0; font-size:1rem; font-weight:700; color:#111; }
.sheet-info p { margin:0; font-size:0.8rem; color:#666; }

.sheet-stats { display:flex; gap:10px; margin-bottom:14px; }
.stat-box { flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px;
    padding:10px; text-align:center;
}
.stat-label { display:block; font-size:0.65rem; text-transform:uppercase; color:#94a3b8; font-weight:700; margin-bottom:2px;}
.stat-value { font-size:0.95rem; font-weight:800; color:#0f1724; }

.dest-bar { background:#eff6ff; color:#1e40af; padding:12px; border-radius:12px;
    display:flex; align-items:center; gap:10px; font-size:0.9rem; font-weight:600; margin-bottom:12px; }
.dest-bar i { font-size:1.1rem; }
.dest-text { white-space: nowrap; overflow:hidden; text-overflow:ellipsis; }

.btn-close-sheet {
    width: 100%; background:#0f1724; color:white; border:none; padding:12px; border-radius:12px;
    font-size:0.9rem; font-weight:600; cursor:pointer; transition:background 0.2s;
}
.btn-close-sheet:hover { background:#1f2937; }

.last-update-text {
    display:block; width:100%; text-align:center;
    font-size:0.7rem; color:#9ca3af; margin-top:10px; font-weight:500;
}

/* Car SVG */
.car-marker-container { pointer-events:auto; }
.car-body svg { width:34px; height:auto; display:block; filter: drop-shadow(0 3px 6px rgba(0,0,0,0.4)); transition: transform 0.5s linear; }
</style>
</head>
<body>

<div id="map"></div>
<div class="info-bar">
    <div id="driverText"><?php echo htmlspecialchars($driverName); ?> is on the way</div>
    <div id="eta" class="eta">-- min</div>
</div>

<div class="driver-sheet" id="driverSheet" aria-hidden="true">
    <div class="sheet-top">
        <div class="sheet-avatar"><i class="bi bi-person-fill"></i></div>
        <div class="sheet-info">
            <h4 id="sDriver">Driver</h4>
            <p id="sVehicle">Vehicle --</p>
        </div>
    </div>
    
    <div class="sheet-stats">
        <div class="stat-box">
            <span class="stat-label">Speed</span>
            <span class="stat-value" id="sSpeed">0 km/h</span>
        </div>
        <div class="stat-box">
            <span class="stat-label">Client</span>
            <span class="stat-value" id="sClient">--</span>
        </div>
    </div>

    <div class="dest-bar">
        <i class="bi bi-geo-alt-fill"></i>
        <div class="dest-text" id="sDest">No destination</div>
    </div>

    <button class="btn-close-sheet" onclick="closeSheet()">Close</button>
    <span class="last-update-text" id="sUpdate">Updated: --:--:--</span>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

<script>
var map = L.map('map', { zoomControl: false, attributionControl: false }).setView([<?php echo $lat; ?>, <?php echo $lng; ?>], 15);
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom:20 }).addTo(map);

// Car SVG exactly like admin
const carSvg = `
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 50 100">
  <path d="M5,15 Q25,-5 45,15 L48,85 Q48,98 25,98 Q2,98 2,85 Z" fill="#d1d5db" stroke="#999" stroke-width="1"/>
  <path d="M6,30 Q25,25 44,30 L42,45 Q25,40 8,45 Z" fill="#1f2937"/>
  <path d="M10,75 Q25,72 40,75 L38,82 Q25,85 12,82 Z" fill="#1f2937"/>
  <rect x="4" y="90" width="8" height="3" fill="#dc2626" rx="1"/>
  <rect x="38" y="90" width="8" height="3" fill="#dc2626" rx="1"/>
</svg>`;

var carMarker = L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>], {
    icon: L.divIcon({ className: 'car-marker-container', html: `<div class="car-body">${carSvg}</div>`, iconSize:[30,60], iconAnchor:[15,30] })
}).addTo(map);

var clientLat, clientLng;
if(navigator.geolocation){
    navigator.geolocation.getCurrentPosition(pos=>{
        clientLat = pos.coords.latitude;
        clientLng = pos.coords.longitude;

        var routingControl = L.Routing.control({
            waypoints: [L.latLng(<?php echo $lat; ?>, <?php echo $lng; ?>), L.latLng(clientLat, clientLng)],
            routeWhileDragging: false,
            show:false,
            addWaypoints:false,
            lineOptions:{ styles:[{color:'#0d6efd', opacity:0.8, weight:6}] },
            createMarker: i => i===0 ? carMarker : L.marker([clientLat, clientLng])
        }).addTo(map);

        routingControl.on('routesfound', e=>{
            var summary = e.routes[0].summary;
            document.getElementById('eta').innerText = Math.round(summary.totalTime/60) + " min";
        });

        setInterval(()=>{
            fetch(`Includes/dist/pages/api_get_tracking.php?ride_id=<?php echo $rideId; ?>`)
            .then(r=>r.json())
            .then(res=>{
                if(res.success && res.data){
                    let d = res.data;
                    carMarker.setLatLng([d.latitude, d.longitude]);
                }
            });
        },4000);

    }, ()=> alert("Could not get your location. Enable GPS."));
} else alert("Geolocation not supported.");

function closeSheet(){
    document.getElementById('driverSheet').classList.remove('active');
}

</script>
</body>
</html>
