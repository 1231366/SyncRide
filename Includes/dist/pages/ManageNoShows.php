<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está logado e tem a role de admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=../../../index.php");
    exit();
}
?>

<!doctype html>
<html lang="en">
  <head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Gerir No Shows</title>
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
        /* Esconde a barra de pesquisa feia do DataTables */
        .dataTables_filter {
            display: none;
        }
        /* Tabela limpa */
        .table-borderless tbody tr {
             border-bottom: 1px solid #dee2e6;
        }
        .table-borderless thead th {
             border-bottom: 2px solid #343a40;
        }
        .table-hover tbody tr:hover {
            background-color: #f1f3f5 !important;
        }
        /* Botões de Ação Modernos */
        .btn-group-sm > .btn.rounded-circle {
            width: 34px; height: 34px; padding: 0;
            line-height: 1; display: inline-flex;
            align-items: center; justify-content: center;
            margin: 0 4px; border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        .btn-group-sm > .btn.rounded-circle:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        /* Tooltip */
        .tooltip-inner {
            background-color: #343a40; color: #fff;
            border-radius: 4px; font-size: 0.8rem; padding: 4px 8px;
        }
        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #343a40;
        }
        /* Estilo para a imagem no modal */
        #photoModalImage {
            width: 100%;
            height: auto;
            border-radius: 8px;
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
              <div class="navbar-search-block">
                <form class="form-inline">
                  <div class="input-group">
                    <input class="form-control form-control-navbar" type="search" placeholder="Procurar na tabela..." aria-label="Search" id="table-search-input">
                    <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                      <i class="bi bi-x-lg"></i>
                    </button>
                  </div>
                </form>
              </div>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
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
                  <p><?php echo $_SESSION['name']; ?> - Admin</p>
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
                  <a href="admin_driver_stats.php" class="nav-link">
                      <i class="nav-icon bi bi-graph-up"></i>
                      <p>Estatísticas (Condutores)</p>
                  </a>
              </li>
              <li class="nav-item">
                  <a href="ManageNoShows.php" class="nav-link active">
                      <i class="nav-icon bi bi-camera-fill"></i>
                      <p>Gerir No Shows</p>
                  </a>
              </li>
              <li class="nav-item">
                  <a href="manageStorage.php" class="nav-link">
                      <i class="nav-icon bi bi-archive-fill"></i>
                      <p>Gerir Armazenamento</p>
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
              <div class="col-sm-6"><h3 class="mb-0">Gerir Viagens "No Show"</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="admin.php">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">No Shows</li>
                </ol>
              </div>
            </div>
          </div>
        </div>
        <div class="app-content">
          <div class="container-fluid">
            
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tabelaNoShows" class="table table-borderless table-hover align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID Viagem</th>
                                    <th>Data & Hora</th>
                                    <th>Condutor</th>
                                    <th>Rota</th>
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
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="photoModalTitle">Foto de No Show - Viagem #</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
          </div>
          <div class="modal-body text-center">
            <img src="" id="photoModalImage" alt="Comprovativo de No Show">
          </div>
        </div>
      </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    
    <script
      src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
      integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r"
      crossorigin="anonymous"
    ></script>
    <script
      src="httpsG://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
      integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy"
      crossorigin="anonymous"
    ></script>
    <script src="../../dist/js/adminlte.js"></script>
    
    <script
      src="https.cdn.jsdelivr.net/npm/overlayscrollbars@2.10.1/browser/overlayscrollbars.browser.es6.min.js"
      integrity="sha256-dghWARbRe2eLlIJ56wNB+b760ywulqK3DzZYEpsg2fQ="
      crossorigin="anonymous"
    ></script>
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
    
    let tabelaNoShows;
    let photoModal; // Instância do modal de foto

    $(document).ready(function () {
        
        // Inicializar os Modais do Bootstrap
        photoModal = new bootstrap.Modal(document.getElementById('photoModal'));

        tabelaNoShows = $('#tabelaNoShows').DataTable({
            "processing": true,
            "serverSide": false, // Usar false se o load_noshows_data.php retornar todos de uma vez
            "ajax": {
                "url": "load_noshows_data.php",
                "type": "GET",
                "dataSrc": "data"
            },
            "columns": [
                { "data": "id", "width": "10%" },
                { "data": "data_hora", "width": "20%" },
                { "data": "condutor", "width": "20%" },
                { "data": "rota", "width": "30%" },
                { "data": "acoes", "width": "20%", "orderable": false, "className": "text-center" }
            ],
            "language": {
                // ... (traduções do datatables) ...
                "search": "🔍 Procurar:",
                "lengthMenu": "Mostrar _MENU_ registos",
                "info": "A mostrar _START_ a _END_ de _TOTAL_ viagens",
                "infoEmpty": "Nenhuma viagem 'No Show' encontrada",
                "infoFiltered": "(filtrado de _MAX_ viagens no total)",
                "zeroRecords": "Nenhuma viagem encontrada com esse filtro",
                "paginate": {"next": "Seguinte", "previous": "Anterior"}
            },
            "order": [[1, 'desc']], // Ordenar por Data (mais recente primeiro)
            "pageLength": 10,
            "drawCallback": function( settings ) {
                // (Re)ativar os tooltips do Bootstrap5
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    var tooltipInstance = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                    if (tooltipInstance) {
                        return tooltipInstance;
                    } else {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    }
                });
            }
        });
        
        // Lógica para a BARRA DE PESQUISA MODERNA (na navbar)
        $('#table-search-input').on('keyup', function() {
            tabelaNoShows.search(this.value).draw();
        });
        
        // JS DO FORMULÁRIO DE EMAIL FOI REMOVIDO
    });

    // --- FUNÇÕES GLOBAIS PARA OS MODAIS ---

    function openPhotoModal(tripId, photoPath) {
        $('#photoModalTitle').text('Foto de No Show - Viagem #' + tripId);
        $('#photoModalImage').attr('src', photoPath);
        photoModal.show();
    }

    // A FUNÇÃO openEmailModal FOI REMOVIDA (o link agora é direto)
    
    // Toastr (Notificações)
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000",
    };
    </script>
    
  </body>
  </html>