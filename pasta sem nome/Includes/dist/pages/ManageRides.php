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

<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
    <!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Painel de Administrador</title>
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
</head>
  </head>
  <!--end::Head-->
  <!--begin::Body-->
  <body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <?php $success = isset($_GET['success']) ? $_GET['success'] : null; ?>

<!-- Notificação (estilo + JS + container) -->
<style>
    .notificacao {
        position: fixed;
        top: 20px; /* <-- agora aparece no topo */
        right: 20px;
        background-color: #4CAF50;
        color: #fff;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        font-family: Arial, sans-serif;
        font-size: 14px;
        opacity: 0;
        animation: fadeInOut 4s ease-in-out;
    }

    @keyframes fadeInOut {
        0% { opacity: 0; transform: translateY(-10px); }
        10% { opacity: 1; transform: translateY(0); }
        90% { opacity: 1; transform: translateY(0); }
        100% { opacity: 0; transform: translateY(-10px); }
    }
</style>

<script>
    function mostrarNotificacao(mensagem) {
        const notificacao = document.createElement("div");
        notificacao.classList.add("notificacao");
        notificacao.textContent = mensagem;
        document.body.appendChild(notificacao);

        setTimeout(() => {
            notificacao.remove();
        }, 4000);
    }

    const success = "<?php echo $success; ?>";

    if (success === "ride_created") {
        mostrarNotificacao("Viagem criada com sucesso!");
        // Remove o parâmetro da URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname);
    }
    if (success === "ViagemaApagada") {
        mostrarNotificacao("Viagem eliminada com sucesso!");
        // Remove o parâmetro da URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname);
    }
    if (success === "ViagemAtribuida") {
        mostrarNotificacao("Viagem atribuida com sucesso!");
        // Remove o parâmetro da URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname);
    }
    if (success === "rideUpdated") {
        mostrarNotificacao("Viagem editada com sucesso!");
        // Remove o parâmetro da URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname);
    }
</script>


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
        session_start();
        
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
      <?php
  require __DIR__ . '/../../../Auth/dbconfig.php';

  // Consultas SQL para obter as viagens
  $sqlViagensPorAtribuir = "
      SELECT s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.serviceStartPoint, s.serviceTargetPoint
      FROM Services s
      LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
      WHERE sr.UserID IS NULL
  ";

  $sqlViagensAtribuidas = "
      SELECT s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.serviceStartPoint, s.serviceTargetPoint, u.name AS driverName
      FROM Services s
      INNER JOIN Services_Rides sr ON s.ID = sr.RideID
      INNER JOIN Users u ON sr.UserID = u.ID
  ";

  // Consulta para obter os condutores (users com role = 2)
  $sqlCondutores = "
      SELECT ID, name FROM Users WHERE role = 2
  ";

  // Obter viagens por atribuir
  $stmt = $pdo->prepare($sqlViagensPorAtribuir);
  $stmt->execute();
  $viagensPorAtribuir = $stmt->fetchAll();

  // Obter viagens atribuídas
  $stmt = $pdo->prepare($sqlViagensAtribuidas);
  $stmt->execute();
  $viagensAtribuidas = $stmt->fetchAll();

  // Obter condutores
  $stmt = $pdo->prepare($sqlCondutores);
  $stmt->execute();
  $condutores = $stmt->fetchAll();
  ?>

<main class="app-main">
        <!--begin::App Content Header-->
        <div class="app-content-header">
          <!--begin::Container-->
          <div class="container-fluid">
            <!--begin::Row-->
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Gestão de Viagens</h3></div>
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
        <h3><?php echo count($viagensPorAtribuir); ?></h3>
        <p>Viagens por Atribuir</p>
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
        <h3><?php echo count($viagensAtribuidas); ?><sup class="fs-5"></sup></h3>
        <p>Viagens Atribuídas</p>
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

  <!-- Novo bloco inserido depois da caixa verde -->
<div class="col-12 col-sm-6 col-md-3">
  <div class="info-box text-bg-warning shadow-sm" role="button" data-bs-toggle="modal" data-bs-target="#modalCriarViagem">
    <span class="info-box-icon">
      <i class="bi bi-people-fill"></i>
    </span>
    <div class="info-box-content">
      <span class="info-box-text">Criar Viagem</span>
    </div>
  </div>
</div>

<!--end::Row-->


<!-- Modal Criar Viagem -->
<div class="modal fade" id="modalCriarViagem" tabindex="-1" aria-labelledby="modalCriarViagemLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCriarViagemLabel">Criar Nova Viagem</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <form action="addRide.php" method="POST">
          <div class="mb-3">
            <label for="inputServiceDate" class="form-label">Data da Viagem</label>
            <input type="date" class="form-control" id="inputServiceDate" name="serviceDate" required />
          </div>

          <div class="mb-3">
            <label for="inputServiceStartTime" class="form-label">Hora de Início</label>
            <input type="time" class="form-control" id="inputServiceStartTime" name="serviceStartTime" required />
          </div>

          <div class="mb-3">
            <label for="inputPaxADT" class="form-label">Passageiros Adultos</label>
            <input type="number" class="form-control" id="inputPaxADT" name="paxADT" required />
          </div>

          <div class="mb-3">
            <label for="inputPaxCHD" class="form-label">Passageiros Crianças</label>
            <input type="number" class="form-control" id="inputPaxCHD" name="paxCHD" required />
          </div>

          <div class="mb-3">
            <label for="inputServiceStartPoint" class="form-label">Ponto de Partida</label>
            <input type="text" class="form-control" id="inputServiceStartPoint" name="serviceStartPoint" required />
          </div>

          <div class="mb-3">
            <label for="inputServiceTargetPoint" class="form-label">Ponto de Chegada</label>
            <input type="text" class="form-control" id="inputServiceTargetPoint" name="serviceTargetPoint" required />
          </div>

          <div class="mb-3">
            <label for="inputDriver" class="form-label">Condutor</label>
            <select class="form-select" id="inputDriver" name="driver" required>
  <option value="later" selected>Atribuir mais tarde</option>
  <?php foreach ($condutores as $condutor): ?>
    <option value="<?= $condutor['ID'] ?>"><?= $condutor['name'] ?></option>
  <?php endforeach; ?>
</select>

          </div>

          <button type="submit" class="btn btn-primary w-100">Criar Viagem</button>
        </form>
      </div>
    </div>
  </div>
</div>



            <!--begin::App Main-->
<main class="app-main">
  <!--end::App Content Header-->

  
 <!--begin::App Main-->
<main class="app-main">
  <!--end::App Content Header-->

  

  <!--begin::App Content-->
  <div class="app-content">
    <div class="container-fluid">
      <div class="row">

        <!-- Viagens por Atribuir -->
        <div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title">Viagens por Atribuir</h3>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="text-nowrap">
          <tr>
            <th style="width: 10px">#</th>
            <th>Data e Hora</th>
            <th>Condutor</th>
            <th class="d-none d-sm-table-cell">Nº Passageiros</th>
            <th class="d-none d-md-table-cell">Recolha</th>
            <th class="d-none d-md-table-cell">Entrega</th>
            <th style="min-width: 150px">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($viagensPorAtribuir as $viagem): ?>
            <tr>
              <td><?= $viagem['ID'] ?>.</td>
              <td><?= $viagem['serviceDate'] ?> <?= $viagem['serviceStartTime'] ?></td>
              <td><span class="badge bg-secondary">N.A</span></td>
              <td class="d-none d-sm-table-cell"><?= $viagem['paxADT'] + $viagem['paxCHD'] ?></td>
              <td class="d-none d-md-table-cell"><?= $viagem['serviceStartPoint'] ?></td>
              <td class="d-none d-md-table-cell"><?= $viagem['serviceTargetPoint'] ?></td>
              <td class="text-nowrap">
                <span class="badge bg-primary" data-bs-toggle="modal" data-bs-target="#atribuirCondutorModal" onclick="setViagemId(<?= $viagem['ID'] ?>)">Atribuir</span>
                <span class="badge text-bg-warning" data-bs-toggle="modal" data-bs-target="#editModal" onclick="editTravel(<?= $viagem['ID'] ?>, '<?= $viagem['serviceDate'] ?> <?= $viagem['serviceStartTime'] ?>', 'N.A', '<?= $viagem['paxADT'] + $viagem['paxCHD'] ?>', '<?= $viagem['serviceStartPoint'] ?>', '<?= $viagem['serviceTargetPoint'] ?>')">Detalhes</span>
                <span class="badge bg-danger" data-bs-toggle="modal" data-bs-target="#deleteTripModal" onclick="setDeleteTrip(<?= $viagem['ID'] ?>, '<?= $viagem['serviceStartPoint'] ?> - <?= $viagem['serviceTargetPoint'] ?>')">Apagar</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>



       <!-- Viagens Atribuídas -->
<div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title">Viagens Atribuídas</h3>
  </div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped mb-0">
        <thead>
          <tr>
            <th style="width: 10px">#</th>
            <th>Data e Hora</th>
            <th>Condutor</th>
            <th>Nº Passageiros</th>
            <th>Local de Recolha</th>
            <th>Local de Entrega</th>
            <th style="width: 150px">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($viagensAtribuidas as $viagem): ?>
            <tr class="align-middle">
              <td><?= $viagem['ID'] ?>.</td>
              <td><?= $viagem['serviceDate'] ?> <?= $viagem['serviceStartTime'] ?></td>
              <td><span class="badge text-bg-success"><?= $viagem['driverName'] ?></span></td>
              <td><?= $viagem['paxADT'] + $viagem['paxCHD'] ?></td>
              <td><?= $viagem['serviceStartPoint'] ?></td>
              <td><?= $viagem['serviceTargetPoint'] ?></td>
              <td>
                <span class="badge text-bg-warning" data-bs-toggle="modal" data-bs-target="#editModal" onclick="editTravel(<?= $viagem['ID'] ?>, '<?= $viagem['serviceDate'] ?> <?= $viagem['serviceStartTime'] ?>', '<?= $viagem['driverName'] ?>', '<?= $viagem['paxADT'] + $viagem['paxCHD'] ?>', '<?= $viagem['serviceStartPoint'] ?>', '<?= $viagem['serviceTargetPoint'] ?>')">Detalhes</span>
                <span class="badge text-bg-danger" data-bs-toggle="modal" data-bs-target="#deleteTripModal" onclick="setDeleteTripId(<?= $viagem['ID'] ?>, '<?= $viagem['serviceDate'] ?> <?= $viagem['serviceStartTime'] ?>')">Apagar</span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<script>
  // Função para definir o ID da viagem a ser apagada e nome
  function setDeleteTripId(viagemId, viagemName) {
    document.getElementById('deleteTripName').innerText = viagemName;
    document.getElementById('confirmDeleteTripBtn').href = 'apagar_viagem.php?id=' + viagemId; // Alterei para 'apagar_viagem.php'
  }
</script>
<!-- Modal Editar Viagem -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Editar Viagem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <form id="editTripForm" action="updateRide.php" method="POST">
                    <!-- Campo oculto para o ID da viagem -->
                    <input type="hidden" name="edit_trip_id" id="editTripId">
                    
                    <div class="mb-3">
                        <label for="editDataHora" class="form-label">Data e Hora</label>
                        <input type="datetime-local" class="form-control" id="editDataHora" name="edit_departure_time" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="editCondutor" class="form-label">Condutor</label>
                        <select class="form-control" id="editCondutor" name="edit_conductor_id" disabled>
                            <?php foreach ($conductors as $conductor): ?>
                                <option value="<?= $conductor['id'] ?>"><?= $conductor['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editPassageiros" class="form-label">Número de Passageiros</label>
                        <input type="number" class="form-control" id="editPassageiros" name="edit_passengers" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="editRecolha" class="form-label">Local de Recolha</label>
                        <input type="text" class="form-control" id="editRecolha" name="edit_origin" disabled>
                    </div>

                    <div class="mb-3">
                        <label for="editEntrega" class="form-label">Local de Entrega</label>
                        <input type="text" class="form-control" id="editEntrega" name="edit_destination" disabled>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <!-- Botão para ativar a edição -->
                <button type="button" class="btn btn-warning" id="enableEditBtn" onclick="enableEdit()">Editar</button>
                <!-- Botão para salvar alterações (inicialmente oculto) -->
                <button type="submit" class="btn btn-primary" form="editTripForm" id="saveChangesBtn" style="display: none;">Guardar Alterações</button>
            </div>
        </div>
    </div>
</div>

<script>
    function enableEdit() {
        // Habilitar todos os campos
        document.getElementById('editDataHora').disabled = false;
        document.getElementById('editCondutor').disabled = false;
        document.getElementById('editPassageiros').disabled = false;
        document.getElementById('editRecolha').disabled = false;
        document.getElementById('editEntrega').disabled = false;
        
        // Mostrar o botão de salvar alterações
        document.getElementById('saveChangesBtn').style.display = 'inline-block';
        
        // Alterar o texto do botão para "Cancelar Edição"
        document.getElementById('enableEditBtn').textContent = 'Cancelar Edição';
        document.getElementById('enableEditBtn').setAttribute('onclick', 'disableEdit()');
    }

    function disableEdit() {
        // Desabilitar os campos novamente
        document.getElementById('editDataHora').disabled = true;
        document.getElementById('editCondutor').disabled = true;
        document.getElementById('editPassageiros').disabled = true;
        document.getElementById('editRecolha').disabled = true;
        document.getElementById('editEntrega').disabled = true;
        
        // Ocultar o botão de salvar alterações
        document.getElementById('saveChangesBtn').style.display = 'none';

        // Alterar o texto do botão para "Editar"
        document.getElementById('enableEditBtn').textContent = 'Editar';
        document.getElementById('enableEditBtn').setAttribute('onclick', 'enableEdit()');
    }
</script>




  <!-- Modal Atribuir Condutor -->
  <div class="modal fade" id="atribuirCondutorModal" tabindex="-1" aria-labelledby="atribuirCondutorModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="atribuirCondutorModalLabel">Selecionar Condutor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <label for="condutorSelect">Escolha o Condutor:</label>
          <select id="condutorSelect" class="form-select">
            <?php foreach ($condutores as $condutor): ?>
              <option value="<?= $condutor['ID'] ?>"><?= $condutor['name'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          <button type="button" class="btn btn-primary" onclick="atribuirCondutor()">Atribuir Condutor</button>
        </div>
      </div>
    </div>
  </div>

 <!-- Modal de Confirmação de Exclusão da Viagem -->
<div class="modal fade" id="deleteTripModal" tabindex="-1" aria-labelledby="deleteTripModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTripModalLabel">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja apagar a viagem <strong id="deleteTripName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmDeleteTripBtn" class="btn btn-danger">Apagar</a>
            </div>
        </div>
    </div>
</div>
<script>
let viagemIdToDelete = null;

function setDeleteTrip(id, name) {
    viagemIdToDelete = id;
    document.getElementById("deleteTripName").textContent = name;
    
    // Atualiza o botão de confirmação para chamar corretamente o delete_trip.php
    document.getElementById("confirmDeleteTripBtn").href = `apagar_viagem.php?id=${id}`;
}
</script>


</main>

<script>

// Preencher o modal de exclusão com o nome da viagem e ID
document.addEventListener('DOMContentLoaded', function() {
    var deleteTripModal = document.getElementById('deleteTripModal');
    deleteTripModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var tripId = button.getAttribute('data-id');
        var tripName = button.getAttribute('data-name');
        document.getElementById('deleteTripName').textContent = tripName;
        
        // Setar o link de confirmação de exclusão com o id da viagem
        document.getElementById('confirmDeleteTripBtn').setAttribute('href', 'apagar_viagem.php?id=' + tripId);
    });
});

  let viagemId = null;

  // Função para definir o ID da viagem selecionada
  function setViagemId(id) {
    viagemId = id;
  }

  function atribuirCondutor() {
    const condutorId = document.getElementById('condutorSelect').value;
    if (viagemId !== null && condutorId) {
        console.log(`Condutor ${condutorId} atribuído à viagem ${viagemId}`);
        
        // Enviar os dados para o backend para associar a viagem com o condutor
        const formData = new FormData();
        formData.append('viagemId', viagemId);
        formData.append('condutorId', condutorId);
        
        fetch('atribuirCondutor.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Redireciona ou recarrega a página independentemente do sucesso ou falha
            window.location.reload();
        })
        .catch(error => {
            console.error('Erro:', error);
            window.location.reload();
        });
    }
}




  function editTravel(id, dataHora, condutor, passageiros, recolha, entrega) {
    document.getElementById('editTripId').value = id;
    document.getElementById('editDataHora').value = dataHora;
    document.getElementById('editCondutor').value = condutor
    document.getElementById('editPassageiros').value = passageiros;
    document.getElementById('editRecolha').value = recolha;
    document.getElementById('editEntrega').value = entrega;
    

    let selectCondutor = document.getElementById('editCondutor');

    // Garantir que o condutor existe na lista antes de selecionar
    if (condutor !== null && condutor !== "null") {
        let encontrou = false;
        for (let option of selectCondutor.options) {
            if (option.value == condutor) {
                option.selected = true;
                encontrou = true;
                break;
            }
        }
        if (!encontrou) {
            console.warn("Condutor não encontrado na lista: ", condutor);
        }
    }

    // Abrir o modal
var editModal = new bootstrap.Modal(document.getElementById('editModal'));

// Mostrar o modal
editModal.show();

// Evento que ocorre quando o modal é fechado
editModal._element.addEventListener('hidden.bs.modal', function () {
    // Remover manualmente a camada de fundo (backdrop)
    var backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        backdrop.remove();
    }
});

}


  
</script>


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
