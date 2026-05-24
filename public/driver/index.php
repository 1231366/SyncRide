<?php
session_start(); 
require __DIR__ . '/../../auth/dbconfig.php'; 

$viagens = [];
$serviceTypeFilter = isset($_GET['serviceType']) ? $_GET['serviceType'] : null; 

// --- 0. MODO API (AUTO-REFRESH) ---
if (isset($_GET['api']) && $_GET['api'] === 'refresh') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_id'])) { echo json_encode([]); exit(); }

    $userId = $_SESSION['user_id'];
    $filterType = isset($_GET['serviceType']) ? $_GET['serviceType'] : null;

    try {
        $query = "SELECT s.ID AS ServiceID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint, 
                         s.paxADT, s.paxCHD, s.FlightNumber, s.NomeCliente, s.ClientNumber, s.serviceType, 
                         s.total_price, s.has_key, s.partner_id, COALESCE(s.status_id, 0) as status_id,
                         u.name AS AgencyName, u.phone AS AgencyPhone
                  FROM Services_Rides sr 
                  INNER JOIN Services s ON sr.RideID = s.ID 
                  LEFT JOIN Users u ON s.partner_id = u.id
                  WHERE sr.UserID = ?";
                  
        if ($filterType !== null) { $query .= " AND s.serviceType = ?"; }
        $query .= " ORDER BY s.serviceDate ASC, s.serviceStartTime ASC";
        
        $stmt = $pdo->prepare($query);
        if ($filterType !== null) { $stmt->execute([$userId, $filterType]); } else { $stmt->execute([$userId]); }
        $viagensData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($viagensData); exit();
    } catch (PDOException $e) { echo json_encode([]); exit(); }
}

// --- 1. VERIFICAÇÃO DE SESSÃO ---
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 2) {
    $userId = $_SESSION['user_id']; 
    $userName = $_SESSION['name'];

    // --- 2. FOTO DE PERFIL ---
    $userPhotoPath = '../../dist/assets/img/user2-160x160.jpg'; 
    if (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) {
        $userPhotoPath = '../../../' . $_SESSION['profile_photo_path'];
    } else {
        try {
            $stmt = $pdo->prepare("SELECT profile_photo_path FROM Users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result && !empty($result['profile_photo_path'])) {
                $userPhotoPath = '../../../' . $result['profile_photo_path'];
                $_SESSION['profile_photo_path'] = $result['profile_photo_path'];
            }
        } catch (PDOException $e) {}
    }

    // --- 3. FETCH INICIAL ---
    try {
        $query = "SELECT s.ID AS ServiceID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint, 
                         s.paxADT, s.paxCHD, s.FlightNumber, s.NomeCliente, s.ClientNumber, s.serviceType, 
                         s.total_price, s.has_key, s.partner_id, COALESCE(s.status_id, 0) as status_id,
                         u.name AS AgencyName, u.phone AS AgencyPhone
                  FROM Services_Rides sr 
                  INNER JOIN Services s ON sr.RideID = s.ID 
                  LEFT JOIN Users u ON s.partner_id = u.id
                  WHERE sr.UserID = ?";
                  
        if ($serviceTypeFilter !== null) { $query .= " AND s.serviceType = ?"; }
        $query .= " ORDER BY s.serviceDate ASC, s.serviceStartTime ASC";
        
        $stmt = $pdo->prepare($query);
        if ($serviceTypeFilter !== null) { $stmt->execute([$userId, $serviceTypeFilter]); } else { $stmt->execute([$userId]); }
        $viagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { echo "Erro: " . $e->getMessage(); }
} else {
    header("refresh: 1; url=/SRMT/public/"); exit();
}

// --- 4. ESTATÍSTICAS ---
$viagensHoje = 0; $viagensSemana = 0;
if (isset($userId)) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
        $stmt->execute([$userId]); $viagensHoje = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE YEARWEEK(serviceDate, 1) = YEARWEEK(CURDATE(), 1) AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
        $stmt->execute([$userId]); $viagensSemana = $stmt->fetchColumn();
    } catch (PDOException $e) {}
}

echo "<script> var viagens = " . json_encode($viagens) . "; var currentDriverId = " . $_SESSION['user_id'] . "; </script>";
?>

<!doctype html>
<html lang="pt" data-bs-theme="light">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Condutor | SyncRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" />
    
    <style>
        /* --- DESIGN SYSTEM --- */
        :root {
            --font-primary: 'Inter', sans-serif;
            --font-display: 'Poppins', sans-serif;
            --bg-body: #f3f4f6;
            --bg-card: #ffffff;
            --text-main: #111827;
            --text-muted: #6b7280;
            --primary-accent: #4f46e5;
            --primary-hover: #4338ca;
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
            --primary-hover: #818cf8;
            --border-color: #334155;
        }

        body {
            font-family: var(--font-primary);
            background-color: var(--bg-body);
            color: var(--text-main);
            padding-bottom: calc(80px + var(--safe-bottom));
            padding-top: 0;
            margin: 0;
            min-height: 100vh;
        }

        /* --- HEADER --- */
        .app-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-color);
            padding: calc(15px + var(--safe-top)) 20px 15px 20px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; 
            z-index: 1080; 
        }
        .brand-logo { height: 30px; width: auto; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 2px solid var(--border-color); }

        /* --- STAT CARDS --- */
        .stat-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 15px;
            display: flex; align-items: center; gap: 15px;
            box-shadow: var(--shadow-sm);
        }
        .stat-icon {
            width: 45px; height: 45px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .bg-indigo-soft { background: rgba(79, 70, 229, 0.1); color: var(--primary-accent); }
        .bg-emerald-soft { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .stat-info h3 { font-family: var(--font-display); font-weight: 700; font-size: 1.5rem; margin: 0; line-height: 1; }
        .stat-info p { margin: 0; font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }

        /* --- FILTER PILLS --- */
        .filter-container {
            background-color: var(--bg-card);
            padding: 5px; border-radius: 50px;
            display: flex; justify-content: space-between;
            border: 1px solid var(--border-color);
            margin-bottom: 20px;
        }
        .filter-btn {
            flex: 1; text-align: center; padding: 8px 0;
            border-radius: 50px; border: none; background: transparent;
            color: var(--text-muted); font-size: 0.9rem; font-weight: 500;
            transition: all 0.2s;
        }
        .filter-btn.active {
            background-color: var(--primary-accent); color: white;
            box-shadow: 0 2px 5px rgba(79, 70, 229, 0.3);
        }

        /* --- RIDE CARD --- */
        .ride-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            margin-bottom: 15px;
            padding: 16px;
            position: relative;
            transition: transform 0.1s;
            box-shadow: var(--shadow-sm);
        }
        .ride-card:active { transform: scale(0.98); }
        
        .ride-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .ride-time { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
        .ride-badges-container { display: flex; gap: 5px; align-items: center; flex-wrap: wrap; }
        .ride-badge { font-size: 0.7rem; font-weight: 600; padding: 4px 10px; border-radius: 50px; text-transform: uppercase; display: inline-flex; align-items: center; gap: 4px;}
        .badge-private { background: rgba(79, 70, 229, 0.1); color: var(--primary-accent); border: 1px solid rgba(79, 70, 229, 0.2); }
        .badge-shared { background: rgba(245, 158, 11, 0.1); color: #d97706; border: 1px solid rgba(245, 158, 11, 0.2); }

        .card-timeline { position: relative; padding-left: 20px; border-left: 2px dashed var(--border-color); margin-left: 6px; }
        .ct-point { position: relative; margin-bottom: 15px; }
        .ct-point:last-child { margin-bottom: 0; }
        .ct-dot {
            width: 12px; height: 12px; border-radius: 50%;
            position: absolute; left: -27px; top: 4px;
            border: 2px solid var(--bg-card);
        }
        .dot-pickup { background-color: #10b981; }
        .dot-dropoff { background-color: #ef4444; }
        .ct-text { font-size: 0.95rem; color: var(--text-main); line-height: 1.3; }

        .price-tag {
            position: absolute; bottom: 16px; right: 16px;
            background-color: #10b981; color: white;
            font-weight: 700; font-size: 0.85rem;
            padding: 4px 10px; border-radius: 8px;
            display: flex; align-items: center; gap: 4px;
        }

        /* --- MODAL STYLES --- */
        .modal-content { background-color: var(--bg-card); border-radius: 24px; border: none; }
        .modal-header { border-bottom: 1px solid var(--border-color); padding: 1rem 1.2rem; }
        .modal-title { font-family: var(--font-display); font-weight: 700; color: var(--text-main); font-size: 1.1rem; }
        .modal-body { padding: 1rem; }
        
        .info-box { background-color: var(--bg-body); border-radius: 12px; padding: 10px; border: 1px solid var(--border-color); margin-bottom: 8px; }
        .info-label { font-size: 0.65rem; text-transform: uppercase; color: var(--text-muted); font-weight: 600; display: block; margin-bottom: 2px; }
        .info-value { font-size: 0.95rem; color: var(--text-main); font-weight: 600; line-height: 1.2; }

        .btn-dynamic-action {
            width: 100%; padding: 12px; font-size: 1rem; font-weight: 700; font-family: var(--font-display);
            border-radius: 12px; border: none; color: white; text-transform: uppercase; letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); transition: transform 0.2s; margin-bottom: 10px;
        }
        .btn-dynamic-action:active { transform: scale(0.97); }
        .status-btn-0 { background: var(--primary-accent); }
        .status-btn-1 { background: #f59e0b; color: #fff; }
        .status-btn-2 { background: #3b82f6; } 
        .status-btn-5 { background: #10b981; } 
        .status-btn-3 { background: #ef4444; } 
        .status-btn-4 { background: #6b7280; opacity: 0.7; }
        .btn-whatsapp { background-color: #25D366; color: white; border: none; border-radius: 8px; padding: 8px; width: 100%; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; margin-top: 8px; font-size: 0.9rem;}

        /* --- BOTTOM NAV --- */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%; 
            height: calc(70px + var(--safe-bottom));
            background-color: var(--bg-card); border-top: 1px solid var(--border-color);
            display: flex; justify-content: space-around; align-items: flex-start;
            z-index: 1080; 
            padding-bottom: var(--safe-bottom);
            padding-top: 10px;
        }
        .nav-item-mobile {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            color: var(--text-muted); text-decoration: none; font-size: 0.75rem; font-weight: 500;
            width: 100%; height: 50px; transition: color 0.2s;
        }
        .nav-item-mobile i { font-size: 1.5rem; margin-bottom: 4px; }
        .nav-item-mobile.active { color: var(--primary-accent); }

        /* --- AIRPORT OVERLAY (MÁXIMA VISIBILIDADE & AJUSTE MANUAL) --- */
        #airportOverlay { 
            position: fixed; top: 0; left: 0; 
            width: 100vw; height: 100vh; 
            background: #000; 
            z-index: 2000; 
            display: none; 
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            overflow: hidden;
            color: white;
        }

        #airportContentWrapper {
            width: 100%; height: 100%;
            display: flex; flex-direction: column; 
            align-items: center; justify-content: center;
            text-align: center;
            padding: 0;
            overflow: hidden;
        }

        #airportOverlay.landscape-mode #airportContentWrapper {
            position: absolute;
            top: 50%; left: 50%;
            width: 100vh; height: 100vw;
            transform: translate(-50%, -50%) rotate(90deg);
        }
        
        #airportClientName { 
            font-family: var(--font-display);
            font-weight: 900; 
            line-height: 0.9; 
            text-transform: uppercase; 
            width: 100%; 
            margin: 0;
            padding: 0 4vw;
            font-size: 15vw; /* Tamanho inicial base */
            word-wrap: normal;
            display: block;
        }

        .name-part { display: inline-block; white-space: nowrap; }

        #airportFlight {
            font-family: var(--font-primary);
            font-weight: 600;
            color: #FFD700;
            margin-top: 2vh;
            font-size: 6vw;
            letter-spacing: 2px;
        }

        /* Controlos da Placa */
        .airport-controls {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 25px;
            z-index: 2001;
            opacity: 0.2;
            transition: opacity 0.3s;
        }
        .airport-controls:hover { opacity: 1; }

        .airport-zoom-controls {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 20px;
            z-index: 2002;
            opacity: 0.2;
            transition: opacity 0.3s;
        }
        .airport-zoom-controls:hover { opacity: 1; }
        .zoom-btn {
            width: 60px; height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            backdrop-filter: blur(5px);
            cursor: pointer;
        }
        
        /* --- CAMERA OVERLAY --- */
        #cameraOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: #000; z-index: 99999; display: none; flex-direction: column; }
        #cameraViewArea { flex: 1; position: relative; overflow: hidden; background: #000; }
        #cameraStream, #photoCanvas { width: 100%; height: 100%; object-fit: cover; position: absolute; top: 0; left: 0; }
        .camera-ui-controls { position: absolute; bottom: 0; left: 0; width: 100%; padding: 40px 20px calc(40px + var(--safe-bottom)) 20px; background: linear-gradient(to top, #000, transparent); display: flex; justify-content: center; gap: 20px; align-items: center; }
        .camera-btn { width: 70px; height: 70px; border-radius: 50%; border: 4px solid white; background: transparent; display: flex; align-items: center; justify-content: center; padding: 0; }
        .camera-btn-inner { width: 56px; height: 56px; background: white; border-radius: 50%; transition: transform 0.1s; }
        .btn-circle-action { width: 50px; height: 50px; border-radius: 50%; border: none; background: rgba(255,255,255,0.2); color: white; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px); }

    </style>
  </head>
  <body>
    
    <div id="airportOverlay">
        <div class="airport-controls">
            <i class="bi bi-arrow-repeat text-white fs-1" id="rotateScreenBtn" title="Rodar"></i>
            <i class="bi bi-x-lg text-white fs-1" id="closeAirportMode" title="Fechar"></i>
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
        <div style="position: absolute; top: max(20px, env(safe-area-inset-top)); left: 0; width: 100%; text-align: center; color: white; z-index: 10; font-weight: 600; text-shadow: 0 2px 4px rgba(0,0,0,0.8);" id="cameraInstruction">Fotografar</div>
        <div id="cameraViewArea">
            <div id="cameraLoading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); color: white; display: none;">
                <div class="spinner-border mb-2"></div><div>A iniciar...</div>
            </div>
            <video id="cameraStream" autoplay playsinline style="transform-origin: center center;"></video>
            <canvas id="photoCanvas" style="display: none;"></canvas>
            <div id="cameraZoomStrip" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); display: flex; flex-direction: column; gap: 12px; z-index: 10; opacity: 0.7; transition: opacity 0.3s;">
                <button class="btn-circle-action" id="btnCameraZoomIn" title="Zoom +"><i class="bi bi-zoom-in"></i></button>
                <button class="btn-circle-action" id="btnCameraZoomOut" title="Zoom -"><i class="bi bi-zoom-out"></i></button>
            </div>
        </div>
        <div class="camera-ui-controls">
            <div id="stepCaptureControls" class="d-flex align-items-center gap-4">
                <button class="btn-circle-action" onclick="closeCameraOverlay()"><i class="bi bi-x-lg"></i></button>
                <button class="camera-btn" id="btnCapture"><div class="camera-btn-inner"></div></button>
                <button class="btn-circle-action" id="btnRotateCamera"><i class="bi bi-arrow-repeat"></i></button>
            </div>
            <div id="stepConfirmControls" class="d-none d-flex gap-3 w-100 justify-content-center">
                <button class="btn btn-light rounded-pill px-4 py-2 fw-bold" id="btnRetake">Repetir</button>
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold" id="btnConfirmSend">Enviar</button>
            </div>
        </div>
    </div>

    <header class="app-header">
        <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide" class="brand-logo" id="header-logo">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-link text-muted p-0" id="theme-toggle"><i class="bi bi-moon-stars-fill fs-5" id="theme-icon"></i></button>
            <img src="<?php echo $userPhotoPath; ?>" class="user-avatar shadow-sm" alt="User" data-bs-toggle="modal" data-bs-target="#photoModal">
        </div>
    </header>

    <div class="container-fluid px-3 pt-3">
        <div class="mb-4">
            <h4 class="fw-bold mb-3 text-main">Olá, <?php echo explode(' ', trim($userName))[0]; ?>! 👋</h4>
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-indigo-soft"><i class="bi bi-calendar-check"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $viagensHoje; ?></h3>
                            <p>Hoje</p>
                        </div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <div class="stat-icon bg-emerald-soft"><i class="bi bi-calendar-week"></i></div>
                        <div class="stat-info">
                            <h3><?php echo $viagensSemana; ?></h3>
                            <p>Semana</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-container">
            <button class="filter-btn" data-filter="yesterday">Ontem</button>
            <button class="filter-btn active" data-filter="today">Hoje</button>
            <button class="filter-btn" data-filter="tomorrow">Amanhã</button>
        </div>

        <div id="rideList">
            <div class="text-center py-5 text-muted">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 small">A carregar viagens...</div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title">Detalhes</h5>
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
                                    <span class="info-label">Cliente</span>
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
                        <div class="col-6"><button class="btn btn-sm btn-dark w-100 py-2 rounded-3 fw-bold" id="btnAirportMode"><i class="bi bi-signpost-2-fill me-1"></i> Placa</button></div>
                        <div class="col-6"><a href="#" id="trackFlightLink" target="_blank" class="btn btn-sm btn-info w-100 py-2 rounded-3 fw-bold text-white" style="display:none;"><i class="bi bi-airplane-fill me-1"></i> <span id="modalFlight"></span></a></div>
                    </div>

                    <div class="card p-2 border border-color shadow-none mb-3">
                        <div class="card-timeline modal-timeline" style="margin-left: 0; padding-left: 20px;">
                            <div class="ct-point"><div class="ct-dot dot-pickup"></div><span class="ct-label info-label">Recolha</span><div id="modalPickup" class="ct-text fw-bold lh-sm"></div></div>
                            <div class="ct-point mb-0"><div class="ct-dot dot-dropoff"></div><span class="ct-label info-label">Entrega</span><div id="modalDropoff" class="ct-text fw-bold lh-sm"></div></div>
                        </div>
                    </div>

                    <button id="btnDynamicAction" class="btn-dynamic-action status-btn-0">INICIAR RECOLHA</button>

                    <div class="row g-2">
                        <div class="col-6"><button class="btn btn-sm btn-outline-secondary w-100 py-2 rounded-pill fw-bold" id="uploadVoucher"><i class="bi bi-ticket-perforated"></i> Voucher</button></div>
                        <div class="col-6"><button class="btn btn-sm btn-outline-danger w-100 py-2 rounded-pill fw-bold" id="uploadNoShow"><i class="bi bi-camera"></i> No-Show</button></div>
                    </div>

                    <a href="#" id="whatsappAlojamento" target="_blank" class="btn-whatsapp mt-2" style="display:none;">
                        <i class="bi bi-whatsapp"></i> A sair do aeroporto
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0"><h5 class="modal-title">Foto de Perfil</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body text-center">
                    <form action="../../../save_profile_photo.php" method="POST" enctype="multipart/form-data">
                        <img id="currentProfilePhoto" src="<?php echo $userPhotoPath; ?>" class="rounded-circle shadow mb-4" style="width: 120px; height: 120px; object-fit: cover;">
                        <input type="file" name="profile_photo" id="profilePhotoInput" class="form-control mb-3" accept="image/*" required>
                        <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-bold">Guardar Alteração</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="/SRMT/public/driver/" class="nav-item-mobile active"><i class="bi bi-car-front-fill"></i><span>Viagens</span></a>
        <a href="agenda.php" class="nav-item-mobile"><i class="bi bi-calendar3"></i><span>Agenda</span></a>
        <a href="stats.php" class="nav-item-mobile"><i class="bi bi-bar-chart-fill"></i><span>Stats</span></a>
        <a href="/SRMT/public/auth/logout.php" class="nav-item-mobile text-danger"><i class="bi bi-box-arrow-right"></i><span>Sair</span></a>
    </nav>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // --- 1. DARK MODE ---
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;
        const headerLogo = document.getElementById('header-logo');
        const logoDark = "/SRMT/public/assets/images/icons/Syncride.png"; 
        const logoLight = "/SRMT/public/assets/images/icons/Syncridewhite.png";
        const savedTheme = localStorage.getItem('theme') || 'light';
        htmlElement.setAttribute('data-bs-theme', savedTheme);
        updateThemeIcon(savedTheme); updateLogo(savedTheme);
        themeToggle.addEventListener('click', () => {
            const newTheme = htmlElement.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme); updateLogo(newTheme);
        });
        function updateThemeIcon(theme) { themeIcon.className = theme === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5'; }
        function updateLogo(theme) { if(headerLogo) headerLogo.src = theme === 'dark' ? logoLight : logoDark; }

        // --- 2. GLOBAL VARS ---
        let backgroundWatcherId = null; let trackingInterval = null; let currentRideId = null; let currentRideData = null;
        let localTripStatus = {}; let currentFilter = "today"; let stream = null; let currentMode = 'noshow';
        let currentFacingMode = 'environment'; let locationWatcher = null; let currentLat = null; let currentLng = null;
        let cameraZoomLevel = 1; let pinchInitialDistance = 0;
        let wakeLock = null;
        if (typeof viagens !== 'undefined') { viagens.forEach(v => { localTripStatus[String(v.ServiceID)] = parseInt(v.status_id) || 0; }); }

        // --- 3. AUTO REFRESH ---
        function fetchLatestRides() {
            fetch('/SRMT/public/driver/?api=refresh').then(r => r.json()).then(data => {
                if (Array.isArray(data)) {
                    viagens = data;
                    viagens.forEach(v => { if (localTripStatus[String(v.ServiceID)] === undefined) localTripStatus[String(v.ServiceID)] = parseInt(v.status_id) || 0; });
                    filterTrips(currentFilter);
                }
            }).catch(err => console.log(err));
        }
        setInterval(fetchLatestRides, 15000);

        // --- 4. GPS & LOGIC ---
        function sendPosition(position) {
            // Helper: termina o background task no iOS Capacitor (sempre, seja sucesso ou falha)
            const finishBg = () => {
                if (window.Capacitor?.Plugins?.BackgroundGeolocation) Capacitor.Plugins.BackgroundGeolocation.finish();
            };

            if(!currentRideId) { finishBg(); return; }
            const lat = position.latitude || position.coords?.latitude;
            const lng = position.longitude || position.coords?.longitude;
            if (lat === undefined || lng === undefined) { finishBg(); return; }

            const payload = {
                ride_id: currentRideId,
                driver_id: <?php echo $_SESSION['user_id'] ?? 0; ?>,
                lat: lat,
                lng: lng,
                speed: position.speed || position.coords?.speed || 0,
                heading: position.bearing || position.coords?.heading || 0
            };
            // CRÍTICO Capacitor iOS: BackgroundGeolocation.finish() tem de ser chamado
            // SÓ depois do fetch resolver, senão o OS suspende o JS context com o request
            // em vôo e o ping perde-se silenciosamente.
            fetch('/SRMT/public/api/location-update.php', {
                method: 'POST',
                body: JSON.stringify(payload),
                headers: {'Content-Type': 'application/json'}
            })
            .catch(e => console.log('sendPosition error:', e))
            .finally(finishBg);
        }
        function startLiveTracking(rideId) {
            currentRideId = rideId;
            sessionStorage.setItem('activeRideId', rideId);
            if (window.Capacitor?.Plugins?.BackgroundGeolocation) {
                const BGeo = Capacitor.Plugins.BackgroundGeolocation;
                if (backgroundWatcherId) return;
                BGeo.addWatcher({
                    backgroundTitle: "SyncRide em Serviço",
                    backgroundMessage: "A sua localização está a ser partilhada",
                    requestAllowAlwaysLocation: true,
                    distanceFilter: 10, staleLocationThreshold: 30, radius: 20
                }, (location, error) => { if (location) sendPosition(location); }).then(id => { backgroundWatcherId = id; });
            } else if ("geolocation" in navigator) {
                if(trackingInterval) clearInterval(trackingInterval); 
                navigator.geolocation.getCurrentPosition(pos => sendPosition(pos));
                trackingInterval = setInterval(() => { navigator.geolocation.getCurrentPosition(pos => sendPosition(pos)); }, 5000);
            }
        }
        function stopLiveTracking() {
            if(!currentRideId) return;
            sessionStorage.removeItem('activeRideId');
            fetch('/SRMT/public/api/tracking-stop.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ ride_id: currentRideId, driver_id: <?php echo $_SESSION['user_id'] ?? 0; ?> })
            });
            currentRideId = null;
            if (window.Capacitor?.Plugins?.BackgroundGeolocation && backgroundWatcherId) { Capacitor.Plugins.BackgroundGeolocation.removeWatcher({ id: backgroundWatcherId }); backgroundWatcherId = null; }
            if(trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
        }
        document.addEventListener('DOMContentLoaded', async () => {
            if (window.Capacitor?.isNativePlatform()) {
                const { Geolocation, Camera, BackgroundGeolocation } = Capacitor.Plugins;
                await Geolocation.requestPermissions(); await Camera.requestPermissions();
                if (BackgroundGeolocation.requestPermissions) await BackgroundGeolocation.requestPermissions();
            }
            // Restaurar tracking se o utilizador saiu e voltou à app
            const savedRideId = sessionStorage.getItem('activeRideId');
            if (savedRideId && !currentRideId) {
                fetch('/SRMT/public/driver/?api=refresh').then(r => r.json()).then(data => {
                    if (!Array.isArray(data)) return;
                    const ride = data.find(v => String(v.ServiceID) === savedRideId);
                    if (ride && [1, 2, 5, 3].includes(parseInt(ride.status_id))) {
                        startLiveTracking(savedRideId);
                        console.log('Tracking restaurado para viagem', savedRideId);
                    } else {
                        sessionStorage.removeItem('activeRideId');
                    }
                }).catch(() => {});
            }
        });

        // Quando o utilizador volta à tab/app depois de estar no Waze ou noutro lado
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                const savedRideId = sessionStorage.getItem('activeRideId');
                if (savedRideId && !currentRideId) {
                    startLiveTracking(savedRideId);
                    console.log('Tracking reactivado ao voltar à app');
                }
            }
        });

        // --- 5. UI HELPERS ---
        // Abre URLs externas (waze://, wa.me, etc.) SEM partir a WebView do Capacitor.
        // 1º tenta o plugin App (nativo, abre na app correspondente)
        // 2º fallback: window.open(_system) que delega ao browser do sistema
        async function openExternal(url) {
            try {
                if (window.Capacitor?.Plugins?.App?.openUrl) {
                    await Capacitor.Plugins.App.openUrl({ url });
                    return;
                }
            } catch(e) { console.log('openExternal App.openUrl failed:', e); }
            window.open(url, '_system');
        }

        function openWaze(address) {
            openExternal("waze://?q=" + encodeURIComponent(address) + "&navigate=yes");
        }

        // Em Capacitor, links com target="_blank" abririam o in-app browser ou ficariam
        // mortos. Interceta-os e roteia via openExternal() — abre na app correspondente
        // (WhatsApp, Flightradar, etc.) ou no browser do sistema.
        document.addEventListener('click', function(e) {
            const a = e.target.closest('a[target="_blank"]');
            if (!a) return;
            const href = a.getAttribute('href');
            if (!href || href === '#') return;
            // Só queremos interceptar em Capacitor nativo; em web puro, target="_blank" funciona bem
            if (window.Capacitor?.isNativePlatform?.()) {
                e.preventDefault();
                openExternal(href);
            }
        });
        function updateButtonUI(status) {
            const btn = document.getElementById('btnDynamicAction'); if(!btn) return;
            btn.className = 'btn-dynamic-action'; 
            switch(parseInt(status)) {
                case 0: btn.classList.add('status-btn-0'); btn.innerHTML = '<i class="bi bi-car-front-fill me-2"></i> INICIAR RECOLHA'; btn.disabled = false; break;
                case 1: btn.classList.add('status-btn-1'); btn.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i> CHEGUEI'; btn.disabled = false; break;
                case 2: btn.classList.add('status-btn-2'); btn.innerHTML = '<i class="bi bi-person-check-fill me-2"></i> ESTOU COM O CLIENTE'; btn.disabled = false; break;
                case 5: btn.classList.add('status-btn-5'); btn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i> INICIAR VIAGEM'; btn.disabled = false; break;
                case 3: btn.classList.add('status-btn-3'); btn.innerHTML = '<i class="bi bi-stop-circle-fill me-2"></i> TERMINAR'; btn.disabled = false; break;
                default: btn.classList.add('status-btn-4'); btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> CONCLUÍDA'; btn.disabled = true;
            }
        }
        function updateStatusBackend(rideId, nextStatus) {
            let formData = new FormData();
            formData.append('ride_id', rideId);
            formData.append('status', nextStatus);
            fetch('/SRMT/public/api/status-update.php', { method: 'POST', body: formData })
            .then(r => r.json()).then(d => {
                if (d.success) {
                    localTripStatus[rideId] = nextStatus;
                    updateButtonUI(nextStatus);
                    if (parseInt(nextStatus) === 4) {
                        fetch('/SRMT/public/api/final-trip-report.php?ride_id=' + rideId).then(res => res.text()).then(text => { console.log("Email Status:", text); });
                        setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide(); fetchLatestRides(); }, 1000);
                    }
                }
            });
        }
        if(document.getElementById('btnDynamicAction')) {
            document.getElementById('btnDynamicAction').addEventListener('click', function() {
                if(!currentRideData) return;
                const rideId = currentRideData.id; const currentStatus = parseInt(localTripStatus[rideId]); let nextStatus;
                if (currentStatus === 0) nextStatus = 1; else if (currentStatus === 1) nextStatus = 2; else if (currentStatus === 2) nextStatus = 5; else if (currentStatus === 5) nextStatus = 3; else if (currentStatus === 3) nextStatus = 4; 
                if(nextStatus === undefined) return;
                if(currentStatus === 0) {
                    if(!confirm("Iniciar recolha e abrir GPS?")) return;
                    if (currentRideData.clientnumber) {
                        let cNum = currentRideData.clientnumber.replace(/[^0-9]/g, '');
                        if (cNum.length > 7) {
                            // baseUrl: usa origin sempre que possível (funciona em Capacitor bundled ou web).
                            // Fallback para split do path (compatibilidade com URLs antigas).
                            let baseUrl = (window.location.origin && !window.location.origin.startsWith('capacitor'))
                                ? window.location.origin
                                : (window.location.href.split('/Includes/dist/pages/')[0] || 'https://syncride.wmservers.pt');
                            let trackLink = baseUrl + '/track.php?id=' + rideId;
                            let msg = encodeURIComponent("Hello! Your driver is on the way. Track aqui: " + trackLink);
                            openExternal("https://wa.me/" + cNum + "?text=" + msg);
                        }
                    }
                    startLiveTracking(rideId); openWaze(currentRideData.start);
                }
                else if(currentStatus === 1) { if(!confirm("Confirma que chegou?")) return; }
                else if(currentStatus === 2) { if(!confirm("Confirmar que já está com o cliente?")) return; }
                else if(currentStatus === 5) { if(!confirm("Iniciar viagem para destino?")) return; openWaze(currentRideData.end); }
                else if(currentStatus === 3) { if(!confirm("Terminar serviço?")) return; stopLiveTracking(); }
                updateStatusBackend(rideId, nextStatus);
            });
        }

        // --- 6. RENDER LIST ---
        function formatDate(date) { return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`; }
        function filterTrips(filter) {
            currentFilter = filter; if (typeof viagens === 'undefined') return;
            const t = new Date(); const y = new Date(t); y.setDate(t.getDate() - 1); const tm = new Date(t); tm.setDate(t.getDate() + 1);
            const map = { "yesterday": formatDate(y), "today": formatDate(t), "tomorrow": formatDate(tm) };
            renderList(viagens.filter(v => v.serviceDate === map[filter]));
        }
        function renderList(data) {
            const el = document.getElementById("rideList"); if(!el) return; el.innerHTML = "";
            if (data.length === 0) { el.innerHTML = "<div class='text-center py-5 text-muted'><i class='bi bi-calendar-x fs-1 opacity-50'></i><p class='mt-2'>Sem serviços.</p></div>"; return; }
            data.forEach(v => {
                const isPriv = (v.serviceType == 1); const badgeClass = isPriv ? 'badge-private' : 'badge-shared';
                let extraBadges = '';
                if (v.partner_id && v.partner_id > 0) {
                    if (v.AgencyName) extraBadges += `<span class="ride-badge bg-info bg-opacity-10 text-info border border-info border-opacity-25"><i class="bi bi-building-fill"></i> ${v.AgencyName}</span>`;
                    extraBadges += `<span class="ride-badge ${v.has_key == 1 ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' : 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25'}"><i class="bi bi-key-fill"></i> ${v.has_key == 1 ? 'Chave' : 'Sem Chave'}</span>`;
                }
                el.innerHTML += `<div class="ride-card open-modal" data-id="${v.ServiceID}" data-start="${v.serviceStartPoint}" data-end="${v.serviceTargetPoint}"
                        data-time="${v.serviceStartTime.substr(0, 5)}" data-date="${v.serviceDate}" data-paxadt="${v.paxADT||0}" data-paxchd="${v.paxCHD||0}"
                        data-flight="${v.FlightNumber || ''}" data-client="${v.NomeCliente || ''}" data-clientnumber="${v.ClientNumber || ''}"
                        data-price="${v.total_price || ''}" data-haskey="${v.has_key || 0}" data-partnerid="${v.partner_id || 0}" data-agencyname="${v.AgencyName || ''}" data-agencyphone="${v.AgencyPhone || ''}">
                    <div class="ride-header"><div class="ride-time">${v.serviceStartTime.substr(0, 5)}</div><div class="ride-badges-container"><span class="ride-badge ${badgeClass}">${isPriv ? 'Privado' : 'Partilhado'}</span>${extraBadges}</div></div>
                    <div class="card-timeline"><div class="ct-point"><div class="ct-dot dot-pickup"></div><span class="ct-text">${v.serviceStartPoint}</span></div>
                    <div class="ct-point"><div class="ct-dot dot-dropoff"></div><span class="ct-text">${v.serviceTargetPoint}</span></div></div>
                    ${v.total_price > 0 ? `<div class="price-tag"><i class="bi bi-cash"></i> ${parseFloat(v.total_price).toFixed(2)}€</div>` : ''}</div>`;
            });
            document.querySelectorAll(".open-modal").forEach(card => {
                card.addEventListener("click", () => {
                    const d = card.dataset; currentRideData = d; const m = document.getElementById('detailsModal');
                    m.querySelector("#modalIdDisplay").textContent = d.id;
                    m.querySelector("#modalPickup").textContent = d.start; m.querySelector("#modalDropoff").textContent = d.end;
                    m.querySelector("#modalADT").textContent = d.paxadt; m.querySelector("#modalCHD").textContent = d.paxchd;
                    m.querySelector("#modalClient").textContent = d.client || 'Cliente'; m.querySelector("#modalClientNumber").textContent = d.clientnumber;
                    const wa = document.getElementById('whatsappContainer'); wa.innerHTML = ''; wa.style.display = 'none';
                    if (d.clientnumber && d.clientnumber.replace(/[^0-9]/g, '').length > 7) { wa.style.display = 'block'; wa.innerHTML = `<a href="https://wa.me/${d.clientnumber.replace(/[^0-9]/g, '')}" target="_blank" class="btn-whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</a>`; }
                    const waAloj = document.getElementById('whatsappAlojamento');
                    if (d.partnerid && d.partnerid > 0 && d.agencyphone) {
                        const alojPhone = '351' + String(d.agencyphone).replace(/[^0-9]/g, '');
                        const alojMsg = encodeURIComponent(`A sair do aeroporto 🛬\nCliente: ${d.client}\nDestino: ${d.end}`);
                        waAloj.href = `https://wa.me/${alojPhone}?text=${alojMsg}`;
                        waAloj.style.display = 'flex';
                    } else {
                        waAloj.style.display = 'none';
                    }
                    const bc = document.getElementById('modalBadgesContainer'); bc.innerHTML = ''; 
                    if (d.price && parseFloat(d.price) > 0) bc.innerHTML += `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-2"><i class="bi bi-currency-euro"></i> ${parseFloat(d.price).toFixed(2)}</span>`;
                    if (d.partnerid && d.partnerid > 0) {
                        if(d.agencyname) bc.innerHTML += `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-2"><i class="bi bi-building"></i> ${d.agencyname}</span>`;
                        bc.innerHTML += `<span class="badge bg-${d.haskey == 1 ? 'success' : 'danger'} bg-opacity-10 text-${d.haskey == 1 ? 'success' : 'danger'} border border-${d.haskey == 1 ? 'success' : 'danger'} border-opacity-25 rounded-pill px-2"><i class="bi bi-key-fill"></i> ${d.haskey == 1 ? 'Com Chave' : 'Sem Chave'}</span>`;
                    }
                    const tl = document.getElementById("trackFlightLink");
                    if(d.flight && d.flight.trim() !== '') { tl.style.display = "inline-flex"; document.getElementById("modalFlight").textContent = d.flight; tl.href = "https://www.flightradar24.com/data/flights/" + d.flight.replace(/\s/g, ''); } else { tl.style.display = "none"; }
                    updateButtonUI(localTripStatus[d.id]); new bootstrap.Modal(m).show();
                });
            });
        }
        document.querySelectorAll(".filter-btn").forEach(b => { b.addEventListener("click", function() { document.querySelectorAll(".filter-btn").forEach(x => x.classList.remove("active")); this.classList.add("active"); filterTrips(this.dataset.filter); }); });
        filterTrips("today");

        // --- 7. CAMERA & AIRPORT LOGIC ---
        const airportOverlay = document.getElementById('airportOverlay');
        const nameElement = document.getElementById('airportClientName');
        let currentFontSize = 15; // Unidade em vw

        document.getElementById('btnAirportMode').onclick = async () => {
            const rawName = currentRideData.client || "CLIENTE";
            const flight = currentRideData.flight || "";

            const wordsArr = rawName.trim().split(/\s+/);
            const wrappedWords = wordsArr.map(w => `<span class="name-part">${w}</span>`).join(' ');
            nameElement.innerHTML = wrappedWords;
            document.getElementById('airportFlight').textContent = flight;

            currentFontSize = (wordsArr.length <= 2) ? 18 : 12;
            nameElement.style.fontSize = currentFontSize + "vw";

            airportOverlay.style.display = "flex";
            if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();

            // Wake Lock — mantém o ecrã ativo enquanto a placa está visível
            if ('wakeLock' in navigator) {
                try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {}
            }
        };

        // Função de Zoom Manual
        function updateZoom(delta) {
            currentFontSize += delta;
            if (currentFontSize < 5) currentFontSize = 5;
            if (currentFontSize > 50) currentFontSize = 50;
            
            const unit = airportOverlay.classList.contains('landscape-mode') ? "vh" : "vw";
            nameElement.style.fontSize = currentFontSize + unit;
        }

        document.getElementById('btnZoomIn').onclick = (e) => { e.stopPropagation(); updateZoom(2); };
        document.getElementById('btnZoomOut').onclick = (e) => { e.stopPropagation(); updateZoom(-2); };

        document.getElementById('closeAirportMode').onclick = () => {
            airportOverlay.style.display = "none";
            if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
            if (wakeLock) { wakeLock.release().catch(() => {}); wakeLock = null; }
        };

        // Re-adquire wake lock se o dispositivo o libertou temporariamente (ex: bloqueio de ecrã)
        document.addEventListener('visibilitychange', async () => {
            if (wakeLock === null && document.visibilityState === 'visible' && airportOverlay.style.display !== 'none') {
                if ('wakeLock' in navigator) {
                    try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {}
                }
            }
        });

        document.getElementById('rotateScreenBtn').onclick = () => {
            airportOverlay.classList.toggle('landscape-mode');
            const isLandscape = airportOverlay.classList.contains('landscape-mode');
            nameElement.style.fontSize = currentFontSize + (isLandscape ? "vh" : "vw");
        };

        // --- Camera Logic ---
        const video = document.getElementById('cameraStream'); const canvas = document.getElementById('photoCanvas');
        const camOverlay = document.getElementById('cameraOverlay'); const loading = document.getElementById('cameraLoading');
        const btnSend = document.getElementById('btnConfirmSend');
        function stopCameraStream() { if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; } if(locationWatcher) navigator.geolocation.clearWatch(locationWatcher); }
        function closeCameraOverlay() { stopCameraStream(); camOverlay.style.display = 'none'; cameraZoomLevel = 1; video.style.transform = 'scale(1)'; }
        function applyCameraZoom(delta) {
            cameraZoomLevel = Math.min(Math.max(cameraZoomLevel + delta, 1), 8);
            video.style.transform = `scale(${cameraZoomLevel})`;
        }
        function updateCameraUI(state) {
             const cCap = document.getElementById('stepCaptureControls'); const cConf = document.getElementById('stepConfirmControls');
             if (state === 'loading') { loading.style.display='block'; video.style.display='none'; canvas.style.display='none'; cCap.classList.add('d-none'); cConf.classList.add('d-none'); }
             else if (state === 'capture') { loading.style.display='none'; video.style.display='block'; canvas.style.display='none'; cCap.classList.remove('d-none'); cCap.classList.add('d-flex'); cConf.classList.add('d-none'); }
             else if (state === 'review') { loading.style.display='none'; video.style.display='none'; canvas.style.display='block'; cCap.classList.add('d-none'); cConf.classList.remove('d-none'); cConf.classList.add('d-flex'); btnSend.disabled=false; }
        }
        async function startCamera() {
            stopCameraStream(); cameraZoomLevel = 1; video.style.transform = 'scale(1)';
            camOverlay.style.display = 'flex'; updateCameraUI('loading');
            try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: currentFacingMode } }); video.srcObject = stream; video.onloadedmetadata = () => updateCameraUI('capture'); }
            catch (e) { alert('Erro: '+e.message); closeCameraOverlay(); }
            if ("geolocation" in navigator) locationWatcher = navigator.geolocation.watchPosition(p => { currentLat=p.coords.latitude; currentLng=p.coords.longitude; });
        }
        document.getElementById('uploadNoShow').onclick = () => openCamera('noshow');
        document.getElementById('uploadVoucher').onclick = () => openCamera('voucher');
        function openCamera(mode) { currentMode = mode; document.getElementById('cameraInstruction').textContent = (mode==='voucher'?'Fotografar Voucher':'Fotografar No-Show'); startCamera(); }
        document.getElementById('btnRotateCamera').onclick = () => { currentFacingMode = (currentFacingMode==='environment'?'user':'environment'); startCamera(); };
        document.getElementById('btnCapture').onclick = () => {
            const vw = video.videoWidth; const vh = video.videoHeight;
            const sw = vw / cameraZoomLevel; const sh = vh / cameraZoomLevel;
            const sx = (vw - sw) / 2; const sy = (vh - sh) / 2;
            canvas.width = vw; canvas.height = vh;
            canvas.getContext('2d').drawImage(video, sx, sy, sw, sh, 0, 0, vw, vh);
            updateCameraUI('review');
        };
        document.getElementById('btnRetake').onclick = () => updateCameraUI('capture');
        document.getElementById('btnCameraZoomIn').onclick = (e) => { e.stopPropagation(); applyCameraZoom(0.5); };
        document.getElementById('btnCameraZoomOut').onclick = (e) => { e.stopPropagation(); applyCameraZoom(-0.5); };

        // Pinch-to-zoom na área da câmara
        const cameraViewArea = document.getElementById('cameraViewArea');
        cameraViewArea.addEventListener('touchstart', (e) => {
            if (e.touches.length === 2) {
                pinchInitialDistance = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
            }
        }, { passive: true });
        cameraViewArea.addEventListener('touchmove', (e) => {
            if (e.touches.length === 2 && pinchInitialDistance > 0) {
                const dist = Math.hypot(e.touches[0].clientX - e.touches[1].clientX, e.touches[0].clientY - e.touches[1].clientY);
                const delta = (dist - pinchInitialDistance) * 0.01;
                cameraZoomLevel = Math.min(Math.max(cameraZoomLevel + delta, 1), 8);
                video.style.transform = `scale(${cameraZoomLevel})`;
                pinchInitialDistance = dist;
            }
        }, { passive: true });
        btnSend.onclick = () => {
            btnSend.disabled = true; const img = canvas.toDataURL('image/jpeg');
            fetch(currentMode === 'voucher' ? 'upload-voucher.php' : 'upload-no-show.php', { method: 'POST', body: JSON.stringify({ trip_id: currentRideData.id, image_data: img, lat: currentLat, lng: currentLng }) })
            .then(r=>r.json()).then(d=>{ if(d.success) { alert(d.message); closeCameraOverlay(); if(currentMode==='noshow') updateStatusBackend(currentRideData.id, 4); } else { btnSend.disabled=false; } });
        };
        document.getElementById('profilePhotoInput').onchange = (e) => { const [f] = e.target.files; if (f) document.getElementById('currentProfilePhoto').src = URL.createObjectURL(f); };
    </script>
  </body>
</html>