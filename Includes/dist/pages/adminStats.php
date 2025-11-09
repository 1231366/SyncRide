<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está logado e tem a role de admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    // Redireciona para a página de login se não for admin
    header("Location: ../../../index.php");
    exit();
}

require_once __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração da DB

// --- Lógica para buscar os dados ---

// Definir filtros (padrão: mês/ano atuais, todos os condutores)
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selectedDriverId = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0; // 0 representa "Todos"

// Validar mês e ano
if ($selectedMonth < 1 || $selectedMonth > 12) $selectedMonth = date('m');
if ($selectedYear < 2000 || $selectedYear > date('Y') + 5) $selectedYear = date('Y');

// Buscar a lista de condutores para o dropdown
$sqlCondutores = "SELECT ID, name FROM Users WHERE role = 2 ORDER BY name ASC";
$stmtCondutores = $pdo->query($sqlCondutores);
$condutores = $stmtCondutores->fetchAll(PDO::FETCH_ASSOC);

// Preparar a base da consulta SQL
$sqlBase = "
    SELECT
        COUNT(s.ID) AS totalViagens,
        SUM(s.paxADT + s.paxCHD) AS totalPassageiros,
        SUM(CASE WHEN s.serviceType = 1 THEN 1 ELSE 0 END) AS totalPrivadas,
        SUM(CASE WHEN s.serviceType = 0 THEN 1 ELSE 0 END) AS totalPartilhadas
        /* Podes adicionar mais SUM(...) ou COUNT(...) aqui se precisares de outras estatísticas */
    FROM Services s
";
$params = ['year' => $selectedYear, 'month' => $selectedMonth];
$whereConditions = ["YEAR(s.serviceDate) = :year", "MONTH(s.serviceDate) = :month"];

// Definir título e nome do condutor (default: Geral)
$pageSubtitle = "Estatísticas Gerais";
$selectedDriverName = "Todos";

// Se um condutor específico foi selecionado, ajustar a query e o título
if ($selectedDriverId > 0) {
    // Adiciona o JOIN e a condição WHERE para filtrar pelo condutor
    $sqlBase .= " INNER JOIN Services_Rides sr ON s.ID = sr.RideID"; // JOIN necessário para filtrar por UserID
    $whereConditions[] = "sr.UserID = :driver_id";
    $params['driver_id'] = $selectedDriverId;

    // Encontrar o nome do condutor selecionado para o título
    foreach ($condutores as $condutor) {
        if ($condutor['ID'] == $selectedDriverId) {
            $selectedDriverName = $condutor['name'];
            break;
        }
    }
    $pageSubtitle = "Estatísticas de " . htmlspecialchars($selectedDriverName);
} else {
    // Se "Todos" está selecionado, não adicionamos filtro de condutor.
    // A query base já busca todas as viagens do período.
    // Se quisesses contar apenas viagens *atribuídas* na vista geral, adicionarias aqui:
    // $sqlBase .= " INNER JOIN Services_Rides sr ON s.ID = sr.RideID";
}

// Construir a query final
$sqlStats = $sqlBase . " WHERE " . implode(" AND ", $whereConditions);

// Executar a query
$stmtStats = $pdo->prepare($sqlStats);
$stmtStats->execute($params);
$stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

// Garantir que temos valores (0 em vez de null) se a query não retornar resultados
$stats = array_map(function($value) { return $value ?? 0; }, $stats);

// Nomes dos meses para o dropdown
$meses = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

?>
<!DOCTYPE html>
<html lang="pt">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Estatísticas - <?= htmlspecialchars($selectedDriverName) ?> (<?= htmlspecialchars($meses[$selectedMonth]) ?>/<?= htmlspecialchars($selectedYear) ?>)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css" integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css" integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg=" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI=" crossorigin="anonymous" />
    <link rel="stylesheet" href="../../dist/css/adminlte.css" />
     <style>
      /* Ajuste para ecrãs menores no formulário de seleção */
      @media (max-width: 991px) { /* Ajustado para .col-lg */
        .form-select-filters .col-lg-3,
        .form-select-filters .col-lg-2 {
          margin-bottom: 10px;
        }
        .form-select-filters .btn {
            width: 100%;
        }
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
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
              </a>
            </li>
            <li class="nav-item dropdown user-menu">
              <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                <img src="../../dist/assets/img/user2-160x160.jpg" class="user-image rounded-circle shadow" alt="User Image" />
                <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['name']); ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                <li class="user-header text-bg-primary">
                  <img src="../../dist/assets/img/user2-160x160.jpg" class="rounded-circle shadow" alt="User Image" />
                  <p><?php echo htmlspecialchars($_SESSION['name']); ?> - Admin</p>
                </li>
                <li class="user-footer">
                  <a href="#" class="btn btn-default btn-flat">Perfil</a>
                  <a href="logout.php" class="btn btn-default btn-flat float-end">Sair</a>
                </li>
              </ul>
            </li>
          </ul>
          </div>
        </nav>
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
          <a href="./admin.php" class="brand-link">
            <img src="../../dist/assets/img/AdminLTELogo.png" alt="AdminLTE Logo" class="brand-image opacity-75 shadow" />
            <span class="brand-text fw-light">SyncRide</span>
          </a>
        </div>
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
              <li class="nav-item">
                <a href="admin.php" class="nav-link">
                  <i class="nav-icon bi bi-speedometer"></i>
                  <p>Painel Principal</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="ManageRides.php" class="nav-link">
                  <i class="nav-icon bi bi-car-front-fill"></i>
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
                  <a href="manageStorage.php" class="nav-link">
                      <i class="nav-icon bi bi-box-seam-fill"></i>
                      <p>Gerir Armazenamento</p>
                  </a>
              </li>
               <li class="nav-item">
                  <a href="admin_stats.php" class="nav-link active"> <i class="nav-icon bi bi-bar-chart-line-fill"></i>
                      <p>Estatísticas</p>
                  </a>
              </li>
            </ul>
            </nav>
        </div>
        </aside>
      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6">
                 <h3 class="mb-0"><?= $pageSubtitle ?> <small>(<?= htmlspecialchars($meses[$selectedMonth]) ?>/<?= htmlspecialchars($selectedYear) ?>)</small></h3>
              </div>
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

            <div class="card card-primary card-outline mb-4">
              <div class="card-header">
                  <h3 class="card-title">Filtrar Estatísticas</h3>
              </div>
              <div class="card-body">
                  <form method="GET" action="admin_stats.php" class="row g-3 align-items-end form-select-filters">
                      <div class="col-lg-3">
                          <label for="month" class="form-label">Mês:</label>
                          <select name="month" id="month" class="form-select form-select-sm">
                              <?php foreach ($meses as $num => $nome): ?>
                                  <option value="<?= $num ?>" <?= ($num == $selectedMonth) ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($nome) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <div class="col-lg-3">
                          <label for="year" class="form-label">Ano:</label>
                          <select name="year" id="year" class="form-select form-select-sm">
                              <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                  <option value="<?= $y ?>" <?= ($y == $selectedYear) ? 'selected' : '' ?>>
                                      <?= $y ?>
                                  </option>
                              <?php endfor; ?>
                          </select>
                      </div>
                      <div class="col-lg-4">
                          <label for="driver_id" class="form-label">Condutor:</label>
                          <select name="driver_id" id="driver_id" class="form-select form-select-sm">
                              <option value="0" <?= ($selectedDriverId == 0) ? 'selected' : '' ?>>-- Todos os Condutores --</option>
                              <?php foreach ($condutores as $condutor): ?>
                                  <option value="<?= htmlspecialchars($condutor['ID']) ?>" <?= ($condutor['ID'] == $selectedDriverId) ? 'selected' : '' ?>>
                                      <?= htmlspecialchars($condutor['name']) ?>
                                  </option>
                              <?php endforeach; ?>
                          </select>
                      </div>
                      <div class="col-lg-2">
                          <button type="submit" class="btn btn-primary btn-sm w-100">Filtrar</button>
                      </div>
                  </form>
              </div>
            </div>

            <div class="row">
               <div class="col-lg-3 col-6">
                <div class="small-box text-bg-info">
                  <div class="inner">
                    <h3><?= $stats['totalViagens'] ?></h3>
                    <p>Total de Viagens</p>
                  </div>
                  <div class="icon"><i class="bi bi-car-front"></i></div>
                  </div>
              </div>

               <div class="col-lg-3 col-6">
                <div class="small-box text-bg-warning">
                  <div class="inner">
                    <h3><?= $stats['totalPassageiros'] ?></h3>
                    <p>Total de Passageiros</p>
                  </div>
                   <div class="icon"><i class="bi bi-people"></i></div>
                  </div>
              </div>

               <div class="col-lg-3 col-6">
                <div class="small-box text-bg-dark">
                  <div class="inner">
                    <h3><?= $stats['totalPrivadas'] ?></h3>
                    <p>Viagens Privadas</p>
                  </div>
                   <div class="icon"><i class="bi bi-person-lock"></i></div>
                 </div>
              </div>

               <div class="col-lg-3 col-6">
                 <div class="small-box text-bg-secondary">
                  <div class="inner">
                    <h3><?= $stats['totalPartilhadas'] ?></h3>
                    <p>Viagens Partilhadas</p>
                  </div>
                   <div class="icon"><i class="bi bi-person-gear"></i></div>
                  </div>
              </div>
               </div><?php
                // Se quiseres mostrar a lista de viagens filtrada, podes fazer outra query aqui
                // Exemplo: Buscar as viagens correspondentes aos filtros atuais
                /*
                $sqlViagensDetalhes = "SELECT s.*, u.name as driverName FROM Services s
                                       LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                                       LEFT JOIN Users u ON sr.UserID = u.ID
                                       WHERE YEAR(s.serviceDate) = :year AND MONTH(s.serviceDate) = :month";
                if ($selectedDriverId > 0) {
                    $sqlViagensDetalhes .= " AND sr.UserID = :driver_id";
                }
                $sqlViagensDetalhes .= " ORDER BY s.serviceDate DESC, s.serviceStartTime DESC";

                $stmtViagens = $pdo->prepare($sqlViagensDetalhes);
                $stmtViagens->execute($params); // Usa os mesmos $params da query de stats
                $viagensDoPeriodo = $stmtViagens->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($viagensDoPeriodo)) {
                    echo '<div class="card mt-4"><div class="card-header"><h3 class="card-title">Detalhe das Viagens</h3></div><div class="card-body table-responsive p-0"><table class="table table-striped table-hover">';
                    echo '<thead><tr><th>Data</th><th>Hora</th><th>Origem</th><th>Destino</th><th>PAX</th><th>Tipo</th><th>Condutor</th></tr></thead><tbody>';
                    foreach($viagensDoPeriodo as $v) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($v['serviceDate']) . '</td>';
                        echo '<td>' . htmlspecialchars($v['serviceStartTime']) . '</td>';
                        echo '<td>' . htmlspecialchars($v['serviceStartPoint']) . '</td>';
                        echo '<td>' . htmlspecialchars($v['serviceTargetPoint']) . '</td>';
                        echo '<td>' . (htmlspecialchars($v['paxADT']) + htmlspecialchars($v['paxCHD'])) . '</td>';
                        echo '<td><span class="badge ' . ($v['serviceType'] == 1 ? 'bg-dark' : 'bg-warning') . '">' . ($v['serviceType'] == 1 ? 'Private' : 'Shared') . '</span></td>';
                        echo '<td>' . ($v['driverName'] ? htmlspecialchars($v['driverName']) : '<span class="badge bg-secondary">N/A</span>') . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table></div></div>';
                } else {
                     echo '<p class="text-center text-muted mt-4">Nenhuma viagem encontrada para este período/condutor.</p>';
                }
                */
             ?>

          </div></div></main><footer class="app-footer">
        SyncRide All rights reserved.
      </footer>
      </div>
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js" integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <script src="../../dist/js/adminlte.js"></script>
    <script>
      // Configuração do OverlayScrollbars (copiado de outras páginas)
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
    </body>
</html>