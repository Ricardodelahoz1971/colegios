<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
proteccion_extrema();
require_once '../db.php';
require_once '../auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new Exception("Método no permitido");
    
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0;
    $dia = filter_input(INPUT_POST, 'dia', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $hora = filter_input(INPUT_POST, 'hora', FILTER_VALIDATE_INT) ?? 0;
    $evento = filter_input(INPUT_POST, 'evento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $evento = trim($evento);
    
    if (!$curso_id || !$dia || !$hora) throw new Exception("Parámetros incompletos");
    
    $stmt_del = $db->prepare("DELETE FROM khronos_horarios WHERE curso_id = ? AND dia_semana = ? AND hora_numero = ?");
    $stmt_del->execute([$curso_id, $dia, $hora]);
    
    if (!empty($evento)) {
        $stmt_ins = $db->prepare("INSERT INTO khronos_horarios (curso_id, dia_semana, hora_numero, evento_nombre, especialidad_id, docente_id) VALUES (?, ?, ?, ?, 0, 0)");
        $stmt_ins->execute([$curso_id, $dia, $hora, $evento]);
        echo json_encode(['status' => 'success', 'message' => 'Evento guardado correctamente']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Slot despejado']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}