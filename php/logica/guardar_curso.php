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

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre = limpiar_texto_utf8($input['nombre_curso'] ?? filter_input(INPUT_POST, 'nombre_curso', FILTER_DEFAULT) ?? '');
    $tutor_raw = $input['tutor_id'] ?? filter_input(INPUT_POST, 'tutor_id', FILTER_VALIDATE_INT) ?? null;
    $tutor = ($tutor_raw !== null && $tutor_raw !== '') ? filter_var($tutor_raw, FILTER_VALIDATE_INT) : null;
    $jornada = limpiar_texto_utf8($input['jornada'] ?? filter_input(INPUT_POST, 'jornada', FILTER_DEFAULT) ?? 'Mañana');

    if (empty($nombre)) {
        throw new Exception("El nombre del aula/curso es obligatorio.");
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

    $stmt_check = $db->prepare('SELECT COUNT(*) FROM cursos WHERE nombre_curso = :nom AND jornada = :jor');
    $stmt_check->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt_check->bindValue(':jor', $jornada, PDO::PARAM_STR);
    $stmt_check->execute();
    if ((int)$stmt_check->fetchColumn() > 0) {
        throw new Exception("Ya existe un curso registrado con ese nombre en la jornada " . $jornada . ".");
    }

    $stmt = $db->prepare('INSERT INTO cursos (nombre_curso, tutor_id, nivel_id, jornada) VALUES (:nom, :tut, :niv, :jor)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':niv', $nivel_id, PDO::PARAM_INT);
    $stmt->bindValue(':jor', $jornada, PDO::PARAM_STR);
    
    if ($tutor === false || $tutor === null || $tutor === '') {
        $stmt->bindValue(':tut', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':tut', $tutor, PDO::PARAM_INT);
    }

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => '¡Aula/Curso configurado correctamente!']);
    } else {
        throw new Exception("Fallo al inscribir en la base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>