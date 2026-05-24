<?php
require __DIR__ . '/../../auth/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obter todos os campos do formulário
    $viagemId = $_POST['edit_trip_id'] ?? null;
    $dataHora = $_POST['edit_departure_datetime'] ?? null;
    $localRecolha = $_POST['edit_origin'] ?? null;
    $localEntrega = $_POST['edit_destination'] ?? null;
    
    // Campos adicionais
    $paxADT = $_POST['edit_paxADT'] ?? 0;
    $paxCHD = $_POST['edit_paxCHD'] ?? 0;
    $flightNumber = $_POST['edit_flightNumber'] ?? null;
    $nomeCliente = $_POST['edit_clientName'] ?? null;
    $clientNumber = $_POST['edit_clientNumber'] ?? null;

    // --- CORREÇÃO DO PREÇO ---
    // 1. Recebe o valor (ou string vazia se não existir)
    $rawPrice = $_POST['edit_totalPrice'] ?? '';

    // 2. Substitui vírgula por ponto (ex: "10,50" vira "10.50")
    $priceClean = str_replace(',', '.', $rawPrice);

    // 3. Garante que é numérico. Se estiver vazio ou inválido, assume 0.
    if (!is_numeric($priceClean)) {
        $priceClean = 0;
    }
    // -------------------------

    if ($viagemId && $dataHora) {
        try {
            // Separar data e hora corretamente
            list($data, $hora) = explode('T', $dataHora); 
            $hora .= ":00"; // Adiciona segundos

            // SQL
            $sql = "UPDATE Services 
                    SET serviceDate = :data, 
                        serviceStartTime = :hora, 
                        serviceStartPoint = :startPoint, 
                        serviceTargetPoint = :targetPoint,
                        paxADT = :paxADT,
                        paxCHD = :paxCHD,
                        FlightNumber = :flightNumber,
                        NomeCliente = :nomeCliente,
                        ClientNumber = :clientNumber,
                        total_price = :totalPrice
                    WHERE ID = :rideID";

            $stmt = $pdo->prepare($sql);
            
            // Bind dos parâmetros
            $stmt->bindValue(':data', $data);
            $stmt->bindValue(':hora', $hora);
            $stmt->bindValue(':startPoint', $localRecolha);
            $stmt->bindValue(':targetPoint', $localEntrega);
            $stmt->bindValue(':rideID', $viagemId);
            $stmt->bindValue(':paxADT', $paxADT);
            $stmt->bindValue(':paxCHD', $paxCHD);
            $stmt->bindValue(':flightNumber', $flightNumber);
            $stmt->bindValue(':nomeCliente', $nomeCliente);
            $stmt->bindValue(':clientNumber', $clientNumber);
            
            // Bind do Preço Corrigido
            $stmt->bindValue(':totalPrice', $priceClean);
            
            $stmt->execute();

            // Redireciona de volta com sucesso
            header('Location: rides.php?success=rideUpdated');
            exit();

        } catch (PDOException $e) {
            header('Location: rides.php?success=false&message=' . urlencode('Erro ao atualizar viagem: ' . $e->getMessage()));
            exit();
        }
    } else {
        header('Location: rides.php?success=false&message=' . urlencode('Dados inválidos! Faltou o ID ou a Data/Hora.'));
        exit();
    }
} else {
    header('Location: rides.php?success=false&message=' . urlencode('Método não permitido!'));
    exit();
}
?>