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

$docente_id = (int)($_GET['docente_id'] ?? 0);
$dia = $_GET['dia'] ?? '';
$hora = (int)($_GET['hora'] ?? 0);
$curso_act = (int)($_GET['curso_id'] ?? 0);

if (!$docente_id || !$dia || !$hora) {
    echo json_encode(['existe' => false]);
    exit();
}

$stmt = $db->prepare("SELECT c.nombre_curso 
                      FROM khronos_horarios h
                      JOIN cursos c ON h.curso_id = c.id
                      WHERE h.docente_id = :did 
                      AND h.dia_semana = :dia 
                      AND h.hora_numero = :hora 
                      AND h.curso_id != :curso_act");
    $stmt->bindValue(':did', $docente_id, PDO::PARAM_INT);
    $stmt->bindValue(':dia', $dia, PDO::PARAM_STR);
    $stmt->bindValue(':hora', $hora, PDO::PARAM_INT);
    $stmt->bindValue(':curso_act', $curso_act, PDO::PARAM_INT);

    $stmt->execute();
    $res = $stmt->fetch(PDO::FETCH_ASSOC);

if ($res) {
    echo json_encode(['existe' => true, 'curso' => $res['nombre_curso']]);
} else {
    echo json_encode(['existe' => false]);
}
?>

