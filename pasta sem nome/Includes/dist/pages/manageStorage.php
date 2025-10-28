<?php
session_start();
?>
<!doctype html>
<html lang="en">
  <!--begin::Head-->
  <head>
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

    if (success === "user_created") {
        mostrarNotificacao("Utilizador criado com sucesso!");
        // Remove o parâmetro da URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname);
    }
    if (success === "user_deleted") {
        mostrarNotificacao("Utilizador eliminado com sucesso!");
        // Remove o parâmetro da URL
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, document.title, url.pathname);
    }
    if (success === "user_updated") {
        mostrarNotificacao("Utilizador Editado com sucesso!");
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
            <li class="breadcrumb-item active" aria-current="page">Gestão de Armazenamento</li>
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
        <!--begin::Col - Gestão de Armazenamento-->
        <div class="col-lg-12">
          <div class="card mb-4">
            <div class="card-header">
              <h3 class="card-title">Gestão de Armazenamento</h3>
            </div>
            <div class="card-body">
              <div class="row">
                <!-- Botão de Backup -->
                <div class="col-md-3">
                  <button class="btn btn-primary w-100" id="backup-btn">
                    <i class="bi bi-box-arrow-down"></i> Fazer Backup
                  </button>
                </div>
                <!-- Botão de Eliminar Viagens -->
<div class="col-md-3">
  <button class="btn btn-danger w-100" id="delete-rides-btn">
    <i class="bi bi-trash"></i> Eliminar Viagens
  </button>
</div>

<script>
  document.getElementById('delete-rides-btn').addEventListener('click', function () {
    // Confirmação antes de eliminar
    if (confirm('Tem certeza de que deseja eliminar todas as viagens?')) {
      fetch('delete_rides.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Todas as viagens foram eliminadas com sucesso!');
            // Atualizar a interface ou redirecionar
            window.location.reload();
          } else {
            alert('Erro ao eliminar as viagens: ' + data.message);
          }
        })
        .catch(error => {
          alert('Erro: ' + error.message);
        });
    }
  });
</script>

                <!-- Botão de Limpeza de Dados -->
                <div class="col-md-3">
  <button class="btn btn-warning w-100" id="clear-data-btn">
    <i class="bi bi-eraser"></i> Limpar Histórico de Ações
  </button>
</div>

<script>
  document.getElementById('clear-data-btn').addEventListener('click', function () {
    if (confirm('Tem certeza de que deseja limpar todo o histórico de ações?')) {
      fetch('clear_logs.php', { method: 'POST' })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert('Histórico de ações limpo com sucesso!');
            window.location.reload();
          } else {
            alert('Erro ao limpar o histórico: ' + data.message);
          }
        })
        .catch(error => {
          alert('Erro: ' + error.message);
        });
    }
  });
</script>

              </div>
            </div>
          </div>
        </div>
        <!--end::Col-->
      </div>
      <!--end::Row-->
      
      <!--begin::Row - Status do Sistema e Histórico -->
<div class="row">
    <!-- Status do Sistema -->
    <div class="col-lg-6 d-flex align-items-stretch">
        <div class="card mb-4 w-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Saúde do Sistema</h3>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php
                // Conectar à base de dados
                $conn = new mysqli('localhost', 'root', '', 'SyncRide');

                // Verificar erro na conexão
                if ($conn->connect_error) {
                    die("Erro na conexão com a base de dados.");
                }

                // Buscar a data do último backup
                $sql = "SELECT date FROM Logs WHERE Action = 'Backup da base de dados realizado' ORDER BY date DESC LIMIT 1";
                $result = $conn->query($sql);
                $lastBackup = $result->fetch_assoc();
                
                if ($lastBackup) {
                    $backupDate = strtotime($lastBackup['date']);
                    $diff = (time() - $backupDate) / (60 * 60 * 24); // Diferença em dias

                    // Determinar a saúde com base no tempo desde o último backup
                    if ($diff < 7) {
                        $progress = 100;
                        $status = "Ótimo! O último backup foi recente.";
                        $badge = "success";
                    } elseif ($diff < 30) {
                        $progress = 60;
                        $status = "Aviso: O último backup já tem algum tempo. É recomendado fazer um novo backup";
                        $badge = "warning";
                    } else {
                        $progress = 30;
                        $status = "Perigo! Nenhum backup recente encontrado!É urgente fazer um novo backup";
                        $badge = "danger";
                    }
                } else {
                    $progress = 0;
                    $status = "Nenhum backup encontrado!";
                    $badge = "danger";
                }

                // Exibir o status e barra de progresso
                echo "<span class='badge bg-$badge p-2 mb-3'>$status</span>";
                ?>

                <!-- Barra de progresso do status -->
                <div class="progress w-75">
                    <div class="progress-bar bg-<?php echo $badge; ?>" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo $progress; ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Histórico de Ações -->
    <div class="col-lg-6 d-flex align-items-stretch">
        <div class="card mb-4 w-100">
            <div class="card-header">
                <h3 class="card-title">Histórico de Ações</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php
                    // Conectar à base de dados novamente
                    $conn = new mysqli('localhost', 'root', '', 'SyncRide');

                    // Buscar os últimos 10 logs
                    $sql = "SELECT Action, date FROM Logs ORDER BY date DESC LIMIT 10";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo '<li class="list-group-item">' . htmlspecialchars($row['Action']) . ' em ' . date("d/m/Y H:i", strtotime($row['date'])) . '</li>';
                        }
                    } else {
                        echo '<li class="list-group-item">Nenhum log encontrado.</li>';
                    }

                    $conn->close();
                    ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!--end::Row-->

<script>
document.addEventListener("DOMContentLoaded", function () {
    const ctx = document.getElementById('storage-status-chart').getContext('2d');
    const storageChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Utilizado', 'Livre', 'Reservado'],
            datasets: [{
                data: [65, 25, 10], // Exemplos de valores (%)
                backgroundColor: ['#ff6384', '#36a2eb', '#ffcd56'],
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    document.getElementById("refresh-storage").addEventListener("click", function () {
        alert("Atualização do armazenamento em breve!");
    });
});
</script>


<!-- Incluir o CSS do Bootstrap Icons (para ícones) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">

<script>

  // Ações dos botões
  document.getElementById('backup-btn').addEventListener('click', function () {
    fetch('backup.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro ao gerar o backup.');
            }
            return response.blob();
        })
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'backup.sql';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        })
        .catch(error => {
            alert(error.message);
        });
});


  document.getElementById('delete-db-btn').addEventListener('click', function() {
    if (confirm('Tem certeza de que deseja eliminar a database?')) {
      alert('Database eliminada com sucesso!');
      // Função para eliminar a database
    }
  });

  document.getElementById('clear-data-btn').addEventListener('click', function() {
    if (confirm('Tem certeza de que deseja limpar todos os dados?')) {
      alert('Dados limpos com sucesso!');
      // Função para limpar os dados
    }
  });

  document.getElementById('view-log-btn').addEventListener('click', function() {
    alert('Exibindo logs de ações...');
    // Função para exibir os logs
  });
</script>

  



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
    
    <!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
<!-- jQuery (Toastr depende do jQuery) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

  </body>
  <!--end::Body-->
</html>
