<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('cursos')) {
        throw new Exception("Acceso denegado: Rango académico insuficiente.");
    }

    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $nombre = limpiar_texto_utf8(filter_input(INPUT_POST, 'nombre_curso', FILTER_DEFAULT) ?? '');
    $tutor = filter_input(INPUT_POST, 'tutor_id', FILTER_VALIDATE_INT);
    $jornada = limpiar_texto_utf8(filter_input(INPUT_POST, 'jornada', FILTER_DEFAULT) ?? 'Mañana');

    if (empty($id) || empty($nombre)) {
        throw new Exception("Datos incompletos para actualizar el aula.");
    }

    $nivel_id = 0;
    if (preg_match('/^\d+/', $nombre, $matches)) {
        $nivel_id = (int)$matches[0];
    } else {
        $nombre_minuscula = mb_strtolower($nombre);
        $mapa = [
            'primer' => 1, 'segund' => 2, 'tercer' => 3, 'cuart' => 4, 'quint' => 5,
            'sext' => 6, 'septim' => 7, 'octav' => 8, 'noven' => 9, 'decim' => 10, 'once' => 11
        ];
        foreach ($mapa as $clave => $nivel) {
            if (strpos($nombre_minuscula, $clave) !== false) {
                $nivel_id = $nivel;
                break;
            }
        }
    }

    $stmt_check = $db->prepare('SELECT COUNT(*) FROM cursos WHERE nombre_curso = :nom AND jornada = :jor AND id != :id');
    $stmt_check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt_check->bindValue(':jor', $jornada, PDO::PARAM_STR);
    $stmt_check->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_check->execute();
    if ((int)$stmt_check->fetchColumn() > 0) {
        throw new Exception("Ya existe otro curso con ese nombre en la jornada " . $jornada . ".");
    }

    $stmt_current = $db->prepare('SELECT nombre_curso FROM cursos WHERE id = :id');
    $stmt_current->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt_current->execute();
    $current_name = $stmt_current->fetchColumn();

    $db->beginTransaction();

    if ($current_name && strcasecmp($current_name, $nombre) === 0 && $current_name !== $nombre) {
        $temp_name = $nombre . '_temp_' . uniqid();
        $stmt_temp = $db->prepare('UPDATE cursos SET nombre_curso = :temp WHERE id = :id');
        $stmt_temp->bindValue(':temp', $temp_name, PDO::PARAM_STR);
        $stmt_temp->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt_temp->execute();
    }

    $stmt = $db->prepare('UPDATE cursos SET nombre_curso = :nom, tutor_id = :tut, nivel_id = :niv, jornada = :jor WHERE id = :id');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':niv', $nivel_id, PDO::PARAM_INT);
    $stmt->bindValue(':jor', $jornada, PDO::PARAM_STR);

    if ($tutor === false || $tutor === null || $tutor === '') {
        $stmt->bindValue(':tut', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':tut', $tutor, PDO::PARAM_INT);
    }
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => '¡Configuración de aula actualizada correctamente!']);
    } else {
        $db->rollBack();
        throw new Exception("Fallo en la actualización de la bóveda.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>