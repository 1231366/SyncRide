<?php
session_start(); // Inicia a sessão para acessar as variáveis de sessão
require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

$viagens = [];

// Verifica se o usuário está logado e tem a role de condutor
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 2) {
    $userId = $_SESSION['user_id']; // Pega o ID do usuário logado a partir da sessão

    try {
        // Prepara a consulta para buscar os serviços associados ao usuário na tabela de relacionamento
        $stmt = $pdo->prepare("
    SELECT s.ID AS ServiceID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint
    FROM Services_Rides sr
    INNER JOIN Services s ON sr.RideID = s.ID
    WHERE sr.UserID = ?
    ORDER BY s.serviceDate ASC, s.serviceStartTime ASC
");

        $stmt->execute([$userId]);
        $viagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "Erro ao recuperar viagens: " . $e->getMessage();
    }
} else {
    // Redireciona para uma página de erro ou login, se não houver sessão ou role condutor
    header("refresh: 1; url=../../../index.php");
    exit();
}
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

$viagensHoje = 0;
$viagensSemana = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // Pega o ID do usuário logado a partir da sessão

    try {
        // Buscar o número de viagens programadas para hoje
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
        $stmt->execute([$userId]);
        $viagensHoje = $stmt->fetchColumn();

        // Buscar o número de viagens programadas para esta semana
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE YEARWEEK(serviceDate, 1) = YEARWEEK(CURDATE(), 1) AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
        $stmt->execute([$userId]);
        $viagensSemana = $stmt->fetchColumn();
    } catch (PDOException $e) {
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
require __DIR__ . '/../../../Auth/dbconfig.php'; // Inclui a configuração do banco de dados

$viagensHoje = 0;
$viagensSemana = 0;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id']; // Pega o ID do usuário logado a partir da sessão

    try {
        // Buscar o número de viagens programadas para hoje
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
        $stmt->execute([$userId]);
        $viagensHoje = $stmt->fetchColumn();

        // Buscar o número de viagens programadas para esta semana
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE YEARWEEK(serviceDate, 1) = YEARWEEK(CURDATE(), 1) AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
        $stmt->execute([$userId]);
        $viagensSemana = $stmt->fetchColumn();
    } catch (PDOException $e) {
        echo "Erro ao recuperar viagens: " . $e->getMessage();
    }
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
    <div class="small-box text-bg-primary">
        <div class="inner">
            <h3><?php echo $viagensHoje; ?></h3>
            <p>Viagens programadas <br> para hoje</p>
        </div>
        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
        </svg>
    </div>
</div>

<div class="col-lg-3 col-6">
    <div class="small-box text-bg-success">
        <div class="inner">
            <h3><?php echo $viagensSemana; ?></h3>
            <p>Viagens programadas <br> para esta semana</p>
        </div>
        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
        </svg>
    </div>
</div>
                <!--end::Small Box Widget 2-->
              </div>
              <!--end::Col-->
              
                <!--end::Small Box Widget 4-->
              </div>
              <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
<!--begin::Row-->
<div class="card mb-4 shadow-lg border-0 rounded-4">
    <div class="card-header text-center bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-top-4">
        <h3 class="card-title fw-bold mb-0" style="display: flex; justify-content: center; align-items: center; text-align: center;">
            🚖 Minhas Viagens
        </h3>
    </div>

    <!-- Botões de Filtro -->
    <div class="d-flex justify-content-center gap-3 p-3">
        <button class="btn btn-outline-light btn-sm rounded-pill px-4 filter-btn" data-filter="yesterday">⬅ Ontem</button>
        <button class="btn btn-light btn-sm rounded-pill px-4 filter-btn active" data-filter="today">📅 Hoje</button>
        <button class="btn btn-outline-light btn-sm rounded-pill px-4 filter-btn" data-filter="tomorrow">➡ Amanhã</button>
    </div>

    <div class="card-body p-4">
        <div class="list-group">
            <?php if (empty($viagens)): ?>
                <p class="text-center text-muted">Nenhuma viagem encontrada.</p>
            <?php else: ?>
                <?php foreach ($viagens as $viagem): 
                    // Extrai a hora da viagem
                    $hora = intval(explode(":", $viagem["serviceStartTime"])[0]);

                    // Define a cor da borda com base na hora
                    $borderColor = "#007bff"; // Azul padrão
                    if ($hora >= 12 && $hora < 18) $borderColor = "#28a745"; // Verde para tarde
                    if ($hora >= 18) $borderColor = "#ffc107"; // Amarelo para noite
                ?>
                    <div class="list-group-item p-2 rounded-3 mb-3 travel-card"
                         style="background: #ffffff; border-left: 6px solid <?= $borderColor ?>; 
                                box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s, box-shadow 0.3s;">
                        <h5 class="fw-bold text-primary mb-1" style="font-size: 14px;">🕒 <?= substr($viagem["serviceStartTime"], 0, 5) ?></h5>
                        <p class="mb-1" style="font-size: 12px;"><strong>Rota:</strong> <?= htmlspecialchars($viagem["serviceStartPoint"]) ?> → <?= htmlspecialchars($viagem["serviceTargetPoint"]) ?></p>
                        <div class="text-center mt-2">
                            <button class="btn btn-outline-success btn-sm open-modal"
                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                    data-id="<?= $viagem["ServiceID"] ?>"
                                    data-start="<?= htmlspecialchars($viagem["serviceStartPoint"]) ?>"
                                    data-end="<?= htmlspecialchars($viagem["serviceTargetPoint"]) ?>"
                                    data-time="<?= substr($viagem["serviceStartTime"], 0, 5) ?>"
                                    data-date="<?= $viagem["serviceDate"] ?>"> <!-- Agora tem o serviceDate -->
                                ℹ️ Detalhes
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detailsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da Viagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group">
          <li class="list-group-item"><strong>📅 Data:</strong> <span id="modalDate"></span></li>
          <li class="list-group-item"><strong>🕒 Hora:</strong> <span id="modalTime"></span></li>
          <li class="list-group-item"><strong>📍 Local de Recolha:</strong> <span id="modalPickup"></span></li>
          <li class="list-group-item"><strong>🚗 Local de Entrega:</strong> <span id="modalDropoff"></span></li>
          <li class="list-group-item"><strong>👥 Passageiros:</strong> <span id="modalPassengers"></span></li>
        </ul>

        <button class="btn btn-danger w-100 mt-3" id="uploadNoShow">📷 Upload No Show Report</button>

        <!-- Área de captura de foto -->
        <div id="cameraContainer" class="mt-3" style="display: none; text-align: center;">
          <video id="cameraStream" width="100%" autoplay></video>
          <button class="btn btn-primary mt-2" id="capturePhoto">📸 Capturar Foto</button>
          <canvas id="photoCanvas" style="display: none;"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    // Função para formatar a data em YYYY-MM-DD
    function formatDate(date) {
      return date.toISOString().split('T')[0];
    }

    // Carregar todas as viagens a partir do PHP
    const viagens = <?php echo json_encode($viagens); ?>;

    // Função para filtrar as viagens com base na data
    function filterTrips(dateFilter) {
      const today = new Date();
      const yesterday = new Date(today);
      const tomorrow = new Date(today);

      yesterday.setDate(today.getDate() - 1);
      tomorrow.setDate(today.getDate() + 1);

      const todayFormatted = formatDate(today);
      const yesterdayFormatted = formatDate(yesterday);
      const tomorrowFormatted = formatDate(tomorrow);

      // Filtrar as viagens com base no filtro
      let filteredTrips = [];
      if (dateFilter === "yesterday") {
        filteredTrips = viagens.filter(viagem => viagem.serviceDate === yesterdayFormatted);
      } else if (dateFilter === "today") {
        filteredTrips = viagens.filter(viagem => viagem.serviceDate === todayFormatted);
      } else if (dateFilter === "tomorrow") {
        filteredTrips = viagens.filter(viagem => viagem.serviceDate === tomorrowFormatted);
      }

      // Atualizar a lista de viagens no DOM
      updateTripsDisplay(filteredTrips);
    }

    // Função para atualizar o conteúdo das viagens no DOM
    function updateTripsDisplay(filteredTrips) {
      const listGroup = document.querySelector(".list-group");
      listGroup.innerHTML = ""; // Limpar a lista de viagens atual

      if (filteredTrips.length === 0) {
        listGroup.innerHTML = "<p class='text-center text-muted'>Nenhuma viagem encontrada.</p>";
      } else {
        filteredTrips.forEach(viagem => {
          // Extrair hora da viagem
          const hora = parseInt(viagem.serviceStartTime.split(":")[0]);

          // Definir a cor da borda com base na hora
borderColor = "#28a745"; // Verde para tarde

          // Criar o HTML para cada viagem
          const viagemHtml = `
            <div class="list-group-item p-2 rounded-3 mb-3 travel-card"
                 style="background: #ffffff; border-left: 6px solid ${borderColor}; 
                        box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s, box-shadow 0.3s;">
              <h5 class="fw-bold text-primary mb-1" style="font-size: 14px;">🕒 ${viagem.serviceStartTime.substr(0, 5)}</h5>
              <p class="mb-1" style="font-size: 12px;"><strong>Rota:</strong> ${viagem.serviceStartPoint} → ${viagem.serviceTargetPoint}</p>
              <div class="text-center mt-2">
                <button class="btn btn-outline-success btn-sm open-modal"
                        data-bs-toggle="modal" data-bs-target="#detailsModal"
                        data-id="${viagem.ServiceID}"
                        data-start="${viagem.serviceStartPoint}"
                        data-end="${viagem.serviceTargetPoint}"
                        data-time="${viagem.serviceStartTime.substr(0, 5)}"
                        data-date="${viagem.serviceDate}"
                        data-passengers="${viagem.passengers || 'N/A'}">
                  ℹ️ Detalhes
                </button>
              </div>
            </div>
          `;

          listGroup.innerHTML += viagemHtml;
        });
      }
    }

    // Inicializar com as viagens de hoje
    filterTrips("today");

    // Event listener para os botões de filtro
    document.querySelectorAll(".filter-btn").forEach(button => {
      button.addEventListener("click", function () {
        // Remover classe 'active' de todos os botões
        document.querySelectorAll(".filter-btn").forEach(btn => btn.classList.remove("active"));
        // Adicionar classe 'active' ao botão clicado
        this.classList.add("active");

        // Filtrar viagens de acordo com o filtro selecionado
        filterTrips(this.getAttribute("data-filter"));
      });
    });

    // Abrir o modal e preencher os dados
    document.querySelectorAll(".open-modal").forEach(button => {
      button.addEventListener("click", function () {
        // Pegar os dados do botão
        const id = this.getAttribute("data-id");
        const start = this.getAttribute("data-start");
        const end = this.getAttribute("data-end");
        const time = this.getAttribute("data-time");
        const date = this.getAttribute("data-date");
        const passengers = this.getAttribute("data-passengers");

        // Preencher os campos do modal
        document.getElementById("modalDate").textContent = date;
        document.getElementById("modalTime").textContent = time;
        document.getElementById("modalPickup").textContent = start;
        document.getElementById("modalDropoff").textContent = end;
        document.getElementById("modalPassengers").textContent = passengers;
      });
    });
  });
</script>



<!-- Animations & Interactive Styles -->
<style>
  .filter-btn {
    transition: all 0.3s ease;
    background-color: transparent;
    border: 2px solid #ffffff;
    color: #ffffff;
  }

  .filter-btn:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  }

  .filter-btn.active {
    background: linear-gradient(90deg, #00b0ff, #7f00ff);
    color: white;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  .filter-btn:not(.active) {
    background: transparent;
    color: #b0b0b0;
  }

  .travel-card:hover {
    transform: scale(1.03);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
  }

  .travel-card:active {
    transform: scale(1.01);
  }

  .card-header {
    background: linear-gradient(90deg, #00b0ff, #7f00ff);
    border-radius: 12px 12px 0 0;
    text-align: center;
  }

  .card-body {
    background: #f1f5f9;
    border-radius: 0 0 12px 12px;
  }

  .btn-outline-primary, .btn-outline-success, .btn-outline-warning {
    border-radius: 30px;
    transition: background-color 0.3s ease;
  }

  .btn-outline-primary:hover {
    background-color: #007bff;
    color: #fff;
  }

  .btn-outline-success:hover {
    background-color: #28a745;
    color: #fff;
  }

  .btn-outline-warning:hover {
    background-color: #ffc107;
    color: #fff;
  }
</style>

<!-- JavaScript to Handle Active Button State -->
<script>
  const filterButtons = document.querySelectorAll('.filter-btn');
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      filterButtons.forEach(btn => btn.classList.remove('active'));
      this.classList.add('active');
    });
  });
</script>



            
            
              
              
              
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
