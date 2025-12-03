<?php
session_start(); 
require __DIR__ . '/../../../auth/dbconfig.php'; 

$viagens = [];
$serviceTypeFilter = isset($_GET['serviceType']) ? $_GET['serviceType'] : null; 

if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 2) {
    $userId = $_SESSION['user_id']; 

    try {
        $query = "
        SELECT 
            s.ID AS ServiceID, 
            s.serviceDate, 
            s.serviceStartTime, 
            s.serviceStartPoint, 
            s.serviceTargetPoint,
            s.paxADT,
            s.paxCHD,
            s.FlightNumber,
            s.NomeCliente,
            s.ClientNumber,
            s.serviceType 
        FROM Services_Rides sr
        INNER JOIN Services s ON sr.RideID = s.ID
        WHERE sr.UserID = ?";

        if ($serviceTypeFilter !== null) {
            $query .= " AND s.serviceType = ?";
        }

        $query .= " ORDER BY s.serviceDate ASC, s.serviceStartTime ASC";

        $stmt = $pdo->prepare($query);

        if ($serviceTypeFilter !== null) {
            $stmt->execute([$userId, $serviceTypeFilter]);
        } else {
            $stmt->execute([$userId]);
        }

        $viagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao recuperar viagens: " . $e->getMessage();
    }
} else {
    header("refresh: 1; url=../../../index.php");
    exit();
}

echo "<script> 
    var viagens = " . json_encode($viagens) . ";
    var currentDriverId = " . $_SESSION['user_id'] . ";
</script>";
?>

<!doctype html>
<html lang="pt">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Painel de Condutor</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="../../dist/css/adminlte.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css" />
    
    <style>
        #airportOverlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #000; z-index: 99999; display: none; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 20px; }
        #airportClientName { color: #fff; font-weight: 900; line-height: 1.1; text-transform: uppercase; margin: 0; font-size: 12vw; }
        .airport-controls { position: absolute; top: 20px; right: 20px; display: flex; gap: 15px; z-index: 100001; }
        .airport-icon { color: #555; font-size: 2rem; cursor: pointer; transition: color 0.3s; }
        .airport-icon:hover { color: #fff; }
        .rotate-mode .content-wrapper { transform: rotate(90deg); width: 100vh; height: 100vw; }

        .route-timeline { position: relative; padding-left: 20px; border-left: 2px dashed #dee2e6; margin: 15px 0 25px 5px; }
        .rt-item { position: relative; margin-bottom: 20px; }
        .rt-item:last-child { margin-bottom: 0; }
        .rt-dot { position: absolute; left: -26px; top: 2px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #fff; }
        .dot-green { background: #198754; box-shadow: 0 0 0 3px #d1e7dd; }
        .dot-red { background: #dc3545; box-shadow: 0 0 0 3px #f8d7da; }
        .btn-modal-action { display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px; font-weight: 600; border-radius: 12px; }

        .card-header-custom { background: linear-gradient(90deg, #00b0ff, #7f00ff); color: white; border-radius: 12px 12px 0 0; text-align: center; padding: 15px; }
        .filter-btn { transition: all 0.3s ease; background-color: transparent; border: 2px solid #ddd; color: #666; }
        .filter-btn:hover { transform: scale(1.05); border-color: #00b0ff; color: #00b0ff; }
        .filter-btn.active { background: linear-gradient(90deg, #00b0ff, #7f00ff); color: white; border: none; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); }
        .travel-card { cursor: pointer; transition: transform 0.2s; border-radius: 12px; border: 0; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .travel-card:active { transform: scale(0.98); }

        .btn-pulse { animation: pulse-red 2s infinite; }
        @keyframes pulse-red { 0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); } 100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); } }

        .bottom-nav { position: fixed; bottom: 0; left: 0; width: 100%; height: 65px; background: #fff; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); display: flex; justify-content: space-around; align-items: center; z-index: 1000; border-top: 1px solid #eee; }
        .nav-item-mobile { display: flex; flex-direction: column; align-items: center; justify-content: center; color: #adb5bd; text-decoration: none; font-size: 0.75rem; width: 100%; height: 100%; }
        .nav-item-mobile i { font-size: 1.4rem; margin-bottom: 2px; transition: transform 0.2s; }
        .nav-item-mobile.active { color: #00b0ff; font-weight: 600; }
        .nav-item-mobile.active i { transform: translateY(-2px); }
        
        .app-content { padding-bottom: 80px; }
    </style>
  </head>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    
    <div id="airportOverlay">
        <div class="airport-controls">
            <i class="bi bi-phone-landscape airport-icon" id="rotateScreenBtn" title="Rodar"></i>
            <i class="bi bi-x-circle-fill airport-icon" id="closeAirportMode" title="Fechar"></i>
        </div>
        <div class="content-wrapper d-flex flex-column align-items-center justify-content-center h-100 w-100">
            <div style="margin-bottom: 20px; opacity: 0.6;">
                <h3 class="text-white fw-light" style="letter-spacing: 4px;">SYNC<span class="fw-bold">RIDE</span></h3>
            </div>
            <h1 id="airportClientName">CLIENTE</h1>
        </div>
    </div>

    <div class="app-wrapper">
      <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
          <ul class="navbar-nav"><li class="nav-item"><a class="nav-link" data-lte-toggle="sidebar" href="#"><i class="bi bi-list"></i></a></li></ul>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img src="../../dist/assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow" alt="User" />
                <span class="d-none d-md-inline"><?php echo $_SESSION['name']; ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                  <img src="../../dist/assets/img/user2-160x160.jpg" class="rounded-circle shadow" alt="User" />
                  <p><?php echo $_SESSION['name']; ?> - Condutor</p>
                </li>
                <li class="user-footer">
                  <a href="driverstats.php" class="btn btn-default btn-flat">EstatÃ­sticas</a>
                  <a href="logout.php" class="btn btn-default btn-flat float-end">Sair</a>
                </li>
              </ul>
            </li>
          </ul>
        </div>
      </nav>

      <?php
        $viagensHoje = 0; $viagensSemana = 0;
        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id']; 
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
                $stmt->execute([$userId]);
                $viagensHoje = $stmt->fetchColumn();
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE YEARWEEK(serviceDate, 1) = YEARWEEK(CURDATE(), 1) AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
                $stmt->execute([$userId]);
                $viagensSemana = $stmt->fetchColumn();
            } catch (PDOException $e) {}
        }
      ?>

      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Painel de Condutor</h3></div>
            </div>
          </div>
        </div>
        
        <div class="app-content">
          <div class="container-fluid">
            <div class="row mb-3">
              <div class="col-6">
                  <div class="small-box text-bg-primary shadow-sm mb-0">
                      <div class="inner"><h3><?php echo $viagensHoje; ?></h3><p>Hoje</p></div>
                      <div class="small-box-icon"><i class="bi bi-calendar-day"></i></div>
                  </div>
              </div>
              <div class="col-6">
                  <div class="small-box text-bg-success shadow-sm mb-0">
                      <div class="inner"><h3><?php echo $viagensSemana; ?></h3><p>Semana</p></div>
                      <div class="small-box-icon"><i class="bi bi-calendar-week"></i></div>
                  </div>
              </div>
            </div>

            <div class="card mb-4 shadow-lg border-0 rounded-4">
                <div class="card-header-custom">
                    <h3 class="card-title fw-bold mb-0">ðŸš– Minhas Viagens</h3>
                </div>

                <div class="d-flex justify-content-center gap-3 p-3 bg-light border-bottom">
                    <button class="btn btn-sm rounded-pill px-4 filter-btn" data-filter="yesterday">Ontem</button>
                    <button class="btn btn-sm rounded-pill px-4 filter-btn active" data-filter="today">Hoje</button>
                    <button class="btn btn-sm rounded-pill px-4 filter-btn" data-filter="tomorrow">AmanhÃ£</button>
                </div>

                <div class="card-body p-3 bg-light">
                    <div class="list-group">
                        <p class="text-center text-muted py-5">A carregar viagens...</p>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="detailsModal" tabindex="-1">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content shadow-lg border-0" style="border-radius: 20px;">
                  
                  <div class="modal-header bg-white border-bottom-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">Detalhes do ServiÃ§o</h5>
                        <small class="text-muted">ID: #<span id="modalIdDisplay"></span></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  
                  <div class="modal-body p-4 pt-2">
                    
                    <div class="bg-light p-3 rounded-3 border mb-4 mt-2">
                        <div class="d-flex align-items-center mb-2">
                            <div class="bg-white p-2 rounded-circle me-3 shadow-sm">
                                <i class="bi bi-person-fill fs-3 text-primary"></i>
                            </div>
                            <div>
                                <div class="text-muted small text-uppercase fw-bold">Cliente</div>
                                <div id="modalClient" class="fw-bold text-dark fs-5"></div>
                                <div id="modalClientNumber" class="text-primary fw-bold"></div>
                            </div>
                        </div>
                        <a href="#" id="whatsappBtn" class="btn btn-success btn-sm w-100 mt-2 rounded-pill">
                            <i class="bi bi-whatsapp me-1"></i> Contactar Cliente
                        </a>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <small class="text-muted fw-bold text-uppercase">Data</small>
                            <div id="modalDate" class="fw-bold text-dark fs-5"></div>
                        </div>
                        <div class="col-6 text-end">
                            <small class="text-muted fw-bold text-uppercase">Hora</small>
                            <div id="modalTime" class="fw-bolder text-primary fs-4"></div>
                        </div>
                    </div>

                    <div class="route-timeline">
                        <div class="rt-item">
                            <div class="rt-dot dot-green"></div>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Recolha</small>
                            <div id="modalPickup" class="fw-bold text-dark lh-sm"></div>
                        </div>
                        <div class="rt-item mt-3">
                            <div class="rt-dot dot-red"></div>
                            <small class="text-muted fw-bold text-uppercase" style="font-size: 0.65rem;">Entrega</small>
                            <div id="modalDropoff" class="fw-bold text-dark lh-sm"></div>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3 gap-2">
                        <div class="bg-white border rounded p-2 w-50 text-center shadow-sm">
                            <span class="d-block text-muted small fw-bold text-uppercase">Adultos</span>
                            <span class="fs-5 fw-bold text-dark" id="modalADT">0</span>
                        </div>
                        <div class="bg-white border rounded p-2 w-50 text-center shadow-sm">
                            <span class="d-block text-muted small fw-bold text-uppercase">CrianÃ§as</span>
                            <span class="fs-5 fw-bold text-dark" id="modalCHD">0</span>
                        </div>
                    </div>
                    
                    <div id="flightSection" class="alert alert-info border-0 d-flex align-items-center justify-content-between py-2 mb-3" style="display: none;">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-airplane-engines-fill me-2 fs-5"></i>
                            <div>
                                <small class="d-block lh-1 opacity-75">Voo</small>
                                <strong id="modalFlight" class="fs-6"></strong>
                            </div>
                        </div>
                        <a href="#" id="trackFlightLink" target="_blank" class="btn btn-sm btn-light text-primary fw-bold rounded-pill px-3">
                            Rastrear
                        </a>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button id="btnStartTracking" class="btn btn-primary btn-modal-action w-100 text-white shadow-sm">
                            <i class="bi bi-geo-alt-fill me-2"></i> INICIAR RECOLHA
                        </button>
                        <button id="btnStopTracking" class="btn btn-danger btn-modal-action w-100 text-white shadow-sm btn-pulse d-none">
                            <i class="bi bi-stop-circle-fill me-2"></i> TERMINAR VIAGEM
                        </button>
                    </div>

                    <div class="d-grid gap-2">
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <a href="#" id="wazePickupButton" class="btn btn-light w-100 border py-2 fw-bold" target="_blank"><i class="bi bi-waze me-1"></i> Waze Rec</a>
                            </div>
                            <div class="col-6">
                                <a href="#" id="wazeDropoffButton" class="btn btn-light w-100 border py-2 fw-bold" target="_blank"><i class="bi bi-waze me-1"></i> Waze Ent</a>
                            </div>
                        </div>

                        <button class="btn btn-dark w-100 py-3 fw-bold shadow mb-3" id="btnAirportMode" style="border-radius: 12px;">
                            <i class="bi bi-signpost-2-fill me-2"></i> MODO AEROPORTO
                        </button>

                        <div class="row g-2">
                            <div class="col-6">
                                <button class="btn btn-outline-secondary btn-modal-action w-100" id="uploadVoucher">
                                    <i class="bi bi-ticket-perforated"></i> Voucher
                                </button>
                            </div>
                            <div class="col-6">
                                <button class="btn btn-outline-danger btn-modal-action w-100" id="uploadNoShow">
                                    <i class="bi bi-camera"></i> No-Show
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="cameraContainer" class="mt-3 bg-white p-3 rounded border shadow-sm" style="display: none;">
                      <p class="text-center mb-2 fw-bold" id="cameraInstruction">Tirar Foto</p>
                      <div style="position: relative; padding-top: 100%; overflow: hidden; border-radius: 8px; background: #000;">
                          <video id="cameraStream" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;" autoplay playsinline></video>
                          <canvas id="photoCanvas" style="display: none; width: 100%; height: 100%;"></canvas>
                      </div>
                      <div class="d-flex gap-2 mt-3">
                          <button class="btn btn-light flex-grow-1" onclick="stopCameraStream(); document.getElementById('cameraContainer').style.display='none';">Cancelar</button>
                          <button class="btn btn-primary flex-grow-1" id="capturePhoto">ðŸ“¸ Capturar</button>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <script>
              let trackingInterval = null;
              let isTracking = false;
              let currentRideId = null;
              const TEST_PHONE = "934478588";

              document.addEventListener("DOMContentLoaded", function () {
                
                function formatDate(date) {
                    const y = date.getFullYear();
                    const m = String(date.getMonth() + 1).padStart(2, "0");
                    const d = String(date.getDate()).padStart(2, "0");
                    return `${y}-${m}-${d}`;
                }

                function filterTrips(filter) {
                    const t = new Date();
                    const y = new Date(t); y.setDate(t.getDate() - 1);
                    const tm = new Date(t); tm.setDate(t.getDate() + 1);
                    const map = { "yesterday": formatDate(y), "today": formatDate(t), "tomorrow": formatDate(tm) };
                    const res = viagens.filter(v => v.serviceDate === map[filter]);
                    renderList(res);
                }

                function renderList(data) {
                  const el = document.querySelector(".list-group");
                  el.innerHTML = ""; 

                  if (data.length === 0) {
                    el.innerHTML = "<div class='text-center py-5 text-muted opacity-50'><i class='bi bi-calendar-x fs-1'></i><p>Sem viagens.</p></div>";
                    return;
                  }

                  data.forEach(v => {
                      const isPriv = (v.serviceType == 1);
                      const borderColor = isPriv ? "#0d6efd" : "#ffc107";
                      const badge = isPriv 
                        ? '<span class="badge bg-primary rounded-pill">Privado</span>' 
                        : '<span class="badge bg-warning text-dark rounded-pill">Partilhado</span>';

                      const html = `
                        <div class="card mb-3 border-0 shadow-sm open-modal"
                             style="border-left: 5px solid ${borderColor}; cursor: pointer; border-radius: 12px;"
                             data-id="${v.ServiceID}"
                             data-start="${v.serviceStartPoint}"
                             data-end="${v.serviceTargetPoint}"
                             data-time="${v.serviceStartTime.substr(0, 5)}"
                             data-date="${v.serviceDate}"
                             data-paxadt="${v.paxADT||0}"
                             data-paxchd="${v.paxCHD||0}"
                             data-flight="${v.FlightNumber || ''}"
                             data-client="${v.NomeCliente || ''}"
                             data-clientnumber="${v.ClientNumber || ''}">
                          <div class="card-body p-3">
                             <div class="d-flex justify-content-between align-items-center mb-2">
                                <h4 class="fw-bold m-0 text-dark">${v.serviceStartTime.substr(0, 5)}</h4>
                                ${badge}
                             </div>
                             <div class="text-truncate mb-1 text-secondary"><i class="bi bi-geo-alt-fill text-success me-2"></i> ${v.serviceStartPoint}</div>
                             <div class="text-truncate text-dark fw-medium"><i class="bi bi-flag-fill text-danger me-2"></i> ${v.serviceTargetPoint}</div>
                          </div>
                        </div>
                      `;
                      el.innerHTML += html;
                  });
                }

                filterTrips("today");

                document.querySelectorAll(".filter-btn").forEach(b => {
                  b.addEventListener("click", function() {
                    document.querySelectorAll(".filter-btn").forEach(x => { x.classList.remove("active"); });
                    this.classList.add("active");
                    filterTrips(this.dataset.filter);
                  });
                });

                const modal = document.getElementById('detailsModal');
                document.querySelector(".list-group").addEventListener("click", e => {
                    const card = e.target.closest(".open-modal");
                    if(!card) return;

                    stopCameraStream();
                    document.getElementById('cameraContainer').style.display = 'none';

                    // DADOS DA VIAGEM
                    const d = card.dataset;
                    const rideID = d.id; // Guardar em variável local segura
                    
                    modal.querySelector("#modalIdDisplay").textContent = d.id;
                    modal.querySelector("#modalDate").textContent = d.date.split('-').reverse().join('/');
                    modal.querySelector("#modalTime").textContent = d.time;
                    modal.querySelector("#modalPickup").textContent = d.start;
                    modal.querySelector("#modalDropoff").textContent = d.end;
                    modal.querySelector("#modalADT").textContent = d.paxadt;
                    modal.querySelector("#modalCHD").textContent = d.paxchd;
                    modal.querySelector("#modalClient").textContent = d.client || 'Cliente Sem Nome';
                    modal.querySelector("#modalClientNumber").textContent = d.clientnumber || 'Sem contacto';
                    
                    const waBtn = modal.querySelector("#whatsappBtn");
                    if(d.clientnumber) {
                        waBtn.href = "https://wa.me/" + d.clientnumber.replace(/[^0-9]/g, '');
                        waBtn.classList.remove('disabled');
                    } else {
                        waBtn.classList.add('disabled');
                    }

                    const fSection = modal.querySelector("#flightSection");
                    if(d.flight && d.flight !== 'N/A' && d.flight.trim() !== '') {
                        fSection.style.display = "flex";
                        modal.querySelector("#modalFlight").textContent = d.flight;
                        modal.querySelector("#trackFlightLink").href = "https://www.flightradar24.com/data/flights/" + d.flight.replace(/\s/g, '');
                    } else {
                        fSection.style.display = "none";
                    }

                    const wBase = "https://waze.com/ul?q=";
                    modal.querySelector("#wazePickupButton").href = wBase + encodeURIComponent(d.start) + "&navigate=yes";
                    modal.querySelector("#wazeDropoffButton").href = wBase + encodeURIComponent(d.end) + "&navigate=yes";

                    modal.querySelector("#uploadNoShow").dataset.tripId = d.id;
                    modal.querySelector("#uploadVoucher").dataset.tripId = d.id;
                    
                    const btnStart = modal.querySelector("#btnStartTracking");
                    const btnStop = modal.querySelector("#btnStopTracking");
                    
                    if(isTracking && currentRideId == rideID) {
                        btnStart.classList.add("d-none");
                        btnStop.classList.remove("d-none");
                    } else {
                        btnStart.classList.remove("d-none");
                        btnStop.classList.add("d-none");
                    }

                    // INICIAR
                    btnStart.onclick = () => {
                        if(confirm("Iniciar recolha e enviar localização ao cliente?")) {
                            const trackingUrl = `https://syncride.webminds.pt/track.php?id=${rideID}`;
                            const phone = d.clientnumber ? d.clientnumber.replace(/[^0-9]/g, '') : TEST_PHONE;
                            const msg = `Olá! Sou o seu condutor da SyncRide. Acompanhe a viagem aqui: ${trackingUrl}`;
                            window.open(`https://wa.me/${phone}?text=${encodeURIComponent(msg)}`, '_blank');
                            
                            startLiveTracking(rideID);
                            
                            btnStart.classList.add("d-none");
                            btnStop.classList.remove("d-none");
                        }
                    };
                    
                    // PARAR (AQUI ESTAVA O ERRO)
                    btnStop.onclick = () => {
                        if(confirm("Terminar viagem?")) {
                            stopLiveTracking();
                            
                            // Enviar pedido de limpeza explicitamente com o ID correto
                            console.log("A limpar viagem:", rideID);
                            
                            fetch('api_stop_tracking.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({ ride_id: rideID })
                            })
                            .then(r => r.json())
                            .then(res => {
                                console.log("Resposta Limpeza:", res);
                                if(res.success) {
                                    alert("Viagem terminada e mapa limpo.");
                                } else {
                                    alert("Erro ao limpar mapa: " + res.error);
                                }
                            })
                            .catch(e => alert("Erro de rede ao limpar mapa."));

                            btnStart.classList.remove("d-none");
                            btnStop.classList.add("d-none");
                        }
                    };

                    modal.querySelector("#btnAirportMode").onclick = () => {
                        const overlay = document.getElementById('airportOverlay');
                        document.getElementById('airportClientName').textContent = d.client || "CLIENTE";
                        overlay.style.display = "flex";
                        overlay.classList.remove("rotate-mode");
                        if(document.documentElement.requestFullscreen) document.documentElement.requestFullscreen().catch(()=>{});
                    };

                    new bootstrap.Modal(modal).show();
                });

                document.getElementById('closeAirportMode').onclick = () => {
                    document.getElementById('airportOverlay').style.display = "none";
                    if(document.exitFullscreen) document.exitFullscreen().catch(()=>{});
                };
                document.getElementById('rotateScreenBtn').onclick = () => {
                    document.getElementById('airportOverlay').classList.toggle("rotate-mode");
                };
              });

              function startLiveTracking(rideId) {
                  currentRideId = rideId;
                  isTracking = true;
                  if ("geolocation" in navigator) {
                      navigator.geolocation.getCurrentPosition(pos => sendPosition(pos));
                      trackingInterval = setInterval(() => {
                          navigator.geolocation.getCurrentPosition(pos => sendPosition(pos));
                      }, 5000);
                  } else { alert("GPS Indisponível."); }
              }
              
              function stopLiveTracking() {
                  isTracking = false;
                  currentRideId = null;
                  if(trackingInterval) clearInterval(trackingInterval);
              }
              
              function sendPosition(position) {
                  if(!isTracking) return;
                  const payload = {
                      ride_id: currentRideId,
                      driver_id: <?php echo $_SESSION['user_id']; ?>,
                      lat: position.coords.latitude,
                      lng: position.coords.longitude,
                      speed: position.coords.speed,
                      heading: position.coords.heading
                  };
                  fetch('api_update_location.php', {
                      method: 'POST', body: JSON.stringify(payload), headers: {'Content-Type': 'application/json'}
                  }).catch(e => console.log(e));
              }
            </script>

            <script>
                const cameraContainer = document.getElementById('cameraContainer');
                const video = document.getElementById('cameraStream');
                const canvas = document.getElementById('photoCanvas');
                const captureButton = document.getElementById('capturePhoto');
                const noShowButton = document.getElementById('uploadNoShow');
                const voucherButton = document.getElementById('uploadVoucher'); 
                const instructionText = document.getElementById('cameraInstruction'); 
                let stream = null; let currentMode = 'noshow'; 

                function stopCameraStream() { if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; } }

                async function startCamera(mode) {
                    currentMode = mode;
                    instructionText.textContent = (mode === 'voucher') ? "Fotografar Voucher" : "Prova de No-Show";
                    if (stream) { stopCameraStream(); cameraContainer.style.display = 'none'; return; }
                    canvas.style.display = 'none'; video.style.display = 'block'; cameraContainer.style.display = 'block'; 
                    try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }); video.srcObject = stream; } 
                    catch (err) { try { stream = await navigator.mediaDevices.getUserMedia({ video: true }); video.srcObject = stream; } catch (e) {} }
                }

                noShowButton.addEventListener('click', () => startCamera('noshow'));
                voucherButton.addEventListener('click', () => startCamera('voucher'));

                captureButton.addEventListener('click', () => {
                    if (!stream) return; 
                    captureButton.disabled = true; captureButton.innerHTML = 'A enviar...';
                    const ctx = canvas.getContext('2d');
                    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0);
                    const imgData = canvas.toDataURL('image/jpeg');
                    stopCameraStream(); video.style.display='none'; canvas.style.display='block';

                    const tripId = noShowButton.dataset.tripId; 
                    const endpoint = currentMode === 'voucher' ? 'upload_voucher.php' : 'upload_no_show.php';

                    fetch(endpoint, {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ trip_id: tripId, image_data: imgData })
                    })
                    .then(r => r.json())
                    .then(d => {
                        alert(d.message); 
                        bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide();
                        cameraContainer.style.display='none';
                    })
                    .catch(e => alert('Erro envio.'))
                    .finally(() => { captureButton.disabled = false; captureButton.textContent = '📸 Capturar'; });
                });
                
                document.getElementById('detailsModal').addEventListener('hidden.bs.modal', () => {
                    stopCameraStream(); cameraContainer.style.display = 'none';
                });
            </script>

          </div>
        </div>
      </main>
      <footer class="app-footer">
        <div class="float-end d-none d-sm-inline"></div>
        SyncRide All rights reserved.
      </footer>

      <nav class="bottom-nav d-flex d-md-none">
          <a href="driver.php" class="nav-item-mobile active">
              <i class="bi bi-car-front-fill"></i>
              <span>Viagens</span>
          </a>
          <a href="driver_agenda.php" class="nav-item-mobile">
              <i class="bi bi-calendar3"></i>
              <span>Agenda</span>
          </a>
          <a href="driverstats.php" class="nav-item-mobile">
              <i class="bi bi-bar-chart-fill"></i>
              <span>Stats</span>
          </a>
          <a href="logout.php" class="nav-item-mobile text-danger">
              <i class="bi bi-box-arrow-right"></i>
              <span>Sair</span>
          </a>
      </nav>

    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
    <script src="../../dist/js/adminlte.js"></script>
  </body>
</html>