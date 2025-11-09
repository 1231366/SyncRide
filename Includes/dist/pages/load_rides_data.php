<?php
session_start();
require __DIR__ . '/../../../Auth/dbconfig.php';

// Apenas admins podem aceder a estes dados
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    echo json_encode(['data' => []]); // Retorna dados vazios se não for admin
    exit();
}

// Filtro vindo do AJAX
$status = $_GET['status'] ?? 'pending'; // 'pending' por defeito

$sql = "";
$params = [];

if ($status === 'pending') {
    // Viagens POR ATRIBUIR (ainda não têm associação em Services_Rides)
    $sql = "SELECT 
                s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, 
                s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber, 
                s.NomeCliente, s.ClientNumber, s.serviceType,
                NULL AS driverName
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            WHERE sr.UserID IS NULL
            ORDER BY s.serviceDate, s.serviceStartTime";
} elseif ($status === 'assigned') {
    // Viagens ATRIBUÍDAS (têm associação E um condutor)
    $sql = "SELECT 
                s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, 
                s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber, 
                s.NomeCliente, s.ClientNumber, s.serviceType,
                u.name AS driverName
            FROM Services s
            INNER JOIN Services_Rides sr ON s.ID = sr.RideID
            INNER JOIN Users u ON sr.UserID = u.ID
            ORDER BY s.serviceDate, s.serviceStartTime";
} else {
    // TODAS as viagens
    $sql = "SELECT 
                s.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, 
                s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber, 
                s.NomeCliente, s.ClientNumber, s.serviceType,
                u.name AS driverName
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            LEFT JOIN Users u ON sr.UserID = u.ID
            ORDER BY s.serviceDate, s.serviceStartTime";
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $viagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($viagens as $viagem) {
        
        // 1. Coluna Data & Hora
        $data_hora = htmlspecialchars($viagem['serviceDate'] . ' ' . substr($viagem['serviceStartTime'], 0, 5));

        // 2. Coluna Condutor
        if ($viagem['driverName']) {
            $condutor_html = '<span class="badge text-bg-success">' . htmlspecialchars($viagem['driverName']) . '</span>';
        } else {
            $condutor_html = '<span class="badge bg-secondary">N.A</span>';
        }

        // 3. Coluna Tipo
        $tipo_badge = $viagem['serviceType'] == 1 ? 'bg-dark' : 'bg-warning';
        $tipo_texto = $viagem['serviceType'] == 1 ? 'Private' : 'Shared';
        $tipo_html = '<span class="badge ' . $tipo_badge . '" 
                           style="cursor:pointer;" 
                           data-bs-toggle="tooltip" title="Mudar Tipo de Viagem"
                           data-bs-target="#changeTripTypeModal" 
                           onclick="changeTripType(' . $viagem['ID'] . ', ' . $viagem['serviceType'] . ')">'
                     . $tipo_texto . 
                     '</span>';

        // 4. Coluna Ações (Botões)
        $onclick_details = sprintf(
            "editTravel(%d, '%sT%s', '%s', '%s', '%s', %d, %d, '%s', '%s', '%s')",
            $viagem['ID'],
            $viagem['serviceDate'],
            substr($viagem['serviceStartTime'], 0, 5),
            htmlspecialchars(addslashes($viagem['driverName'] ?? 'N.A'), ENT_QUOTES),
            htmlspecialchars(addslashes($viagem['serviceStartPoint']), ENT_QUOTES),
            htmlspecialchars(addslashes($viagem['serviceTargetPoint']), ENT_QUOTES),
            $viagem['paxADT'],
            $viagem['paxCHD'],
            htmlspecialchars(addslashes($viagem['FlightNumber']), ENT_QUOTES),
            htmlspecialchars(addslashes($viagem['NomeCliente']), ENT_QUOTES),
            htmlspecialchars(addslashes($viagem['ClientNumber']), ENT_QUOTES)
        );
        
        $onclick_delete_name = htmlspecialchars(addslashes($viagem['serviceStartPoint'] . ' - ' . $viagem['serviceTargetPoint']), ENT_QUOTES);

        // ===================================
        // BOTÕES LINDISSIMOS
        // ===================================
        $acoes_html = '<div class="btn-group-sm d-flex justify-content-center">';
        
        // Botão Atribuir / Mudar Condutor
        $btn_atribuir_cor = $viagem['driverName'] ? 'btn-info' : 'btn-primary';
        $btn_atribuir_titulo = $viagem['driverName'] ? 'Mudar Condutor' : 'Atribuir Condutor';
        $btn_atribuir_icone = $viagem['driverName'] ? 'bi-person-check-fill' : 'bi-person-plus-fill';
        $acoes_html .= '<a href="#" class="btn ' . $btn_atribuir_cor . ' rounded-circle" 
                           data-bs-toggle="tooltip" title="' . $btn_atribuir_titulo . '"
                           data-bs-target="#atribuirCondutorModal" 
                           onclick="event.preventDefault(); setViagemId(' . $viagem['ID'] . '); var myModal = new bootstrap.Modal(document.getElementById(\'atribuirCondutorModal\')); myModal.show();">
                           <i class="bi ' . $btn_atribuir_icone . '"></i>
                        </a>';

        // Botão Detalhes/Editar
        $acoes_html .= '<a href="#" class="btn btn-warning rounded-circle text-dark" 
                           data-bs-toggle="tooltip" title="Ver Detalhes / Editar"
                           data-bs-target="#editModal" 
                           onclick="event.preventDefault(); ' . $onclick_details . '; var myModal = new bootstrap.Modal(document.getElementById(\'editModal\')); myModal.show();">
                           <i class="bi bi-pencil-fill"></i>
                        </a>';
        
        // Botão Apagar
        $acoes_html .= '<a href="#" class_name="btn btn-danger rounded-circle" 
                           data-bs-toggle="tooltip" title="Apagar Viagem"
                           data-bs-target="#deleteTripModal" 
                           onclick="event.preventDefault(); setDeleteTrip(' . $viagem['ID'] . ', \'' . $onclick_delete_name . '\'); var myModal = new bootstrap.Modal(document.getElementById(\'deleteTripModal\')); myModal.show();">
                           <i class="bi bi-trash3-fill"></i>
                        </a>';
        
        $acoes_html .= '</div>';
        // ===================================
        // FIM DOS BOTÕES
        // ===================================


        // Adiciona a linha formatada para o DataTables
        $data[] = [
            'id' => $viagem['ID'],
            'data_hora' => $data_hora,
            'condutor' => $condutor_html,
            'recolha' => htmlspecialchars($viagem['serviceStartPoint']),
            'entrega' => htmlspecialchars($viagem['serviceTargetPoint']),
            'tipo' => $tipo_html,
            'acoes' => $acoes_html
        ];
    }

    // Retorna o JSON formatado que o DataTables espera
    echo json_encode(['data' => $data]);

} catch (PDOException $e) {
    // Em caso de erro de SQL
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
?>