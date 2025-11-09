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
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Painel de Condutor</title>
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
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/apexcharts@3.37.1/dist/apexcharts.css"
      integrity="sha256-4MX+61mt9NVvvuPjUWdUdyfZfxSB1/Rf9WtqRHgG5S0="
      crossorigin="anonymous"
    />
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/jsvectormap@1.5.3/dist/css/jsvectormap.min.css"
      integrity="sha256-+uGLJmmTKOqBr+2E6KDYs/NRsHxSkONXFHUL0fy2O/4="
      crossorigin="anonymous"
    />
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
            <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Home</a></li>
            <li class="nav-item d-none d-md-block"><a href="#" class="nav-link">Contact</a></li>
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
                    <?php echo $_SESSION['name']; ?> - <?php echo $_SESSION['role'] == 1 ? 'Admin' : 'Condutor'; ?>
                  </p>
                </li>
                <li class="user-footer">
                  <a href="driverstats.php" class="btn btn-default btn-flat">Estatísticas</a>
                  <a href="logout.php" class="btn btn-default btn-flat float-end">Sign out</a>
                </li>
                </ul>
            </li>
            </ul>
          </div>
        </nav>
      <?php
        // Bloco PHP para buscar estatísticas
        $viagensHoje = 0;
        $viagensSemana = 0;

        if (isset($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id']; 
            try {
                // Viagens para hoje
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
                $stmt->execute([$userId]);
                $viagensHoje = $stmt->fetchColumn();

                // Viagens para esta semana
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE YEARWEEK(serviceDate, 1) = YEARWEEK(CURDATE(), 1) AND ID IN (SELECT RideID FROM Services_Rides WHERE UserID = ?)");
                $stmt->execute([$userId]);
                $viagensSemana = $stmt->fetchColumn();
            } catch (PDOException $e) {
                // Erro não é fatal, apenas mostra 0
            }
        }
      ?>
      <main class="app-main">
        <div class="app-content-header">
          <div class="container-fluid">
            <div class="row">
              <div class="col-sm-6"><h3 class="mb-0">Painel de Condutor</h3></div>
              <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
              </div>
            </div>
            </div>
          </div>
        <div class="app-content">
          <div class="container-fluid">
            <div class="row">
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
              </div>
            <div class="card mb-4 shadow-lg border-0 rounded-4">
                <div class="card-header text-center bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-top-4">
                    <h3 class="card-title fw-bold mb-0" style="display: flex; justify-content: center; align-items: center; text-align: center;">
                        🚖 Minhas Viagens
                    </h3>
                </div>

                <div class="d-flex justify-content-center gap-3 p-3">
                    <button class="btn btn-outline-light btn-sm rounded-pill px-4 filter-btn" data-filter="yesterday">⬅ Ontem</button>
                    <button class="btn btn-light btn-sm rounded-pill px-4 filter-btn active" data-filter="today">📅 Hoje</button>
                    <button class="btn btn-outline-light btn-sm rounded-pill px-4 filter-btn" data-filter="tomorrow">➡ Amanhã</button>
                </div>

                <div class="card-body p-4">
                <div class="list-group">
                    <p class="text-center text-muted">A carregar viagens...</p>
                </div>
            </div>


            <div class="modal fade" id="detailsModal" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Viagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                  </div>
                  <div class="modal-body">
                    <ul class="list-group">
                      <li class="list-group-item"><strong>📅 Data:</strong> <span id="modalDate"></span></li>
                      <li class="list-group-item"><strong>🕒 Hora:</strong> <span id="modalTime"></span></li>
                      <li class="list-group-item"><strong>📍 Local de Recolha:</strong> <span id="modalPickup"></span></li>
                      <li class="list-group-item"><strong>🚗 Local de Entrega:</strong> <span id="modalDropoff"></span></li>
                      <li class="list-group-item"><strong>👥 Passageiros:</strong> <span id="modalPassengers"></span></li>
                      <li class="list-group-item"><strong>✈️ Voo:</strong> <span id="modalFlight"></span></li>
                      <li class="list-group-item"><strong>🧍 Adultos:</strong> <span id="modalADT"></span></li>
                      <li class="list-group-item"><strong>🧒 Crianças:</strong> <span id="modalCHD"></span></li>
                      <li class="list-group-item"><strong>👤 Cliente:</strong> <span id="modalClient"></span></li>
                      <li class="list-group-item"><strong>📞 Nº Cliente:</strong> <span id="modalClientNumber"></span></li>
                    </ul>

                    <div class="d-grid gap-2">
                        <a href="#" id="wazePickupButton" class="btn btn-primary w-100 mt-3" target="_blank">
                            🚀 Navegar para Recolha (Waze)
                        </a>
                        <a href="#" id="wazeDropoffButton" class="btn btn-success w-100" target="_blank">
                            🏁 Iniciar Viagem para Entrega (Waze)
                        </a>
                    
                        <button class="btn btn-danger w-100 mt-2" id="uploadNoShow">
                            📷 Reportar No Show
                        </button>
                    </div>

                    <div id="cameraContainer" class="mt-3" style="display: none; text-align: center; border: 1px dashed #ccc; padding: 10px; border-radius: 8px;">
                      <p class="text-muted">Aponte a câmara para provar o "No Show"</p>
                      <video id="cameraStream" width="100%" autoplay playsinline style="border-radius: 8px;"></video>
                      <canvas id="photoCanvas" style="display: none; width: 100%; border-radius: 8px;"></canvas>
                      <button class="btn btn-success mt-2" id="capturePhoto">📸 Capturar Foto</button>
                    </div>
                    
                  </div>
                </div>
              </div>
            </div>

            <script>
              document.addEventListener("DOMContentLoaded", function () {
                
                // --- LÓGICA DE FILTRAGEM DE DATAS ---
                
                function formatDate(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, "0");
                    const day = String(date.getDate()).padStart(2, "0");
                    return `${year}-${month}-${day}`;
                }

                function filterTrips(dateFilter) {
                    const today = new Date();
                    const yesterday = new Date(today);
                    const tomorrow = new Date(today);

                    yesterday.setDate(today.getDate() - 1);
                    tomorrow.setDate(today.getDate() + 1);

                    const todayFormatted = formatDate(today);
                    const yesterdayFormatted = formatDate(yesterday);
                    const tomorrowFormatted = formatDate(tomorrow);

                    let filteredTrips = [];
                    
                    if (dateFilter === "yesterday") {
                        filteredTrips = viagens.filter(viagem => viagem.serviceDate === yesterdayFormatted);
                    } else if (dateFilter === "today") {
                        filteredTrips = viagens.filter(viagem => viagem.serviceDate === todayFormatted);
                    } else if (dateFilter === "tomorrow") {
                        filteredTrips = viagens.filter(viagem => viagem.serviceDate === tomorrowFormatted);
                    }

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
                      let borderColor = "#ffc107"; // Shared (serviceType = 0)
                      let buttonColor = "btn-outline-warning";

                      if (viagem.serviceType === "1" || viagem.serviceType === 1) {
                        borderColor = "#007bff"; // Private (serviceType = 1)
                        buttonColor = "btn-outline-primary";
                      }

                      const viagemHtml = `
                        <div class="list-group-item p-2 rounded-3 mb-3 travel-card"
                             style="background: #ffffff; border-left: 6px solid ${borderColor}; 
                                    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s, box-shadow 0.3s;">
                          <h5 class="fw-bold text-primary mb-1" style="font-size: 14px;">🕒 ${viagem.serviceStartTime.substr(0, 5)}</h5>
                          <p class="mb-1" style="font-size: 12px;"><strong>Rota:</strong> ${viagem.serviceStartPoint} → ${viagem.serviceTargetPoint}</p>
                          <div class="text-center mt-2">
                            <button class="btn ${buttonColor} btn-sm open-modal"
                                    data-bs-toggle="modal" data-bs-target="#detailsModal"
                                    data-id="${viagem.ServiceID}"
                                    data-start="${viagem.serviceStartPoint}"
                                    data-end="${viagem.serviceTargetPoint}"
                                    data-time="${viagem.serviceStartTime.substr(0, 5)}"
                                    data-date="${viagem.serviceDate}"
                                    data-passengers="${(parseInt(viagem.paxADT) || 0) + (parseInt(viagem.paxCHD) || 0)}"
                                    data-paxadt="${viagem.paxADT || '0'}"
                                    data-paxchd="${viagem.paxCHD || '0'}"
                                    data-flight="${viagem.FlightNumber || 'N/A'}"
                                    data-client="${viagem.NomeCliente || 'N/A'}"
                                    data-clientnumber="${viagem.ClientNumber || 'N/A'}"
                            >
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
                    document.querySelectorAll(".filter-btn").forEach(btn => btn.classList.remove("active"));
                    this.classList.add("active");
                    filterTrips(this.getAttribute("data-filter"));
                  });
                });

                // --- DELEGAÇÃO DE EVENTOS PARA O MODAL ---
                const listGroup = document.querySelector(".list-group");
                const detailsModal = document.getElementById('detailsModal');

                listGroup.addEventListener("click", function(event) {
                    const button = event.target.closest(".open-modal");
                    if (!button) return;

                    // Limpar estado da câmara de "no shows" anteriores
                    stopCameraStream();
                    document.getElementById('cameraContainer').style.display = 'none';
                    document.getElementById('photoCanvas').style.display = 'none';
                    document.getElementById('cameraStream').style.display = 'block';

                    // Pegar os dados do botão
                    const id = button.getAttribute("data-id");
                    const start = button.getAttribute("data-start");
                    const end = button.getAttribute("data-end");
                    const time = button.getAttribute("data-time");
                    const date = button.getAttribute("data-date");
                    const passengers = button.getAttribute("data-passengers");
                    const paxADT = button.getAttribute("data-paxadt");
                    const paxCHD = button.getAttribute("data-paxchd");
                    const flight = button.getAttribute("data-flight");
                    const client = button.getAttribute("data-client");
                    const clientNumber = button.getAttribute("data-clientnumber");

                    // Preencher os campos do modal
                    detailsModal.querySelector("#modalDate").textContent = date;
                    detailsModal.querySelector("#modalTime").textContent = time;
                    detailsModal.querySelector("#modalPickup").textContent = start;
                    detailsModal.querySelector("#modalDropoff").textContent = end;
                    detailsModal.querySelector("#modalPassengers").textContent = passengers;
                    detailsModal.querySelector("#modalFlight").textContent = flight;
                    detailsModal.querySelector("#modalADT").textContent = paxADT;
                    detailsModal.querySelector("#modalCHD").textContent = paxCHD;
                    detailsModal.querySelector("#modalClient").textContent = client;
                    detailsModal.querySelector("#modalClientNumber").textContent = clientNumber;
                    
                    // --- LÓGICA DO WAZE ---
                    const wazePickupButton = detailsModal.querySelector("#wazePickupButton");
                    const wazeDropoffButton = detailsModal.querySelector("#wazeDropoffButton");
                    
                    const encodedStart = encodeURIComponent(start);
                    const encodedEnd = encodeURIComponent(end);
                    
                    // Botão 1: Navegar para a RECOLHA
                    wazePickupButton.href = `https://waze.com/ul?q=${encodedStart}&navigate=yes`;
                    
                    // Botão 2: Navegar para a ENTREGA
                    wazeDropoffButton.href = `https://waze.com/ul?q=${encodedEnd}&navigate=yes`;
                    
                    // Armazena o ID no botão "No Show"
                    detailsModal.querySelector("#uploadNoShow").setAttribute("data-trip-id", id);
                });

              });
            </script>
            <script>
                const cameraContainer = document.getElementById('cameraContainer');
                const video = document.getElementById('cameraStream');
                const canvas = document.getElementById('photoCanvas');
                const captureButton = document.getElementById('capturePhoto');
                const noShowButton = document.getElementById('uploadNoShow');
                let stream = null; // Para guardar a stream da câmara

                // Função para parar a câmara
                function stopCameraStream() {
                    if (stream) {
                        stream.getTracks().forEach(track => track.stop());
                        stream = null;
                    }
                }

                // 1. Quando clica em "Reportar No Show"
                noShowButton.addEventListener('click', async () => {
                    // Se a câmara já estiver ligada, desliga-a
                    if (stream) {
                        stopCameraStream();
                        cameraContainer.style.display = 'none';
                        return;
                    }

                    // Limpa o canvas de fotos antigas
                    canvas.style.display = 'none';
                    video.style.display = 'block';

                    try {
                        // Pedir permissão para a câmara TRASEIRA (environment)
                        stream = await navigator.mediaDevices.getUserMedia({ 
                            video: { 
                                facingMode: 'environment' 
                            } 
                        });
                        
                        video.srcObject = stream;
                        cameraContainer.style.display = 'block';
                    } catch (err) {
                        console.error("Erro ao aceder à câmara: ", err);
                        // Se falhar (ex: no PC), tenta a câmara frontal
                        try {
                             stream = await navigator.mediaDevices.getUserMedia({ video: true });
                             video.srcObject = stream;
                             cameraContainer.style.display = 'block';
                        } catch (err2) {
                            console.error("Erro ao aceder a qualquer câmara: ", err2);
                            alert("Não foi possível aceder a nenhuma câmara.");
                        }
                    }
                });

                // 2. Quando clica em "Capturar Foto"
                captureButton.addEventListener('click', () => {
                    if (!stream) return; // Não faz nada se a câmara não estiver ligada

                    // Desenha a imagem da câmara no canvas
                    const context = canvas.getContext('2d');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    context.drawImage(video, 0, 0, canvas.width, canvas.height);

                    // Mostra a foto (canvas) e esconde o vídeo
                    canvas.style.display = 'block';
                    video.style.display = 'none';

                    // Para a stream da câmara
                    stopCameraStream();

                    // ===================================
                    // LÓGICA DE UPLOAD (AGORA FUNCIONA)
                    // ===================================
                    const imageDataUrl = canvas.toDataURL('image/jpeg');
                    const tripId = noShowButton.getAttribute('data-trip-id');
                    
                    // Mudar o botão para "A enviar..."
                    captureButton.disabled = true;
                    captureButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> A enviar...';

                    fetch('upload_no_show.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            trip_id: tripId,
                            image_data: imageDataUrl
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            alert('Foto de "No Show" enviada com sucesso!');
                            // Fechar o modal
                            var myModal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
                            myModal.hide();
                        } else {
                            alert('Erro ao enviar a foto: ' + data.message);
                        }
                    })
                    .catch(err => {
                        console.error('Erro no upload: ', err);
                        alert('Erro de rede ao enviar a foto.');
                    })
                    .finally(() => {
                        // Restaurar o botão
                        captureButton.disabled = false;
                        captureButton.innerHTML = '📸 Capturar Foto';
                    });
                });
                
                // Quando o modal é fechado, garantir que a câmara desliga
                document.getElementById('detailsModal').addEventListener('hidden.bs.modal', () => {
                    stopCameraStream();
                    cameraContainer.style.display = 'none';
                });

            </script>
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

            <script>
              const filterButtons = document.querySelectorAll('.filter-btn');
              filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                  filterButtons.forEach(btn => btn.classList.remove('active'));
                  this.classList.add('active');
                });
              });
            </script>
               
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
    </body>
  </html>