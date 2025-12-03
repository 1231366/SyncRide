<?php
session_start();
require __DIR__ . '/../../../Auth/dbconfig.php';

// Apenas Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../../../index.php");
    exit();
}

// --- APAGAR ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM Vehicles WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: manageFleet.php?success=deleted");
    exit();
}

// --- GUARDAR / EDITAR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['vehicle_id'] ?? null;
    $plate = strtoupper(trim($_POST['license_plate']));
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $km = (int)$_POST['current_km'];
    $review_km = (int)$_POST['next_review_km'];
    $inspection = $_POST['inspection_date'];
    $insurance = $_POST['insurance_date'];
    $status = $_POST['status'];

    try {
        if ($id) {
            // Editar
            $sql = "UPDATE Vehicles SET license_plate=?, brand=?, model=?, current_km=?, next_review_km=?, inspection_date=?, insurance_date=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$plate, $brand, $model, $km, $review_km, $inspection, $insurance, $status, $id]);
            $msg = "updated";
        } else {
            // Novo
            $sql = "INSERT INTO Vehicles (license_plate, brand, model, current_km, next_review_km, inspection_date, insurance_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$plate, $brand, $model, $km, $review_km, $inspection, $insurance, $status]);
            $msg = "created";
        }
        header("Location: manageFleet.php?success=$msg");
        exit(); // Importante para evitar ecrã branco
    } catch (PDOException $e) {
        die("Erro: " . $e->getMessage());
    }
}
?>