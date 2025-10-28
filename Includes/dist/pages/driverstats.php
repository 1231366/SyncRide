<?php
session_start(); // Inicia a sessão para acessar as variáveis de sessão
require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

$viagens = [];
$serviceTypeFilter = isset($_GET['serviceType']) ? $_GET['serviceType'] : null; // Obtém o tipo de serviço, se presente

// Verifica se o usuário está logado e tem a role de condutor
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 2) {
    $userId = $_SESSION['user_id']; // Pega o ID do usuário logado a partir da sessão

    try {
        // Prepara a consulta para buscar os serviços associados ao usuário na tabela de relacionamento
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
            s.serviceType  -- Adicionando o tipo de serviço
        FROM Services_Rides sr
        INNER JOIN Services s ON sr.RideID = s.ID
        WHERE sr.UserID = ?";

        // Se houver um filtro pelo tipo de serviço, aplicamos ele
        if ($serviceTypeFilter !== null) {
            $query .= " AND s.serviceType = ?";
        }

        $query .= " ORDER BY s.serviceDate ASC, s.serviceStartTime ASC";

        $stmt = $pdo->prepare($query);

        // Se o filtro estiver presente, passamos ele para a execução da consulta
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
    // Redireciona para uma página de erro ou login, se não houver sessão ou role condutor
    header("refresh: 1; url=../../../index.php");
    exit();
}

// Passar as viagens para o JavaScript
echo "<script> 
    var viagens = " . json_encode($viagens) . ";
</script>";
?>

<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Painel de Condutor</title>
    <!--begin::Primary Meta Tags-->
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="title" content="AdminLTE v4 | Dashboard" />
    <meta name="author" content="ColorlibHQ" />
    <meta
      name="description"
      content="AdminLTE is a Free Bootstrap 5 Admin Dashboard, 30 example pages using Vanilla JS."
    />
    <meta
      name="keywords"
      content="bootstrap 5, bootstrap, bootstrap 5 admin dashboard, bootstrap 5 dashboard, bootstrap 5 charts, bootstrap 5 calendar, bootstrap 5 datepicker, bootstrap 5 tables, bootstrap 5 datatable, vanilla js datatable, colorlibhq, colorlibhq dashboard, colorlibhq admin dashboard"
    />
    <!--end::Primary Meta Tags-->
    <!--begin::Fonts-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css"
      integrity="sha256-tXJfXfp6Ewt1ilPzLDtQnJV4hclT9XuaZUKyUvmyr+Q="
      crossorigin="anonymous"
    />
    <!--end::Fonts-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/styles/overlayscrollbars.min.css"
      integrity="sha256-tZHrRjVqNSRyWg2wbppGnT833E/Ys0DHWGwT04GiqQg="
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(OverlayScrollbars)-->
    <!--begin::Third Party Plugin(Bootstrap Icons)-->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
      integrity="sha256-9kPW/n5nn53j4WMRYAxe9c1rCY96Oogo/MKSVdKzPmI="
      crossorigin="anonymous"
    />
    <!--end::Third Party Plugin(Bootstrap Icons)-->
    <!--begin::Required Plugin(AdminLTE)-->
    <link rel="stylesheet" href="../../dist/css/adminlte.css" />
    <!--end::Required Plugin(AdminLTE)-->
    <!-- apexcharts -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />
    <!-- jsvectormap -->
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <!--begin::App Wrapper-->
    <div class="app-wrapper">
      <!--begin::Header-->
      <nav class="app-header navbar navbar-expand bg-body">
        <!--begin::Container-->
        <div class="container-fluid">
          <!--begin::Start Navbar Links-->
          <ul class="navbar-nav">
            <li class="nav-item">
              <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                <i class="bi bi-list"></i>
              </a>
            </li>
            <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Home</a></li>
            <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Contact</a></li>
          </ul>
          <!--end::Start Navbar Links-->
          <!--begin::End Navbar Links-->
          <ul class="navbar-nav ms-auto">
            <!--begin::Navbar Search-->
            <li class="nav-item">
              <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="bi bi-search"></i>
              </a>
            </li>
            <!--end::Navbar Search-->
            <!--end::Notifications Dropdown Menu-->
            <!--begin::Fullscreen Toggle-->
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
              </a>
            </li>
            <!--end::Fullscreen Toggle-->
            <!--begin::User Menu Dropdown-->
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
                <!--begin::User Image-->
                <li class="user-header text-bg-primary">
                  <img
                    src="../../dist/assets/img/user2-160x160.jpg"
                    class="rounded-circle shadow"
                    alt="User Image"
                  />
                  <p>
        
        <!-- Mostrar o nome do utilizador e o tipo de utilizador (role) -->
        <?php 
          require_once __DIR__ . '/../../../Auth/dbconfig.php';
        session_start();
        
        echo $_SESSION['name']; ?> - <?php echo $_SESSION['role'] == 1 ? 'Admin' : 'Condutor'; 
        
        ?>
      </p>
                </li>
                <!--end::User Image-->
                <!--end::Menu Body-->
                <!--begin::Menu Footer-->
                <li class="user-footer">
      <a href="profile.html" class="btn btn-default btn-flat">Profile</a>
      <a href="logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
    </li>
                <!--end::Menu Footer-->
              </ul>
            </li>
            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
      <?php
require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

// 1. GARANTIA: As variáveis começam com o valor 0.
// Se a consulta falhar ou o utilizador não estiver logado, este é o valor que será mostrado.
$viagensTotal = 0;
$viagensUltimoMes = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    try {
        // 2. GARANTIA: A consulta com COUNT(*) sempre retorna um número.
        // Se não encontrar viagens, retorna 0.
        $sqlTotal = "SELECT COUNT(*)
                     FROM Services s
                     JOIN Services_Rides sr ON s.ID = sr.RideID
                     WHERE sr.UserID = ? AND s.serviceDate <= CURDATE()";
        $stmt = $pdo->prepare($sqlTotal);
        $stmt->execute([$userId]);
        $viagensTotal = $stmt->fetchColumn(); // Se a contagem for 0, esta variável será 0.

        // O mesmo se aplica aqui para as viagens do último mês.
        $sqlMes = "SELECT COUNT(*)
                   FROM Services s
                   JOIN Services_Rides sr ON s.ID = sr.RideID
                   WHERE sr.UserID = ? AND s.serviceDate BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()";
        $stmt = $pdo->prepare($sqlMes);
        $stmt->execute([$userId]);
        $viagensUltimoMes = $stmt->fetchColumn(); // Se a contagem for 0, esta variável será 0.

    } catch (PDOException $e) {
        // Mesmo que ocorra um erro na base de dados, as variáveis mantêm o valor 0.
        echo "Erro ao recuperar viagens: " . $e->getMessage();
    }
}
?>
      <!--begin::App Main-->
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Painel de Condutor</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
              </div>
            </div>
            <!--end::Row-->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content Header-->
        <?php
// =================================================================
// 1. INÍCIO DO BLOCO PHP - BUSCAR TODOS OS DADOS
// =================================================================
session_start();
require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

// Proteger a página e obter o ID do utilizador
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Altere para a sua página de login
    exit();
}
$userId = $_SESSION['user_id'];

// --- DADOS PARA AS "SMALL BOXES" ---
$viagensTotal = 0;
$viagensUltimoMes = 0;
try {
    // Total de viagens feitas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = ? AND s.serviceDate <= CURDATE()");
    $stmt->execute([$userId]);
    $viagensTotal = $stmt->fetchColumn();

    // Viagens no último mês
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = ? AND s.serviceDate BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE()");
    $stmt->execute([$userId]);
    $viagensUltimoMes = $stmt->fetchColumn();
} catch (PDOException $e) {
    die("Erro ao buscar stats das boxes: " . $e->getMessage());
}


// --- DADOS PARA O DASHBOARD (GRÁFICO E CARDS) ---
$dashboard_data_for_js = [];
try {
    // Obter o ano a partir do URL (?year=...). Se não existir, usa o ano atual.
    $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

    // Inicializar os dados de todos os meses a zero
    $monthly_data = array_fill(0, 12, 0);
    $meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

    // Buscar dados mensais para o ano selecionado
    $sql = "SELECT MONTH(s.serviceDate) AS mes, COUNT(sr.associationID) AS total
            FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID
            WHERE sr.UserID = :userId AND YEAR(s.serviceDate) = :year AND s.serviceDate <= CURDATE()
            GROUP BY mes";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['userId' => $userId, 'year' => $selectedYear]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($results as $row) {
        $monthly_data[$row['mes'] - 1] = (int)$row['total'];
    }

    // Buscar todos os anos disponíveis para o seletor
    $stmt_years = $pdo->prepare("SELECT DISTINCT YEAR(s.serviceDate) as ano FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID WHERE sr.UserID = :userId ORDER BY ano DESC");
    $stmt_years->execute(['userId' => $userId]);
    $available_years = $stmt_years->fetchAll(PDO::FETCH_COLUMN);

    // Preparar os dados para passar ao JavaScript
    $dashboard_data_for_js = [
        'labels' => $meses_nomes,
        'data' => $monthly_data,
        'available_years' => $available_years,
        'selected_year' => $selectedYear
    ];

} catch (PDOException $e) {
    die("Erro ao buscar dados do dashboard: " . $e->getMessage());
}


// --- DADOS PARA A LISTA "MINHAS VIAGENS" ---
// !! NOTA: Esta consulta está pré-definida para hoje. Terá de a tornar dinâmica com JavaScript
// para que os botões "Ontem" e "Amanhã" funcionem.
$viagens = [];
try {
    $stmt_viagens = $pdo->prepare("
        SELECT s.ID as ServiceID, s.* FROM Services s
        JOIN Services_Rides sr ON s.ID = sr.RideID
        WHERE sr.UserID = ? AND s.serviceDate = CURDATE()
        ORDER BY s.serviceStartTime ASC
    ");
    $stmt_viagens->execute([$userId]);
    $viagens = $stmt_viagens->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erro ao buscar a lista de viagens: " . $e->getMessage());
}
?>
        <!--begin::App Content-->
        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <!--begin::Col-->
             <div class="col-lg-3 col-6">
                <!--end::Small Box Widget 2-->
              </div>
              <!--end::Col-->
              
                <!--end::Small Box Widget 4-->
              </div>
              <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
<!--begin::Row--><!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Painel de Condutor</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Estilos para o novo Dashboard */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f4f7f9; /* Um fundo suave para a página */
        }
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .year-selector {
            padding: 8px 12px;
            font-size: 16px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background-color: white;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .card.stat-card { /* Adicionada classe stat-card para evitar conflitos */
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            text-align: center;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #7f8c8d;
            font-size: 16px;
        }
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
        }
        .chart-container {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
    </style>
</head>
<body>

<div class="app-content">
  <div class="container-fluid">
    
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-info">
                <div class="inner">
                    <h3><?php echo $viagensTotal; ?></h3>
                    <p>Viagens feitas <br> desde sempre</p>
                </div>
                </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3><?php echo $viagensUltimoMes; ?></h3>
                    <p>Viagens feitas <br> no último mês</p>
                </div>
                </div>
        </div>
    </div>
    <div class="card mb-4 shadow-lg border-0 rounded-4">
        <div class="card-body p-4">
            <div class="dashboard-header">
                <h1 style="font-size: 1.5rem; color: #2c3e50;">📊 Dashboard de Atividade</h1>
                <select id="year-selector" class="year-selector"></select>
            </div>

            <div class="stats-cards">
                <div class="card stat-card">
                    <h3>Total de Viagens em <span class="selected-year-text"></span></h3>
                    <p id="total-year-trips" class="value">0</p>
                </div>
                <div class="card stat-card">
                    <h3>Mês de Maior Atividade</h3>
                    <p id="busiest-month" class="value">-</p>
                </div>
            </div>

            <div class="chart-container">
                <canvas id="monthlyTripsChart"></canvas>
            </div>
        </div>
    </div>
    
    

  </div>
  </div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Passar os dados do PHP para uma variável JavaScript
    const dashboardData = <?php echo json_encode($dashboard_data_for_js); ?>;

    const yearSelector = document.getElementById('year-selector');
    let myChart = null; // Variável para a instância do gráfico

    // --- FUNÇÕES AUXILIARES ---
    function updateSummaryCards(labels, data, year) {
        const totalTripsElement = document.getElementById('total-year-trips');
        const busiestMonthElement = document.getElementById('busiest-month');
        document.querySelectorAll('.selected-year-text').forEach(el => el.textContent = year);
        
        const totalTrips = data.reduce((sum, value) => sum + value, 0);
        totalTripsElement.textContent = totalTrips;

        if (totalTrips > 0) {
            const maxValue = Math.max(...data);
            const busiestMonthIndex = data.indexOf(maxValue);
            busiestMonthElement.textContent = labels[busiestMonthIndex];
        } else {
            busiestMonthElement.textContent = '-';
        }
    }

    function renderChart(labels, data) {
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
                    backgroundColor: 'rgba(52, 152, 219, 0.7)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                },
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: `Viagens Mensais em ${dashboardData.selected_year}`, font: { size: 16 } }
                }
            }
        });
    }

    // --- INICIALIZAÇÃO DO DASHBOARD ---
    
    // Popular o seletor de anos
    dashboardData.available_years.forEach(y => {
        const option = new Option(y, y);
        yearSelector.add(option);
    });
    // Se não houver anos disponíveis, adiciona o ano atual como opção
    if(dashboardData.available_years.length === 0) {
        const currentYear = new Date().getFullYear();
        yearSelector.add(new Option(currentYear, currentYear));
    }
    yearSelector.value = dashboardData.selected_year;

    // Adicionar o evento para recarregar a página ao mudar o ano
    yearSelector.addEventListener('change', function() {
        const newYear = this.value;
        // A linha abaixo constrói o URL atual e adiciona/atualiza o parâmetro 'year'
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('year', newYear);
        window.location.href = currentUrl.toString();
    });

    // Renderizar tudo com os dados iniciais
    updateSummaryCards(dashboardData.labels, dashboardData.data, dashboardData.selected_year);
    renderChart(dashboardData.labels, dashboardData.data);

});
</script>

</body>
</html>


            
            
              
              
              
              <!-- Incluir o CSS do Dropzone -->
              <link href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" rel="stylesheet">
              
              <!-- Incluir o JS do Dropzone -->
              <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>
              
              <script>
               // Desativar a auto-descoberta do Dropzone
                  Dropzone.autoDiscover = false;

                  // Inicializar o Dropzone para enviar o arquivo para uploadxlm.php
                  let myDropzone = new Dropzone("#file-upload", {
                    url: "uploadxlm.php", // Enviar para o script PHP
                    paramName: "fileUpload", // Nome do parâmetro do arquivo
                    maxFilesize: 10, // Tamanho máximo do arquivo (em MB)
                    maxFiles: 1, // Limitar o número de arquivos para 1
                    acceptedFiles: ".xml", // Tipos de arquivo aceitos (apenas XML)
                    autoProcessQueue: false, // Desativar upload automático
                    dictDefaultMessage: "Arraste e solte o ficheiro aqui ou clique para selecionar",
                    dictInvalidFileType: "Apenas ficheiros XML são permitidos!",
                    dictFileTooBig: "O ficheiro é muito grande. Tamanho máximo permitido: 10MB",
                    dictMaxFilesExceeded: "Você só pode enviar um ficheiro por vez.",
                  });

                  // Evento para processar a fila quando o botão for pressionado
                  document.getElementById("upload-btn").addEventListener("click", function() {
                    if (myDropzone.files.length > 0) {
                      myDropzone.processQueue(); // Enviar o ficheiro para o uploadxlm.php
                    } else {
                      alert("Por favor, selecione um ficheiro antes de confirmar o upload.");
                    }
                  });

                  // Evento quando o arquivo for enviado com sucesso
                  myDropzone.on("complete", function(file) {
                    if (myDropzone.getQueuedFiles().length === 0) {
                      // Redireciona para a mesma página com o status de sucesso
                      window.location.href = "index.php?status=success";
                    }
                  });

                  // Evento quando ocorre um erro no upload
                  myDropzone.on("error", function(file, errorMessage) {
                    // Redireciona para a mesma página com o status de erro
                    window.location.href = "index.php?status=error";
                  });


              </script>
              
              
              
              
              
              
              
              <!-- /.Start col -->
               
            </div>
            <!-- /.row (main row) -->
          </div>
          <!--end::Container-->
        </div>
        <!--end::App Content-->
      </main>
      <!--end::App Main-->
      <!--begin::Footer-->
      <footer class="app-footer">
        <!--begin::To the end-->
        <div class="float-end d-none d-sm-inline"></div>
        <!--end::To the end-->
        <!--begin::Copyright-->
        SyncRide All rights reserved.
        <!--end::Copyright-->
      </footer>
      <!--end::Footer-->
    </div>
    <!--end::App Wrapper-->
    <!--begin::Script-->
    <!--begin::Third Party Plugin(OverlayScrollbars)-->
    <script
      src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
      integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
      crossorigin="anonymous"
    ></script>
    <!--end::Third Party Plugin(OverlayScrollbars)--><!--begin::Required Plugin(popperjs for Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(popperjs for Bootstrap 5)--><!--begin::Required Plugin(Bootstrap 5)-->
    <script
      src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
      integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
      crossorigin="anonymous"
    ></script>
    <!--end::Required Plugin(Bootstrap 5)--><!--begin::Required Plugin(AdminLTE)-->
    <script src="../../dist/js/adminlte.js"></script>
    <!--end::Required Plugin(AdminLTE)--><!--begin::OverlayScrollbars Configure-->
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
    <!--end::OverlayScrollbars Configure-->
    <!-- OPTIONAL SCRIPTS -->
    <!-- sortablejs -->
    <script
      src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"
      integrity="sha256-ipiJrswvAR4VAx/th+6zWsdeYmVae0iJuiR+6OqHJHQ="
      crossorigin="anonymous"
    ></script>
    <!-- sortablejs -->
    <script>
      const connectedSortables = document.querySelectorAll('.connectedSortable');
      connectedSortables.forEach((connectedSortable) => {
        let sortable = new Sortable(connectedSortable, {
          group: 'shared',
          handle: '.card-header',
        });
      });

      const cardHeaders = document.querySelectorAll('.connectedSortable .card-header');
      cardHeaders.forEach((cardHeader) => {
        cardHeader.style.cursor = 'move';
      });
    </script>
    <!-- apexcharts -->
    <script
      src="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.min.js"
      integrity="sha256-+vh8GkaU7C9/wbSLIcwq82tQ2wTf44aOHA8HlBMwRI8="
      crossorigin="anonymous"
    ></script>
    <!-- ChartJS -->
    <script>
      // NOTICE!! DO NOT USE ANY OF THIS JAVASCRIPT
      // IT'S ALL JUST JUNK FOR DEMO
      // ++++++++++++++++++++++++++++++++++++++++++

      const sales_chart_options = {
        series: [
          {
            name: 'Digital Goods',
            data: [28, 48, 40, 19, 86, 27, 90],
          },
          {
            name: 'Electronics',
            data: [65, 59, 80, 81, 56, 55, 40],
          },
        ],
        chart: {
          height: 300,
          type: 'area',
          toolbar: {
            show: false,
          },
        },
        legend: {
          show: false,
        },
        colors: ['#0d6efd', '#20c997'],
        dataLabels: {
          enabled: false,
        },
        stroke: {
          curve: 'smooth',
        },
        xaxis: {
          type: 'datetime',
          categories: [
            '2023-01-01',
            '2023-02-01',
            '2023-03-01',
            '2023-04-01',
            '2023-05-01',
            '2023-06-01',
            '2023-07-01',
          ],
        },
        tooltip: {
          x: {
            format: 'MMMM yyyy',
          },
        },
      };

      const sales_chart = new ApexCharts(
        document.querySelector('#revenue-chart'),
        sales_chart_options,
      );
      sales_chart.render();
    </script>
    <!-- jsvectormap -->
    <script
      src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"
      integrity="sha256-/t1nN2956BT869E6H4V1dnt0X5pAQHPytli+1nTZm2Y="
      crossorigin="anonymous"
    ></script>
    <script
      src="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/maps/world.js"
      integrity="sha256-XPpPaZlU8S/HWf7FZLAncLg2SAkP8ScUTII89x9D3lY="
      crossorigin="anonymous"
    ></script>
    <!-- jsvectormap -->
    <script>
      const visitorsData = {
        US: 398, // USA
        SA: 400, // Saudi Arabia
        CA: 1000, // Canada
        DE: 500, // Germany
        FR: 760, // France
        CN: 300, // China
        AU: 700, // Australia
        BR: 600, // Brazil
        IN: 800, // India
        GB: 320, // Great Britain
        RU: 3000, // Russia
      };

      // World map by jsVectorMap
      const map = new jsVectorMap({
        selector: '#world-map',
        map: 'world',
      });

      // Sparkline charts
      const option_sparkline1 = {
        series: [
          {
            data: [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021],
          },
        ],
        chart: {
          type: 'area',
          height: 50,
          sparkline: {
            enabled: true,
          },
        },
        stroke: {
          curve: 'straight',
        },
        fill: {
          opacity: 0.3,
        },
        yaxis: {
          min: 0,
        },
        colors: ['#DCE6EC'],
      };

      const sparkline1 = new ApexCharts(document.querySelector('#sparkline-1'), option_sparkline1);
      sparkline1.render();

      const option_sparkline2 = {
        series: [
          {
            data: [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921],
          },
        ],
        chart: {
          type: 'area',
          height: 50,
          sparkline: {
            enabled: true,
          },
        },
        stroke: {
          curve: 'straight',
        },
        fill: {
          opacity: 0.3,
        },
        yaxis: {
          min: 0,
        },
        colors: ['#DCE6EC'],
      };

      const sparkline2 = new ApexCharts(document.querySelector('#sparkline-2'), option_sparkline2);
      sparkline2.render();

      const option_sparkline3 = {
        series: [
          {
            data: [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21],
          },
        ],
        chart: {
          type: 'area',
          height: 50,
          sparkline: {
            enabled: true,
          },
        },
        stroke: {
          curve: 'straight',
        },
        fill: {
          opacity: 0.3,
        },
        yaxis: {
          min: 0,
        },
        colors: ['#DCE6EC'],
      };

      const sparkline3 = new ApexCharts(document.querySelector('#sparkline-3'), option_sparkline3);
      sparkline3.render();
    </script>
    <!--end::Script-->
  </body>
  <!--end::Body-->
</html>
