<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
    session_write_close();

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: Inicie sesión.']);
    exit();
}

$curso_id = (int)($_GET['curso_id'] ?? 0);
$especialidad_id = (int)($_GET['especialidad_id'] ?? 0);
$dia = $_GET['dia'] ?? '';

// Cargar límite desde ajustes
$stmt_fatiga = $db->prepare("SELECT valor FROM ajustes_estetica WHERE clave = 'khronos_max_diario_materia'");
$stmt_fatiga->execute();
$max_diario = (int)$stmt_fatiga->fetchColumn() ?: 2;

if (!$curso_id || !$especialidad_id || !$dia) {
    echo json_encode(['limite_alcanzado' => false]);
    exit();
}

$stmt = $db->prepare("SELECT COUNT(*) FROM khronos_horarios 
                      WHERE curso_id = :cid 
                      AND especialidad_id = :eid 
                      AND dia_semana = :dia");
$stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
$stmt->bindValue(':eid', $especialidad_id, PDO::PARAM_INT);
$stmt->bindValue(':dia', $dia, PDO::PARAM_STR);

$stmt->execute();
$actual = (int)$stmt->fetchColumn();

if ($actual >= $max_diario) {
    echo json_encode(['limite_alcanzado' => true, 'max' => $max_diario, 'actual' => $actual]);
} else {
    echo json_encode(['limite_alcanzado' => false]);
}
?>

