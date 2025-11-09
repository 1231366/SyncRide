<?php
// =================================================================
// 1. INÍCIO DO BLOCO PHP - BUSCAR TODOS OS DADOS
// =================================================================
session_start();
require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

// Proteger a página e obter o ID do utilizador
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 2) {
    header("refresh: 1; url=../../../index.php");
    exit();
}
$userId = $_SESSION['user_id'];

// --- DADOS PARA AS "SMALL BOXES" ---
$viagensTotal = 0;
$viagensUltimoMes = 0;
$totalViagensAno = 0;
$mesMaisAtivo = '-';
$meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

try {
    // 1. Total de viagens feitas (desde sempre)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = ? AND s.serviceDate <= CURDATE()");
    $stmt->execute([$userId]);
    $viagensTotal = $stmt->fetchColumn();

    // 2. Viagens no último mês
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = ? AND s.serviceDate BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()");
    $stmt->execute([$userId]);
    $viagensUltimoMes = $stmt->fetchColumn();

    // --- DADOS PARA O GRÁFICO E CARDS DO ANO ---
    
    // Obter o ano a partir do URL (?year=...). Se não existir, usa o ano atual.
    $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    // Inicializar os dados de todos os meses a zero
    $monthly_data = array_fill(1, 12, 0); // Chaves de 1 a 12

    // Buscar dados mensais para o ano selecionado
    $sql = "SELECT MONTH(s.serviceDate) AS mes, COUNT(sr.associationID) AS total
            FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID
            WHERE sr.UserID = :userId AND YEAR(s.serviceDate) = :year AND s.serviceDate <= CURDATE()
            GROUP BY mes";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId, 'year' => $selectedYear]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $monthly_data[(int)$row['mes']] = (int)$row['total'];
    }

    // Buscar todos os anos disponíveis para o seletor
    $stmt_years = $pdo->prepare("SELECT DISTINCT YEAR(s.serviceDate) as ano FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID WHERE sr.UserID = :userId ORDER BY ano DESC");
    $stmt_years->execute(['userId' => $userId]);
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);
    
    // Garantir que o ano atual está na lista se não houver viagens
    if (empty($available_years)) {
        $available_years[] = date('Y');
    }

    // Calcular stats para as caixas a partir dos dados do gráfico
    $totalViagensAno = array_sum($monthly_data);
    $maxViagensMes = max($monthly_data);
    if ($maxViagensMes > 0) {
        $mesIndex = array_search($maxViagensMes, $monthly_data); // A chave é 1-12
        $mesMaisAtivo = $meses_nomes[$mesIndex - 1]; // Ajustar para array 0-11
    }

    // Preparar os dados para passar ao JavaScript (Chart.js precisa de um array 0-indexed)
    $dashboard_data_for_js = [
        'labels' => $meses_nomes,
        'data' => array_values($monthly_data), // Converte [1=>0, 2=>5,...] para [0, 5,...]
        'available_years' => $available_years,
        'selected_year' => $selectedYear
    ];

} catch (PDOException $e) {
    die("Erro ao buscar dados do dashboard: " . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Estatísticas - Painel de Condutor</title>
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
      .card-header.bg-gradient-driver {
        background: linear-gradient(90deg, #00b0ff, #7f00ff);
        color: white;
      }
      .year-selector {
            padding: 4px 8px;
            font-size: 0.9rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: white;
            color: #333;
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
                    <?php echo $_SESSION['name']; ?> - Condutor
                  </p>
                </li>
                <li class="user-footer">
                  <a href="driver.php" class="btn btn-default btn-flat">Painel</a>
                  <a href="logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
                </li>
                </ul>
            </li>
            </ul>
          </div>
        </nav>
      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Minhas Estatísticas</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="driver.php">Painel</a></li>
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
                  <div class="small-box text-bg-info">
                      <div class="inner">
                          <h3><?php echo $viagensTotal; ?></h3>
                          <p>Viagens Totais<br>(Desde Sempre)</p>
                      </div>
                      <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" ...><path ...></path></svg>
                  </div>
              </div>
              <div class="col-lg-3 col-6">
                  <div class="small-box text-bg-success">
                      <div class="inner">
                          <h3><?php echo $viagensUltimoMes; ?></h3>
                          <p>Viagens<br>(Últimos 30 dias)</p>
                      </div>
                      <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" ...><path ...></path></svg>
                  </div>
              </div>
              <div class="col-lg-3 col-6">
                  <div class="small-box text-bg-primary">
                      <div class="inner">
                          <h3><?php echo $totalViagensAno; ?></h3>
                          <p>Viagens Totais<br>(Ano: <?php echo $selectedYear; ?>)</p>
                      </div>
                      <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" ...><path ...></path></svg>
                  </div>
              </div>
              <div class="col-lg-3 col-6">
                  <div class="small-box text-bg-warning">
                      <div class="inner">
                          <h3><?php echo $mesMaisAtivo; ?></h3>
                          <p>Mês de Topo<br>(Ano: <?php echo $selectedYear; ?>)</p>
                      </div>
                      <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" ...><path ...></path></svg>
                  </div>
              </div>
            </div>
            <div class="row">
              <div class="col-12">
                <div class="card mb-4 shadow-lg border-0 rounded-4">
                    <div class="card-header text-center bg-gradient-driver rounded-top-4 d-flex justify-content-between align-items-center p-3">
                        <h3 class="card-title fw-bold mb-0" style="font-size: 1.25rem;">
                            📊 Volume de Viagens
                        </h3>
                        <select id="year-selector" class="year-selector"></select>
                    </div>
                    
                    <div class="card-body p-4" style="background: #f1f5f9;">
                        <div class="chart-container" style="background: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);">
                            <canvas id="monthlyTripsChart" style="min-height: 300px;"></canvas>
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
        SyncRide All rights reserved.
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
        // Passar os dados do PHP para uma variável JavaScript
        const dashboardData = <?php echo json_encode($dashboard_data_for_js); ?>;

        const yearSelector = document.getElementById('year-selector');
        let myChart = null; // Variável para a instância do gráfico

        function renderChart(labels, data, year) {
            const ctx = document.getElementById('monthlyTripsChart').getContext('2d');
            if (myChart) {
                myChart.destroy(); // Destruir o gráfico antigo antes de desenhar um novo
            }
            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Nº de Viagens',
                        data: data,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)', // Cor azul primária
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
                        legend: { display: false },
                        title: { 
                            display: false // O título já está no cabeçalho do card
                        }
                    }
                }
            });
        }

        // --- INICIALIZAÇÃO DO DASHBOARD ---
        
        // Popular o seletor de anos
        dashboardData.available_years.forEach(y => {
            const option = new Option(y, y);
            if (y == dashboardData.selected_year) {
                option.selected = true;
            }
            yearSelector.add(option);
        });
        
        // Adicionar o evento para recarregar a página ao mudar o ano
        yearSelector.addEventListener('change', function() {
            const newYear = this.value;
            // Constrói o URL atual e adiciona/atualiza o parâmetro 'year'
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('year', newYear);
            window.location.href = currentUrl.toString();
        });

        // Renderizar o gráfico com os dados iniciais
        renderChart(dashboardData.labels, dashboardData.data, dashboardData.selected_year);

    });
    </script>
    
  </body>
  </html>