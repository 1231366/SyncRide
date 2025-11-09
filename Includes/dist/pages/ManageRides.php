<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está logado e tem a role de admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=../../../index.php");
    exit();
}

// O ÚNICO PHP que precisamos aqui é para os MODAIS (lista de condutores)
require __DIR__ . '/../../../Auth/dbconfig.php';
try {
    $stmt = $pdo->prepare("SELECT ID, name FROM Users WHERE role = 2");
    $stmt->execute();
    $condutores = $stmt->fetchAll();
} catch (PDOException $e) {
    $condutores = []; // Em caso de erro, o modal fica vazio mas a página carrega
}
?>

<!doctype html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Gerir Viagens</title>
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
        /* A barra de pesquisa do DataTables volta a ser visível, mas com estilo do BS5 */
        div.dataTables_wrapper div.dataTables_filter {
            text-align: right;
            margin-bottom: 0.5rem; /* Ajusta o espaçamento */
        }
        div.dataTables_wrapper div.dataTables_filter label {
            font-weight: normal;
            white-space: nowrap;
            text-align: left;
        }
        div.dataTables_wrapper div.dataTables_filter input {
            margin-left: 0.5em;
            display: inline-block;
            width: auto;
            border-radius: .25rem; /* Bordas arredondadas */
            border: 1px solid #ced4da; /* Borda leve */
            padding: .375rem .75rem; /* Espaçamento interno */
        }

        /* Abas com estilo de "pílula" (botão) */
        .nav-pills .nav-link {
            background-color: #f8f9fa;
            color: #495057;
            font-weight: 600;
            margin-right: 8px;
            border: 1px solid #dee2e6;
        }
        .nav-pills .nav-link.active {
            color: #fff;
            background-color: #007bff;
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.25);
        }
        .nav-pills .nav-link:hover:not(.active) {
            background-color: #e9ecef;
        }

        /* Tabela limpa e minimalista */
        .table-borderless tbody tr {
             border-bottom: 1px solid #dee2e6; /* Linha horizontal suave */
        }
        .table-borderless thead th {
             border-bottom: 2px solid #343a40; /* Cabeçalho mais forte */
        }
        .table-hover tbody tr:hover {
            background-color: #f1f3f5 !important; /* Cor de hover suave */
        }

        /* Badge de loading */
        .loading-badge {
            display: none; /* Escondido por defeito */
        }

        /* Botões de Ação Modernos (Ícones Redondos) */
        .btn-group-sm > .btn.rounded-circle {
            width: 34px;    /* Tamanho ligeiramente maior */
            height: 34px;
            padding: 0;
            line-height: 1; /* Alinha o ícone */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 4px;  /* Espaço entre os botões */
            border: none;   /* Remover borda feia */
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); /* Sombra suave */
            transition: all 0.2s ease;
        }
        .btn-group-sm > .btn.rounded-circle:hover {
            transform: translateY(-2px); /* Efeito "levantar" */
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        /* Cor específica para o botão de apagar */
        .btn-danger.rounded-circle {
            background-color: #dc3545; /* Vermelho do Bootstrap */
            color: #fff;
        }
        .btn-danger.rounded-circle:hover {
            background-color: #c82333; /* Vermelho mais escuro no hover */
        }

        /* Tooltip (Dica) com look profissional */
        .tooltip-inner {
            background-color: #343a40;
            color: #fff;
            border-radius: 4px;
            font-size: 0.8rem;
            padding: 4px 8px;
        }
        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #343a40;
        }

        /* Ajustes para o layout do card-header */
        .card-header-custom {
            display: flex;
            flex-wrap: wrap; /* Permite que os itens quebrem para a linha de baixo */
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem; /* Bootstrap default card-header padding */
            border-bottom: 1px solid rgba(0,0,0,.125); /* Default card-header border */
        }
        .card-header-custom .nav-pills {
            margin-bottom: 0; /* Remove margin-bottom do nav-pills */
        }
        .card-header-custom .dataTables_filter {
            margin-bottom: 0; /* Remove margin-bottom do filtro */
            margin-left: auto; /* Empurra para a direita */
            padding-left: 1rem; /* Espaçamento à esquerda do campo de pesquisa */
        }
        .card-header-custom .btn-success {
             margin-left: 1rem; /* Espaçamento à esquerda do botão */
        }
        /* Responsividade para o header */
        @media (max-width: 767.98px) {
            .card-header-custom {
                flex-direction: column;
                align-items: flex-start;
            }
            .card-header-custom .nav-pills {
                margin-bottom: 1rem;
                width: 100%; /* Ocupa a largura total */
            }
            .card-header-custom .nav-pills .nav-item {
                flex-grow: 1; /* Faz com que as pills se estiquem */
                text-align: center;
            }
            .card-header-custom .dataTables_filter,
            .card-header-custom .btn-success {
                margin-left: 0;
                margin-top: 1rem;
                width: 100%;
            }
            .card-header-custom .dataTables_filter label {
                width: 100%;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }
            .card-header-custom .dataTables_filter input {
                flex-grow: 1; /* Ocupa o espaço restante */
                margin-left: 10px;
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
                  <a href="ManageRides.php" class="nav-link active"> <i class="nav-icon bi bi-box-seam-fill"></i>
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
                  <a href="admin_driver_stats.php" class="nav-link">
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
              <div class="col-sm-6"><h3 class="mb-0">Gestão de Viagens</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Gestão de Viagens</li>
                </ol>
              </div>
            </div>
            </div>
          </div>
        <div class="app-content">
          <div class="container-fluid">
            
            <div class="card shadow-sm border-0">
                <div class="card-header-custom">
                    <ul class="nav nav-pills me-auto" id="ride-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#pending" data-status="pending">
                                <i class="bi bi-clock-history me-1"></i> Por Atribuir
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#assigned" data-status="assigned">
                                <i class="bi bi-person-check-fill me-1"></i> Atribuídas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#all" data-status="all">
                                <i class="bi bi-list-ul me-1"></i> Todas as Viagens
                            </a>
                        </li>
                    </ul>
                    
                    <span class="badge bg-secondary loading-badge me-2">
                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        A carregar...
                    </span>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCriarViagem">
                        <i class="bi bi-plus-circle-fill me-1"></i> Criar Nova Viagem
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tabelaViagens" class="table table-borderless table-hover align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Data & Hora</th>
                                    <th>Condutor</th>
                                    <th>Recolha</th>
                                    <th>Entrega</th>
                                    <th>Tipo</th>
                                    <th class="text-center">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                </tbody>
                        </table>
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
    <div class="modal fade" id="modalCriarViagem" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalCriarViagemLabel">Criar Nova Viagem</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body">
            <form action="addRide.php" method="POST">
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="inputServiceDate" class="form-label">Data da Viagem</label>
                  <input type="date" class="form-control" id="inputServiceDate" name="serviceDate" required />
                </div>
                <div class="col-md-6">
                  <label for="inputServiceStartTime" class="form-label">Hora de Início</label>
                  <input type="time" class="form-control" id="inputServiceStartTime" name="serviceStartTime" required />
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="inputPaxADT" class="form-label">Passageiros Adultos</label>
                  <input type="number" class="form-control" id="inputPaxADT" name="paxADT" required />
                </div>
                <div class="col-md-6">
                  <label for="inputPaxCHD" class="form-label">Passageiros Crianças</label>
                  <input type="number" class="form-control" id="inputPaxCHD" name="paxCHD" required />
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="inputServiceStartPoint" class="form-label">Ponto de Partida</label>
                  <input type="text" class="form-control" id="inputServiceStartPoint" name="serviceStartPoint" required />
                </div>
                <div class="col-md-6">
                  <label for="inputServiceTargetPoint" class="form-label">Ponto de Chegada</label>
                  <input type="text" class="form-control" id="inputServiceTargetPoint" name="serviceTargetPoint" required />
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="inputDriver" class="form-label">Condutor</label>
                  <select class="form-select" id="inputDriver" name="driver" required>
                    <option value="later" selected>Atribuir mais tarde</option>
                    <?php foreach ($condutores as $condutor): ?>
                      <option value="<?= $condutor['ID'] ?>"><?= htmlspecialchars($condutor['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label for="inputServiceType" class="form-label">Tipo de Serviço</label>
                  <select class="form-select" id="inputServiceType" name="serviceType" required>
                    <option value="1" selected>Privado</option>
                    <option value="0">Partilhado</option>
                  </select>
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="inputFlightNumber" class="form-label">Número do Voo</label>
                  <input type="text" class="form-control" id="inputFlightNumber" name="FlightNumber" />
                </div>
                <div class="col-md-6">
                  <label for="inputNomeCliente" class="form-label">Nome do Cliente</label>
                  <input type="text" class="form-control" id="inputNomeCliente" name="NomeCliente" />
                </div>
              </div>
              <div class="row mb-3">
                <div class="col-md-6">
                  <label for="inputClientNumber" class="form-label">Número do Cliente</label>
                  <input type="text" class="form-control" id="inputClientNumber" name="ClientNumber" />
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100">Criar Viagem</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="changeTripTypeModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="changeTripTypeModalLabel">Alterar Tipo de Viagem</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="changeTripForm" action="update_trip_type.php" method="POST">
              <div class="modal-body">
                  <input type="hidden" id="tripId_changeType" name="tripId" value="">
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="tripType" id="private" value="1">
                    <label class="form-check-label" for="private">Private</label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="radio" name="tripType" id="shared" value="0">
                    <label class="form-check-label" for="shared">Shared</label>
                  </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" class="btn btn-primary">Guardar Alterações</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Detalhes da Viagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                <form id="editTripForm" action="updateRide.php" method="POST">
                    <input type="hidden" name="edit_trip_id" id="editTripId">
                    <div class="mb-3">
                        <label for="editDataHora" class="form-label">Data e Hora</label>
                        <input type="datetime-local" class="form-control" id="editDataHora" name="edit_departure_datetime" disabled>
                    </div>
                    <div class="mb-3" id="editCondutorWrapper">
                        <label for="editCondutor" class="form-label">Condutor</label>
                        <input type="text" class="form-control" id="editCondutor" name="edit_driverName" disabled>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label for="editRecolha" class="form-label">Local de Recolha</label>
                            <input type="text" class="form-control" id="editRecolha" name="edit_origin" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="editEntrega" class="form-label">Local de Entrega</label>
                            <input type="text" class="form-control" id="editEntrega" name="edit_destination" disabled>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-4">
                            <label for="editpaxADT" class="form-label">Adultos</label>
                            <input type="number" class="form-control" id="editpaxADT" name="edit_paxADT" disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="editpaxCHD" class="form-label">Crianças</label>
                            <input type="number" class="form-control" id="editpaxCHD" name="edit_paxCHD" disabled>
                        </div>
                        <div class="col-md-4">
                            <label for="editflightNumber" class="form-label">Voo</label>
                            <input type="text" class="form-control" id="editflightNumber" name="edit_flightNumber" disabled>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <div class="col-md-6">
                            <label for="editclientName" class="form-label">Cliente</label>
                            <input type="text" class="form-control" id="editclientName" name="edit_clientName" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="editclientNumber" class="form-label">Nº Cliente</label>
                            <input type="text" class="form-control" id="editclientNumber" name="edit_clientNumber" disabled>
                        </div>
                    </div>
                </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-warning" id="enableEditBtn" onclick="enableEdit()">Editar</button>
                    <button type="submit" class="btn btn-primary" form="editTripForm" id="saveChangesBtn" style="display: none;">Guardar Alterações</button>
                </div>
            </div>
        </div>
    </div>
    
      <div class="modal fade" id="atribuirCondutorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="atribuirCondutorModalLabel">Atribuir/Mudar Condutor</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="assignDriverForm" action="atribuircondutor.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="viagemId_assign" name="viagemId" value="">
                    <label for="condutorSelect">Escolha o Condutor:</label>
                    <select id="condutorSelect" name="condutorId" class="form-select">
                        <?php foreach ($condutores as $condutor): ?>
                          <option value="<?= $condutor['ID'] ?>"><?= htmlspecialchars($condutor['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                  <button type="submit" class="btn btn-primary">Atribuir Condutor</button>
                </div>
            </form>
          </div>
        </div>
      </div>

     <div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
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


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
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
    
    // Variável global para a tabela
    let tabelaViagens;
    const loadingBadge = document.querySelector('.loading-badge');

    $(document).ready(function () {
        
        tabelaViagens = $('#tabelaViagens').DataTable({
            "processing": true,
            "serverSide": false, // Estamos a carregar tudo de uma vez por AJAX
            "ajax": {
                "url": "load_rides_data.php?status=pending", // Carregar "Por Atribuir" por defeito
                "type": "GET",
                "dataSrc": "data"
            },
            "columns": [
                { "data": "id", "width": "5%" },
                { "data": "data_hora", "width": "15%" },
                { "data": "condutor", "width": "15%" },
                { "data": "recolha", "width": "20%" },
                { "data": "entrega", "width": "20%" },
                { "data": "tipo", "width": "5%" },
                { "data": "acoes", "width": "15%", "orderable": false, "className": "text-center" }
            ],
            "language": {
                "search": "🔍 Procurar:",
                "lengthMenu": "Mostrar _MENU_ registos",
                "info": "A mostrar _START_ a _END_ de _TOTAL_ viagens",
                "infoEmpty": "Nenhuma viagem encontrada",
                "infoFiltered": "(filtrado de _MAX_ viagens no total)",
                "zeroRecords": "Nenhuma viagem encontrada com esse filtro",
                "paginate": {
                    "first": "Primeira",
                    "last": "Última",
                    "next": "Seguinte",
                    "previous": "Anterior"
                },
                "loadingRecords": "A carregar..."
            },
            "order": [[1, 'asc']], // Ordenar por Data & Hora ascendente
            "pageLength": 10,
            "drawCallback": function( settings ) {
                // (Re)ativar os tooltips do Bootstrap5 sempre que a tabela for redesenhada
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                    // Evita criar tooltips duplicados
                    var tooltipInstance = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                    if (tooltipInstance) {
                        return tooltipInstance;
                    } else {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    }
                });
            }
        });

        // Eventos de loading
        tabelaViagens.on('preXhr.dt', function () {
            loadingBadge.style.display = 'inline-block';
        });
        tabelaViagens.on('xhr.dt', function () {
            loadingBadge.style.display = 'none';
        });

        // Lógica para as TABS (Filtros)
        $('#ride-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const status = $(e.target).data('status');
            const newUrl = `load_rides_data.php?status=${status}`;
            
            // Atualiza o URL do AJAX da tabela e recarrega os dados
            tabelaViagens.ajax.url(newUrl).load();
        });
        
        // Lógica para a BARRA DE PESQUISA DO DATATABLES
        $('div.dataTables_filter input').attr('placeholder', 'Procurar...');
        $('div.dataTables_filter input').off('keyup search input').on('keyup', function() {
            tabelaViagens.search(this.value).draw();
        });

    });

    // --- FUNÇÕES GLOBAIS PARA OS MODAIS ---
    // Estas funções são chamadas pelos botões "onclick" gerados no load_rides_data.php

    // Para o modal "Atribuir Condutor"
    function setViagemId(id) {
        document.getElementById('viagemId_assign').value = id;
    }

    // Para o modal "Mudar Tipo de Viagem"
    function changeTripType(tripId, currentType) {
        document.getElementById('tripId_changeType').value = tripId;
        if (currentType == 1) {
            document.getElementById('private').checked = true;
        } else {
            document.getElementById('shared').checked = true;
        }
    }

    // Para o modal "Editar/Ver Detalhes"
    function editTravel(id, dataHora, condutor, recolha, entrega, paxADT, paxCHD, flightNumber, clientName, clientNumber) {
        // Resetar o modal para "disabled"
        disableEdit(); 
        
        document.getElementById('editTripId').value = id;
        document.getElementById('editDataHora').value = dataHora;
        document.getElementById('editCondutor').value = condutor;
        document.getElementById('editRecolha').value = recolha;
        document.getElementById('editEntrega').value = entrega;
        document.getElementById('editpaxADT').value = paxADT;
        document.getElementById('editpaxCHD').value = paxCHD;
        document.getElementById('editflightNumber').value = flightNumber;
        document.getElementById('editclientName').value = clientName;
        document.getElementById('editclientNumber').value = clientNumber;
    }

    // Para o modal "Apagar Viagem"
    function setDeleteTrip(id, name) {
        document.getElementById("deleteTripName").textContent = name;
        document.getElementById("confirmDeleteTripBtn").href = `apagar_viagem.php?id=${id}`;
    }

    // Funções para o botão "Editar" dentro do modal de Detalhes
    function enableEdit() {
        document.querySelectorAll('#editTripForm input').forEach(input => {
            if (input.id !== 'editCondutor') { // Não deixa editar o nome do condutor
                input.disabled = false;
            }
        });
        document.getElementById('saveChangesBtn').style.display = 'inline-block';
        document.getElementById('enableEditBtn').textContent = 'Cancelar Edição';
        document.getElementById('enableEditBtn').setAttribute('onclick', 'disableEdit()');
    }

    function disableEdit() {
        document.querySelectorAll('#editTripForm input').forEach(input => input.disabled = true);
        document.getElementById('saveChangesBtn').style.display = 'none';
        document.getElementById('enableEditBtn').textContent = 'Editar';
        document.getElementById('enableEditBtn').setAttribute('onclick', 'enableEdit()');
    }

    // Toastr (Notificações)
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000",
    };
    const success = "<?php echo isset($_GET['success']) ? $_GET['success'] : ''; ?>";
    if (success) {
        let message = '';
        if (success === "ride_created") message = "Viagem criada com sucesso!";
        if (success === "ViagemaApagada") message = "Viagem eliminada com sucesso!";
        if (success === "viagemAtribuida") message = "Viagem atribuída com sucesso!";
        if (success === "rideUpdated") message = "Viagem editada com sucesso!";
        if (success === "TypeChanged") message = "Tipo de viagem alterado com sucesso!";
        
        if (message) {
            toastr.success(message, 'Sucesso!');
            // Limpa o parâmetro da URL
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url.pathname);
        }
    }
    </script>
    
  </body>
  </html>