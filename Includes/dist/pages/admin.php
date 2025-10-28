<?php
session_start(); // Inicia a sessão para acessar as variáveis de sessão

// Verifica se o usuário está logado e tem a role de admin
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 1) {
    // O código para exibir o conteúdo da página vai aqui
    // Exemplo: pode colocar a lógica para mostrar o conteúdo que só admin pode acessar.
} else {
    // Redireciona para a página de login ou outra página de erro
    header("refresh: 1; url=../../../index.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
  <!--begin::Head-->
  <head>
  <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/js/adminlte.min.js"></script><script src="https://cdn.jsdelivr.net/npm/admin-lte@3.1.0/dist/js/adminlte.min.js"></script>



    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Painel de Administrador</title>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
</head>
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
    <!-- Mostrar o nome do utilizador logado -->
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

        
        echo $_SESSION['name']; ?> - <?php echo $_SESSION['role'] == 1 ? 'Admin' : 'Driver'; 
        
        ?>
      </p>
    </li>
    <!--end::User Image-->
    <!--begin::Menu Footer-->
    <li class="user-footer">
      <a href="profile.html" class="btn btn-default btn-flat">Profile</a>
      <a href="logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
    </li>
    <!--end::Menu Footer-->
  </ul>
</li>
<!--end::User Menu Dropdown-->

            <!--end::User Menu Dropdown-->
          </ul>
          <!--end::End Navbar Links-->
        </div>
        <!--end::Container-->
      </nav>
      <!--end::Header-->
      <!--begin::Sidebar-->
      <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <!--begin::Sidebar Brand-->
        <div class="sidebar-brand">
          <!--begin::Brand Link-->
          <a href="./admin.php" class="brand-link">
            <!--begin::Brand Image-->
            <img
              src="../../dist/assets/img/AdminLTELogo.png"
              alt="AdminLTE Logo"
              class="brand-image opacity-75 shadow"
            />
            <!--end::Brand Image-->
            <!--begin::Brand Text-->
            <span class="brand-text fw-light">SyncRide</span>
            <!--end::Brand Text-->
          </a>
          <!--end::Brand Link-->
        </div>
        <!--end::Sidebar Brand-->
        <!--begin::Sidebar Wrapper-->
        <div class="sidebar-wrapper">
          <nav class="mt-2">
            <!--begin::Sidebar Menu-->
            <ul
              class="nav sidebar-menu flex-column"
              data-lte-toggle="treeview"
              role="menu"
              data-accordion="false"
            >
            <li class="nav-item menu-open">
                <a href="admin.php" class="nav-link active">
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
                      <i class="nav-icon bi bi-box-seam-fill"></i>
                      <p>Gerir Funcionários</p>
                  </a>
              </li>

              
              <li class="nav-item">
                  <a href="manageStorage.php" class="nav-link">
                      <i class="nav-icon bi bi-box-seam-fill"></i>
                      <p>Gerir Armazenamento</p>
                  </a>
              </li>


            <!--end::Sidebar Menu-->
          </nav>
        </div>
        <!--end::Sidebar Wrapper-->
      </aside>
      <!--end::Sidebar-->
      <!--begin::App Main-->
      
      <main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Painel de Administrador</h3></div>
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
        <!--begin::App Content-->
        <?php
require __DIR__ . '/../../../Auth/dbconfig.php';

// Todas as viagens (desde sempre)
$sqlTodasAsViagens = "
    SELECT s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.serviceStartPoint, s.serviceTargetPoint,
           u.name AS driverName
    FROM Services s
    LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
    LEFT JOIN Users u ON sr.UserID = u.ID
";

// Viagens desta semana
$sqlViagensSemana = "
    SELECT s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.serviceStartPoint, s.serviceTargetPoint,
           u.name AS driverName
    FROM Services s
    LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
    LEFT JOIN Users u ON sr.UserID = u.ID
    WHERE WEEK(s.serviceDate, 1) = WEEK(CURDATE(), 1)
      AND YEAR(s.serviceDate) = YEAR(CURDATE())
";

// Viagens de hoje
$sqlViagensHoje = "
    SELECT s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.serviceStartPoint, s.serviceTargetPoint,
           u.name AS driverName
    FROM Services s
    LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
    LEFT JOIN Users u ON sr.UserID = u.ID
    WHERE s.serviceDate = CURDATE()
";
// Viagens da semana que já foram atribuídas (i.e., com UserID associado)
$sqlViagensSemanaConcluidas = "
    SELECT COUNT(*) AS totalConcluidas
    FROM Services s
    INNER JOIN Services_Rides sr ON s.ID = sr.RideID
    WHERE WEEK(s.serviceDate, 1) = WEEK(CURDATE(), 1)
      AND YEAR(s.serviceDate) = YEAR(CURDATE())
";



// Executar e contar

// Todas
$stmt = $pdo->prepare($sqlTodasAsViagens);
$stmt->execute();
$todasAsViagens = $stmt->fetchAll();
$totalTodasAsViagens = count($todasAsViagens);

// Semana
$stmt = $pdo->prepare($sqlViagensSemana);
$stmt->execute();
$viagensSemana = $stmt->fetchAll();
$totalViagensSemana = count($viagensSemana);

// Hoje
$stmt = $pdo->prepare($sqlViagensHoje);
$stmt->execute();
$viagensHoje = $stmt->fetchAll();
$totalViagensHoje = count($viagensHoje);

$stmt = $pdo->prepare($sqlViagensSemanaConcluidas);
$stmt->execute();
$resultado = $stmt->fetch();
$totalSemanaConcluidas = $resultado['totalConcluidas'] ?? 0;

// Calcular percentagem (evitar divisão por zero)
$percentagemSemanaConcluida = $totalViagensSemana > 0
    ? round(($totalSemanaConcluidas / $totalViagensSemana) * 100, 2)
    : 0;

// Calcular viagens por dia da última semana (segunda-feira a domingo)
$sqlViagensPorDiaSemana = "
    SELECT DAYOFWEEK(s.serviceDate) AS diaSemana, COUNT(*) AS totalViagens
    FROM Services s
    LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
    WHERE s.serviceDate BETWEEN CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) - 2) DAY 
                              AND CURDATE() - INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY
    GROUP BY diaSemana
    ORDER BY diaSemana
";

// Executar a consulta para obter o total de viagens por dia da semana
$stmt = $pdo->prepare($sqlViagensPorDiaSemana);
$stmt->execute();
$viagensPorDiaSemana = $stmt->fetchAll();

// Organizar os resultados para garantir que todos os dias da semana apareçam, mesmo que sem viagens
$viagensPorDia = array_fill(1, 7, 0); // Inicializa todos os dias com 0

foreach ($viagensPorDiaSemana as $viagem) {
    $viagensPorDia[$viagem['diaSemana']] = $viagem['totalViagens'];
}

// Formatar os dados para o gráfico
$viagensDia = implode(",", $viagensPorDia);

// Variação de datas para a última semana
$categorias = [];
for ($i = 6; $i >= 0; $i--) {
    $categorias[] = date('D', strtotime("-$i days"));
}

$categoriasStr = implode("','", $categorias);
?>

        <div class="app-content">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <!--begin::Col-->
              <div class="col-lg-3 col-6">
                <!--begin::Small Box Widget 1-->
                <div class="small-box text-bg-primary">
                  <div class="inner">
                    <h3><?= $totalTodasAsViagens ?></h3>
                    <p>Viagens concluídas</p>
                  </div>
                  <svg
                    class="small-box-icon"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"
                    ></path>
                  </svg>
                </div>
                <!--end::Small Box Widget 1-->
              </div>
              <!--end::Col-->
              <div class="col-lg-3 col-6">
                <!--begin::Small Box Widget 2-->
                <div class="small-box text-bg-success">
                  <div class="inner">
                    <h3><?= $totalViagensSemana ?><sup class="fs-5"></sup></h3>
                    <p>Viagens programadas <br> para esta semana</p>
                  </div>
                  <svg
                    class="small-box-icon"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"
                    ></path>
                  </svg>
                </div>
                <!--end::Small Box Widget 2-->
              </div>
              <!--end::Col-->
              <div class="col-lg-3 col-6">
                <!--begin::Small Box Widget 3-->
                <div class="small-box text-bg-warning">
                  <div class="inner">
                    <h3><?= $percentagemSemanaConcluida ?>%</h3>
                    <p>Viagens Semanais <br>concluídas</p>
                  </div>
                  <svg
                    class="small-box-icon"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"
                    ></path>
                  </svg>
                </div>
                <!--end::Small Box Widget 3-->
              </div>
              <!--end::Col-->
              <div class="col-lg-3 col-6">
                <!--begin::Small Box Widget 4-->
                <div class="small-box text-bg-danger">
                  <div class="inner">
                    <h3><?= $totalViagensHoje ?></h3>
                    <p>Viagens Programadas <br> Para hoje</p>
                  </div>
                  <svg
                    class="small-box-icon"
                    fill="currentColor"
                    viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg"
                    aria-hidden="true"
                  >
                    <path
                      clip-rule="evenodd"
                      fill-rule="evenodd"
                      d="M2.25 13.5a8.25 8.25 0 018.25-8.25.75.75 0 01.75.75v6.75H18a.75.75 0 01.75.75 8.25 8.25 0 01-16.5 0z"
                    ></path>
                    <path
                      clip-rule="evenodd"
                      fill-rule="evenodd"
                      d="M12.75 3a.75.75 0 01.75-.75 8.25 8.25 0 018.25 8.25.75.75 0 01-.75.75h-7.5a.75.75 0 01-.75-.75V3z"
                    ></path>
                  </svg>
                </div>
                <!--end::Small Box Widget 4-->
              </div>
              <!--end::Col-->
            </div>
            <!--end::Row-->
            <!--begin::Row-->
            <div class="row">
  <!-- Start col com gráfico, visível apenas no desktop -->
  <div class="col-lg-7 connectedSortable d-none d-lg-block">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Volume de Viagens Efetuadas</h3></div>
      <div class="card-body"><div id="revenue-chart"></div></div>
    </div>
    <!-- /.card -->
  </div>
<!-- Start col com formulário de upload, visível em todos os dispositivos -->
<div class="col-lg-5 connectedSortable">
  <div class="card text-white bg-primary bg-gradient border-primary mb-4">
    <div class="card-header border-0">
      <h3 class="card-title">Efetuar Upload de Viagens (XML)</h3>
    </div>
    <div class="card-body">
      <!-- Formulário para fazer upload do XML -->
      <form method="POST" enctype="multipart/form-data" id="uploadForm">
        <div class="text-center mb-4">
          <label for="xmlFile" class="form-label h5 text-white">Arraste e largue o seu ficheiro XML aqui ou clique para escolher</label>
          
          <!-- Área de Drag and Drop -->
          <div id="drop-area" class="drop-area p-5 text-center rounded-lg shadow-lg">
            <i class="fas fa-cloud-upload-alt fa-4x mb-3 text-white"></i>
            <p class="text-white">Arraste o seu ficheiro XML aqui</p>
            <input type="file" name="xmlFile" id="xmlFile" accept=".xml" class="form-control d-none" required>
            <div id="file-name" class="text-white mt-3">Nenhum ficheiro selecionado</div>
          </div>
        </div>
        <div class="d-grid gap-2">
          <button type="submit" class="btn btn-light btn-lg">Carregar</button>
        </div>
      </form>
      <?php
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['xmlFile']) && $_FILES['xmlFile']['error'] == 0) {

    $xmlContent = file_get_contents($_FILES['xmlFile']['tmp_name']);
    $xml = simplexml_load_string($xmlContent);

    if ($xml === false) {
        echo "<script>
                $(document).Toasts('create', {
                    title: 'Erro!',
                    body: 'Erro ao carregar o ficheiro XML!',
                    class: 'bg-danger',
                    autohide: true,
                    delay: 5000
                });
              </script>";
        return;
    }

    require_once __DIR__ . '/../../../Auth/dbconfig.php';

    $sql = "INSERT INTO Services 
            (serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType)
            VALUES 
            (:serviceDate, :serviceStartTime, :paxADT, :paxCHD, :serviceStartPoint, :serviceTargetPoint, :FlightNumber, :NomeCliente, :ClientNumber, :serviceType)";

    $stmt = $pdo->prepare($sql);
    $success = false; // Para verificar se houve alguma inserção

    if (isset($xml->Groupings->Grouping)) {
        foreach ($xml->Groupings->Grouping as $service) {
            // Definir o tipo de serviço com base no valor de <serviceUnitVehicleName>
            $vehicleType = (string) $service->serviceUnitVehicleName;
            $serviceType = ($vehicleType == 'Taxi') ? 0 : 1; // 0 para Taxi Privado, 1 para Partilhado

            // Inicializa os dados básicos do serviço
            $serviceData = [
                'serviceDate'        => (string) $service->serviceDate,
                'serviceStartTime'   => (string) $service->serviceStartTime,
                'paxADT'             => (int) $service->bookings->bookingItem->paxADT,
                'paxCHD'             => (int) $service->bookings->bookingItem->paxCHD,
                'serviceStartPoint'  => (string) $service->serviceStartPoint,
                'serviceTargetPoint' => (string) $service->serviceTargetPoint,
                'NomeCliente'        => (string) $service->bookings->bookingItem->paxLeadName,
                'ClientNumber'       => isset($service->bookings->bookingItem->remarks) ? preg_match('/Phone number: (\+?\d+)/', (string)$service->bookings->bookingItem->remarks, $matches) ? $matches[1] : 'Não disponível' : 'Não disponível',
                'serviceType'        => $serviceType // Adiciona o tipo de serviço
            ];

            // Inicializar FlightNumber com valor padrão
            $flightCodeNumber = 'Não disponível';

            // Tenta obter dados do nó de pickup
            if (
                isset($service->bookings->bookingItem->pickup->pickupPoint->flightCompanyCode) &&
                isset($service->bookings->bookingItem->pickup->pickupPoint->flightNumber) &&
                (string)$service->bookings->bookingItem->pickup->pickupPoint->flightCompanyCode !== "" &&
                (string)$service->bookings->bookingItem->pickup->pickupPoint->flightNumber !== ""
            ) {
                $flightCompanyCode = (string)$service->bookings->bookingItem->pickup->pickupPoint->flightCompanyCode;
                $flightNumber = (string)$service->bookings->bookingItem->pickup->pickupPoint->flightNumber;
                $flightCodeNumber = $flightCompanyCode . ' ' . $flightNumber;
            }
            // Se não encontrar no pickup, tenta no dropoff
            elseif (
                isset($service->bookings->bookingItem->dropoff->pickupPoint->flightCompanyCode) &&
                isset($service->bookings->bookingItem->dropoff->pickupPoint->flightNumber) &&
                (string)$service->bookings->bookingItem->dropoff->pickupPoint->flightCompanyCode !== "" &&
                (string)$service->bookings->bookingItem->dropoff->pickupPoint->flightNumber !== ""
            ) {
                $flightCompanyCode = (string)$service->bookings->bookingItem->dropoff->pickupPoint->flightCompanyCode;
                $flightNumber = (string)$service->bookings->bookingItem->dropoff->pickupPoint->flightNumber;
                $flightCodeNumber = $flightCompanyCode . ' ' . $flightNumber;
            }

            // Atualiza o campo FlightNumber do array de dados
            $serviceData['FlightNumber'] = $flightCodeNumber;

            try {
                $stmt->execute($serviceData);
                $success = true;
            } catch (PDOException $e) {
                echo "<script>
                        $(document).Toasts('create', {
                            title: 'Erro!',
                            body: 'Erro ao inserir serviço: " . $serviceData['serviceDate'] . " - " . $serviceData['serviceStartTime'] . "',
                            class: 'bg-danger',
                            autohide: true,
                            delay: 5000
                        });
                      </script>";
            }
        }

        if ($success) {
            // Você pode adicionar uma mensagem de sucesso, se desejar.
        }
    } else {
        echo "<script>
                $(document).Toasts('create', {
                    title: 'Aviso!',
                    body: 'Nenhum dado encontrado no XML!',
                    class: 'bg-warning',
                    autohide: true,
                    delay: 5000
                });
              </script>";
    }
}
?>

    </div>
  </div>
</div>
</div>



<!-- Estilos e Script para Drag and Drop -->
<style>
  #drop-area {
    background-color: #007bff;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
    border-radius: 15px;
  }

  #drop-area:hover {
    background-color: #0056b3;
    box-shadow: 0px 4px 20px rgba(0, 123, 255, 0.5);
  }

  #file-name {
    font-size: 16px;
    font-weight: 400;
    text-align: center;
    margin-top: 10px;
  }

  #drop-area .fa-cloud-upload-alt {
    font-size: 70px;
    color: #fff;
  }

  .btn-light {
    background-color: #f8f9fa;
    color: #495057;
    border: none;
  }

  .btn-light:hover {
    background-color: #e2e6ea;
    color: #212529;
  }
</style>

<script>
  // Drag and Drop Functionality
  const dropArea = document.getElementById('drop-area');
  const fileInput = document.getElementById('xmlFile');
  const fileNameDisplay = document.getElementById('file-name');

  dropArea.addEventListener('click', () => {
    fileInput.click();
  });

  dropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropArea.style.backgroundColor = '#0056b3';
    dropArea.style.boxShadow = '0px 4px 20px rgba(0, 123, 255, 0.5)';
  });

  dropArea.addEventListener('dragleave', () => {
    dropArea.style.backgroundColor = '#007bff';
    dropArea.style.boxShadow = 'none';
  });

  dropArea.addEventListener('drop', (e) => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    fileInput.files = e.dataTransfer.files; // Simula a seleção de ficheiro
    fileNameDisplay.textContent = file.name;
    dropArea.style.backgroundColor = '#007bff';
    dropArea.style.boxShadow = 'none';
  });

  fileInput.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
      fileNameDisplay.textContent = file.name;
    }
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
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
    // Verificar se existe um erro na URL e mostrar a notificação com o Toastr
    <?php if (isset($_GET['error']) && $_GET['error'] == 'senha_incorreta'): ?>
        toastr.error('Senha incorreta. Tente novamente.', 'Erro!', {
            closeButton: true,
            progressBar: true,
            timeOut: 5000, // Tempo para desaparecer (5 segundos)
            positionClass: 'toast-top-right' // Posicionar à direita no topo
        });
    <?php endif; ?>
    </script>
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
            name: 'Viagens',
            data: [28, 48, 40, 19, 86, 27, 90],
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

  </body>
  <!--end::Body-->
</html>
