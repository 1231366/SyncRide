<?php
require __DIR__ . '/../../auth/dbconfig.php';

// Inicializamos as variáveis
$idsParaApagar = [];
// Captura a aba de onde veio (prioridade para POST, depois GET, padrão 'today')
$fromTab = $_POST['from_tab'] ?? ($_GET['from_tab'] ?? 'today');

// 1. VERIFICAÇÃO DE DADOS (SUPORTE A SINGLE E BULK)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ids_bulk'])) {
    // Caso venha do Bulk Delete (JSON)
    $idsParaApagar = json_decode($_POST['ids_bulk'], true);
} elseif (isset($_GET['id'])) {
    // Caso venha do botão individual (ID único)
    $idsParaApagar = [$_GET['id']];
}

// 2. EXECUÇÃO DA LÓGICA
if (!empty($idsParaApagar)) {
    try {
        // Iniciar transação para garantir integridade total
        $pdo->beginTransaction();

        // Criamos os placeholders (?,?,?) para a cláusula IN
        $placeholders = implode(',', array_fill(0, count($idsParaApagar), '?'));

        // Primeiro, remover associações na tabela Services_Rides
        $stmt1 = $pdo->prepare("DELETE FROM Services_Rides WHERE RideID IN ($placeholders)");
        $stmt1->execute($idsParaApagar);

        // Agora, apagar as viagens da tabela Services
        $stmt2 = $pdo->prepare("DELETE FROM Services WHERE ID IN ($placeholders)");
        $stmt2->execute($idsParaApagar);

        $pdo->commit();

        // Redirecionar mantendo a tab ativa e mensagem de sucesso
        header("Location: rides.php?success=ride_deleted&tab=" . urlencode($fromTab));
        exit();

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        // Redirecionar com erro mantendo a tab
        header("Location: rides.php?error=" . urlencode("Erro ao processar: " . $e->getMessage()) . "&tab=" . urlencode($fromTab));
        exit();
    }
} else {
    // Se não houver IDs, volta com erro
    header("Location: rides.php?error=" . urlencode("Nenhum item selecionado!") . "&tab=" . urlencode($fromTab));
    exit();
}
?>