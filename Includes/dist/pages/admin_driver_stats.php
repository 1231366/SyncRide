<?php
session_start(); // Inicia a sessão

// 1. VERIFICAÇÃO DE ADMIN
// Esta página é para o Admin (role 1)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=../../../index.php");
    exit();
}

require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

// =================================================================
// 2. LÓGICA DE DADOS
// =================================================================

// Verifica os filtros GET
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : null;
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$page_title = "Atividade Geral dos Condutores";
$driver_name = "Visão Geral";
$meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

// Obter lista de todos os condutores para o dropdown
try {
    $available_drivers = $pdo->query("SELECT ID, name FROM Users WHERE role = 2 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    // Obter anos disponíveis para o filtro de ano
    $available_years = $pdo->query("SELECT DISTINCT YEAR(serviceDate) as ano FROM Services ORDER BY ano DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (empty($available_years)) {
        $available_years[] = date('Y');
    }
} catch (PDOException $e) {
    die("Erro ao carregar lista de condutores/anos: " . $e->getMessage());
}

// Inicializar variáveis
$box1_val = 0; $box1_lbl = "-"; $box1_icon = "bi-person-check-fill"; $box1_color = "primary";
$box2_val = 0; $box2_lbl = "-"; $box2_icon = "bi-calendar-day"; $box2_color = "info";
$box3_val = 0; $box3_lbl = "-"; $box3_icon = "bi-calendar-month"; $box3_color = "warning";
$box4_val = 0; $box4_lbl = "-"; $box4_icon = "bi-bar-chart-fill"; $box4_color = "success";
$chart_labels = $meses_nomes;
$chart_data = array_fill(0, 12, 0);
$table_data = [];
$table_title = "";
$table_is_leaderboard = false;


if ($driver_id) {
    // -----------------------------------
    // VISTA FILTRADA (Condutor Específico)
    // -----------------------------------
    try {
        $stmt = $pdo->prepare("SELECT name FROM Users WHERE ID = ? AND role = 2");
        $stmt->execute([$driver_id]);
        $driver_name = $stmt->fetchColumn();
        if (!$driver_name) {
             header("Location: admin_driver_stats.php"); // ID inválido
             exit();
        }
        $page_title = "Atividade: " . htmlspecialchars($driver_name);

        // Caixas de Resumo (para este condutor)
        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = ? AND s.serviceDate = CURDATE()) AS trips_today,
            (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = ? AND YEARWEEK(s.serviceDate, 1) = YEARWEEK(CURDATE(), 1)) AS trips_week,
            (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = ? AND YEAR(s.serviceDate) = ? AND MONTH(s.serviceDate) = MONTH(CURDATE())) AS trips_month_current,
            (SELECT COUNT(*) FROM Services_Rides sr WHERE sr.UserID = ?) AS trips_total");
        $stmt->execute([$driver_id, $driver_id, $driver_id, $selectedYear, $driver_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $box1_val = $stats['trips_today']; $box1_lbl = "Viagens Hoje"; $box1_icon = "bi-calendar-day"; $box1_color = "info";
        $box2_val = $stats['trips_week'];  $box2_lbl = "Viagens Semana"; $box2_icon = "bi-calendar-week"; $box2_color = "primary";
        $box3_val = $stats['trips_month_current']; $box3_lbl = "Viagens Mês (Atual)"; $box3_icon = "bi-calendar-month"; $box3_color = "warning";
        $box4_val = $stats['trips_total']; $box4_lbl = "Viagens (Total)"; $box4_icon = "bi-bar-chart-fill"; $box4_color = "success";

        // Gráfico (para este condutor, no ano selecionado)
        $sqlChart = "SELECT MONTH(s.serviceDate) AS mes, COUNT(sr.associationID) AS total
                     FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID
                     WHERE sr.UserID = ? AND YEAR(s.serviceDate) = ?
                     GROUP BY mes";
        $stmtChart = $pdo->prepare($sqlChart);
        $stmtChart->execute([$driver_id, $selectedYear]);
        $monthly_results = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR); // [mes => total]
        foreach ($monthly_results as $mes => $total) {
            $chart_data[$mes - 1] = (int)$total;
        }

        // Tabela (Viagens Recentes deste condutor)
        $table_title = "Viagens Mais Recentes";
        $sqlTable = "SELECT s.ID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint, s.serviceType
                     FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID 
                     WHERE sr.UserID = ? 
                     ORDER BY s.serviceDate DESC, s.serviceStartTime DESC LIMIT 20";
        $stmtTable = $pdo->prepare($sqlTable);
        $stmtTable->execute([$driver_id]);
        $table_data = $stmtTable->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Erro ao carregar dados do condutor: " . $e->getMessage());
    }

} else {
    // -----------------------------------
    // VISTA GERAL (Todos os Condutores)
    // -----------------------------------
    $page_title = "Atividade Geral dos Condutores";
    $table_title = "Leaderboard (Ano: $selectedYear)";
    $table_is_leaderboard = true;

    try {
        // Caixas de Resumo (Geral)
        $box1_val = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 2")->fetchColumn();
        $box1_lbl = "Condutores Ativos"; $box1_icon = "bi-people-fill"; $box1_color = "primary";
        $box2_val = $pdo->query("SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE s.serviceDate = CURDATE()")->fetchColumn();
        $box2_lbl = "Viagens Hoje (Geral)"; $box2_icon = "bi-calendar-day"; $box2_color = "info";
        $box3_val = $pdo->query("SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE YEAR(s.serviceDate) = YEAR(CURDATE()) AND MONTH(s.serviceDate) = MONTH(CURDATE())")->fetchColumn();
        $box3_lbl = "Viagens Mês (Geral)"; $box3_icon = "bi-calendar-month"; $box3_color = "warning";
        $box4_val = $pdo->query("SELECT COUNT(*) FROM Services_Rides")->fetchColumn();
        $box4_lbl = "Viagens (Total)"; $box4_icon = "bi-bar-chart-fill"; $box4_color = "success";
        
        // Gráfico (Geral, do ano selecionado)
        $sqlChart = "SELECT MONTH(s.serviceDate) AS mes, COUNT(sr.associationID) AS total
                     FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID
                     WHERE YEAR(s.serviceDate) = ?
                     GROUP BY mes";
        $stmtChart = $pdo->prepare($sqlChart);
        $stmtChart->execute([$selectedYear]);
        $monthly_results = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($monthly_results as $mes => $total) {
            $chart_data[$mes - 1] = (int)$total;
        }

        // Tabela (Leaderboard de Condutores, do ano selecionado)
        $sqlTable = "SELECT 
                        u.ID, 
                        u.name,
                        (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = u.ID AND s.serviceDate = CURDATE()) AS trips_today,
                        (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = u.ID AND YEARWEEK(s.serviceDate, 1) = YEARWEEK(CURDATE(), 1)) AS trips_week,
                        (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = u.ID AND YEAR(s.serviceDate) = ?) AS trips_year,
                        (SELECT COUNT(*) FROM Services_Rides sr WHERE sr.UserID = u.ID) AS trips_total
                     FROM Users u
                     WHERE u.role = 2
                     ORDER BY trips_year DESC, trips_total DESC, u.name ASC";
        
        $stmtTable = $pdo->prepare($sqlTable);
        $stmtTable->execute([$selectedYear]);
        $table_data = $stmtTable->fetchAll(PDO::FETCH_ASSOC);
        
        // Separar o Top 3 do resto
        $top3 = array_slice($table_data, 0, 3);
        $rest_of_drivers = array_slice($table_data, 3);


    } catch (PDOException $e) {
        die("Erro ao carregar dados gerais: " . $e->getMessage());
    }
}

// Passar dados para o JavaScript
$js_data = json_encode([
    'labels' => $chart_labels,
    'data' => $chart_data,
    'driver_name' => $driver_name,
    'year' => $selectedYear
]);
?>

<!doctype html>
<html lang="pt">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $page_title; ?> - Admin SyncRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css"
      integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg="
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI="
      crossorigin="anonymous"
    />
    <link rel="stylesheet" href="../../dist/css/adminlte.css" />
    <style>
        .list-group-item-action {
            transition: background-color 0.2s ease-in-out, transform 0.2s ease;
        }
        .list-group-item-action:hover {
            transform: translateX(5px);
            background-color: #f8f9fa;
        }
        .leaderboard-name {
            font-weight: 600;
            color: #0d6efd; /* Cor primaria do bootstrap */
            text-decoration: none;
        }
        .leaderboard-name:hover {
            text-decoration: underline;
        }
        .trip-details {
            font-size: 0.9rem;
            color: #555;
        }
        .trip-date {
            font-weight: 600;
            color: #333;
        }
        /* Cor da borda para viagens (como no driver.php) */
        .trip-card-private {
            border-left: 5px solid #007bff; /* Azul para Private */
        }
        .trip-card-shared {
            border-left: 5px solid #ffc107; /* Amarelo para Shared */
        }
        
        /* Ajuste para o formulário de filtro no cabeçalho do card */
        .card-header .form-select-sm {
            font-size: 0.8rem;
            padding-top: 0.25rem;
            padding-bottom: 0.25rem;
        }
        .card-header .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    </style>
  </head>
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
      <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
          </ul>
          <ul class="navbar-nav ms-auto">
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
              </a>
            </li>
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img
                  src="../../dist/assets/img/user2-160x160.jpg"
                  class="user-image rounded-circle shadow"
                  alt="User Image"
                />
                <span class="d-none d-md-inline"><?php  echo $_SESSION['name']; ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                  <img
                    src="../../dist/assets/img/user2-160x160.jpg"
                    class="rounded-circle shadow"
                    alt="User Image"
                  />
                  <p>
                    <?php echo $_SESSION['name']; ?> - Admin
                  </p>
                </li>
                <li class="user-footer">
                  <a href="#" class="btn btn-default btn-flat">Profile</a>
                  <a href="logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
                </li>
                </ul>
            </li>
            </ul>
          </div>
        </nav>
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
          <a href="./admin.php" class="brand-link">
            <img
              src="../../dist/assets/img/AdminLTELogo.png"
              alt="AdminLTE Logo"
              class="brand-image opacity-75 shadow"
            />
            <span class="brand-text fw-light">SyncRide</span>
            </a>
          </div>
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="menu"
              data-accordion="false"
            >
            <li class="nav-item">
                <a href="admin.php" class="nav-link">
                    <i class="nav-icon bi bi-speedometer"></i>
                    <p>Painel de Administrador</p>
                </a>
            </li>

              <li class="nav-item">
                  <a href="ManageRides.php" class="nav-link">
                      <i class="nav-icon bi bi-box-seam-fill"></i>
                      <p>Gerir Viagens</p>
                  </a>
              </li>

              <li class="nav-item">
                  <a href="manageUsers.php" class="nav-link">
                      <i class="nav-icon bi bi-people-fill"></i>
                      <p>Gerir Funcionários</p>
                  </a>
              </li>
              
              <li class="nav-item">
                  <a href="admin_driver_stats.php" class="nav-link active">
                      <i class="nav-icon bi bi-graph-up"></i>
                      <p>Estatísticas (Condutores)</p>
                  </a>
              </li>
              
              <li class="nav-item">
                  <a href="manageStorage.php" class="nav-link">
                      <i class="nav-icon bi bi-archive-fill"></i>
                      <p>Gerir Armazenamento</p>
                  </a>
              </li>

            </nav>
        </div>
        </aside>
      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0"><?php echo $page_title; ?></h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Estatísticas</li>
                </ol>
              </div>
            </div>
            </div>
          </div>
        <div class="app-content">
          <div class="container-fluid">
            
            <div class="row">
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-<?php echo $box1_color; ?>">
                  <div class="inner">
                    <h3><?php echo $box1_val; ?></h3>
                    <p><?php echo $box1_lbl; ?></p>
                  </div>
                  <div class="icon"><i class="bi <?php echo $box1_icon; ?>"></i></div>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-<?php echo $box2_color; ?>">
                  <div class="inner">
                    <h3><?php echo $box2_val; ?></h3>
                    <p><?php echo $box2_lbl; ?></p>
                  </div>
                  <div class="icon"><i class="bi <?php echo $box2_icon; ?>"></i></div>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-<?php echo $box3_color; ?>">
                  <div class="inner">
                    <h3><?php echo $box3_val; ?></h3>
                    <p><?php echo $box3_lbl; ?></p>
                  </div>
                  <div class="icon"><i class="bi <?php echo $box3_icon; ?>"></i></div>
                </div>
              </div>
              <div class="col-lg-3 col-6">
                <div class="small-box text-bg-<?php echo $box4_color; ?>">
                  <div class="inner">
                    <h3><?php echo $box4_val; ?></h3>
                    <p><?php echo $box4_lbl; ?></p>
                  </div>
                  <div class="icon"><i class="bi <?php echo $box4_icon; ?>"></i></div>
                </div>
              </div>
            </div>
            <div class="row">
                <div class="col-lg-7">
                    <div class="card card-primary card-outline shadow-sm" style="min-height: 520px;">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0">
                                <i class="bi bi-bar-chart-line-fill me-1"></i>
                                Volume de Viagens em <?php echo $selectedYear; ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container" style="min-height: 420px;">
                                <canvas id="mainChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-5">
                    <div class="card card-success card-outline shadow-sm" style="min-height: 520px;">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center py-2">
                            <h3 class="card-title mb-0 fs-6">
                                <i class="bi <?php echo $table_is_leaderboard ? 'bi-trophy-fill' : 'bi-list-task'; ?> me-1"></i>
                                <?php echo $table_title; ?>
                            </h3>
                            <form method="GET" action="admin_driver_stats.php" class="d-flex align-items-center ms-auto" id="filter-form">
                                <select name="driver_id" class="form-select form-select-sm me-2" onchange="this.form.submit()" style="max-width: 150px;">
                                    <option value="">-- Visão Geral --</option>
                                    <?php foreach ($available_drivers as $driver): ?>
                                        <option value="<?php echo $driver['ID']; ?>" <?php echo ($driver['ID'] == $driver_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($driver['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="year" class="form-select form-select-sm me-2" onchange="this.form.submit()" style="max-width: 90px;">
                                    <?php foreach ($available_years as $year): ?>
                                        <option value="<?php echo $year; ?>" <?php echo ($year == $selectedYear) ? 'selected' : ''; ?>>
                                            <?php echo $year; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="admin_driver_stats.php" class="btn btn-sm btn-outline-secondary" title="Limpar Filtro"><i class="bi bi-x-lg"></i></a>
                            </form>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php if (empty($table_data)): ?>
                                    <div class="list-group-item text-center p-4">Nenhum dado encontrado.</div>
                                <?php else: ?>
                                    
                                    <?php if ($table_is_leaderboard): ?>
                                        <?php if (isset($top3[0])): ?>
                                            <div class="list-group-item list-group-item-action p-3" style="background-color: #fffaf0;">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div class="fs-5">
                                                        <span class="me-2">🥇</span>
                                                        <a href="admin_driver_stats.php?driver_id=<?php echo $top3[0]['ID']; ?>&year=<?php echo $selectedYear; ?>" class="leaderboard-name text-dark">
                                                            <?php echo htmlspecialchars($top3[0]['name']); ?>
                                                        </a>
                                                    </div>
                                                    <span class="badge bg-warning fs-6" title="Viagens no Ano"><?php echo $top3[0]['trips_year']; ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($top3[1])): ?>
                                            <div class="list-group-item list-group-item-action p-3" style="background-color: #f8f9fa;">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div class="fs-5">
                                                        <span class="me-2">🥈</span>
                                                        <a href="admin_driver_stats.php?driver_id=<?php echo $top3[1]['ID']; ?>&year=<?php echo $selectedYear; ?>" class="leaderboard-name text-dark">
                                                            <?php echo htmlspecialchars($top3[1]['name']); ?>
                                                        </a>
                                                    </div>
                                                    <span class="badge bg-secondary fs-6" title="Viagens no Ano"><?php echo $top3[1]['trips_year']; ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($top3[2])): ?>
                                            <div class="list-group-item list-group-item-action p-3" style="background-color: #fdf8f5;">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div class="fs-5">
                                                        <span class="me-2">🥉</span>
                                                        <a href="admin_driver_stats.php?driver_id=<?php echo $top3[2]['ID']; ?>&year=<?php echo $selectedYear; ?>" class="leaderboard-name text-dark">
                                                            <?php echo htmlspecialchars($top3[2]['name']); ?>
                                                        </a>
                                                    </div>
                                                    <span class="badge fs-6" title="Viagens no Ano" style="background-color: #cd7f32; color: white;"><?php echo $top3[2]['trips_year']; ?></span>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php foreach ($rest_of_drivers as $key => $driver): ?>
                                             <div class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between align-items-center">
                                                    <div>
                                                        <span class="me-2 text-muted" style="font-size: 0.9rem;"><?php echo $key + 4; ?>.</span>
                                                        <a href="admin_driver_stats.php?driver_id=<?php echo $driver['ID']; ?>&year=<?php echo $selectedYear; ?>" class="leaderboard-name">
                                                            <?php echo htmlspecialchars($driver['name']); ?>
                                                        </a>
                                                    </div>
                                                    <span class="badge bg-dark"><?php echo $driver['trips_year']; ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php else: ?>
                                        <?php foreach ($table_data as $row): ?>
                                            <?php $borderColorClass = $row['serviceType'] == 1 ? 'trip-card-private' : 'trip-card-shared'; ?>
                                            <div class="list-group-item list-group-item-action <?php echo $borderColorClass; ?> px-3 py-2">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <span class="trip-date"><?php echo htmlspecialchars($row['serviceDate']); ?></span>
                                                    <span class="fw-bold text-primary">🕒 <?php echo substr($row['serviceStartTime'], 0, 5); ?></span>
                                                </div>
                                                <div class="trip-details">
                                                    📍 <?php echo htmlspecialchars($row['serviceStartPoint']); ?> 
                                                    <i class="bi bi-arrow-right-short"></i> 
                                                    🏁 <?php echo htmlspecialchars($row['serviceTargetPoint']); ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </div>
          </div>
        </main>
      <footer class="app-footer">
        <div class="float-end d-none d-sm-inline"></div>
        <strong>SyncRide All rights reserved.</strong>
      </footer>
      </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
      integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
      integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
      crossorigin="anonymous"
    ></script>
    <script src="../../dist/js/adminlte.js"></script>
    <script>
      const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
      const Default = {
        scrollbarTheme: 'os-theme-light',
        scrollbarAutoHide: 'leave',
        scrollbarClickScroll: true,
      };
      document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
          OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
            scrollbars: {
              theme: Default.scrollbarTheme,
              autoHide: Default.scrollbarAutoHide,
              clickScroll: Default.scrollbarClickScroll,
            },
          });
        }
      });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Passar os dados do PHP para o JavaScript
        const chartConfig = <?php echo $js_data; ?>;

        const ctx = document.getElementById('mainChart').getContext('2d');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartConfig.labels,
                    datasets: [{
                        label: 'Nº de Viagens',
                        data: chartConfig.data,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1,
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            ticks: { 
                                precision: 0 // Garante que o eixo Y não mostra casas decimais
                            } 
                        }
                    },
                    plugins: {
                        legend: { 
                            display: false 
                        },
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItems) {
                                    // Tooltip mostra "Mês: X Viagens"
                                    return tooltipItems[0].label;
                                },
                                label: function(tooltipItem) {
                                    return ' Viagens: ' + tooltipItem.raw;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
    </script>
    
  </body>
  </html>