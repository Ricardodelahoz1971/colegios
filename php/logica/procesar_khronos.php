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
proteccion_extrema();

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: Inicie sesión.']);
    exit();
}

if (!tienen_rol(['administrador', 'coordinador', 'rector'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado: Privilegios insuficientes.']);
    exit();
}

$accion = filter_input(INPUT_POST, 'accion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';

// HELPER PARA CALCULAR TIEMPOS BASADOS EN CONFIGURACIÓN (v9.2 PDO)
function getTiempos(PDO $db, int $hora_num, int $curso_id = 0): array {
    $jornada = 'Mañana';
    if ($curso_id > 0) {
        $stmt_j = $db->prepare("SELECT jornada FROM cursos WHERE id = ?");
        $stmt_j->execute([$curso_id]);
        $jornada = $stmt_j->fetchColumn() ?: 'Mañana';
    }

    $stmt_kh = $db->prepare("SELECT clave, valor FROM ajustes_estetica WHERE clave LIKE 'khronos%'");
    $stmt_kh->execute();
    $stmt = $stmt_kh;
    $cfg = []; 
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) $cfg[$r['clave']] = $r['valor'];

    $obtener_cfg = function($clave, $jornada) use ($cfg) {
        $suffix = strtolower(str_replace([' ', 'á', 'é', 'í', 'ó', 'ú'], ['', 'a', 'e', 'i', 'o', 'u'], $jornada));
        $clave_jornada = $clave . '_' . $suffix;
        if (isset($cfg[$clave_jornada]) && $cfg[$clave_jornada] !== '') {
            return $cfg[$clave_jornada];
        }
        return $cfg[$clave] ?? null;
    };
    
    $h_ini = $obtener_cfg('khronos_inicio', $jornada) ?? '07:00';
    $dur = (int)($obtener_cfg('khronos_duracion', $jornada) ?? 55);
    $d1h = (int)($obtener_cfg('khronos_descanso_h', $jornada) ?? 0);
    $d1m = (int)($obtener_cfg('khronos_descanso_m', $jornada) ?? 0);
    $d2h = (int)($obtener_cfg('khronos_descanso2_h', $jornada) ?? 0);
    $d2m = (int)($obtener_cfg('khronos_descanso2_m', $jornada) ?? 0);

    [$h, $m] = explode(':', $h_ini);
    $t_ini = '';
    $t_fin = '';
    for($i=1; $i<=$hora_num; $i++) {
        $t_ini = sprintf("%02d:%02d", $h, $m);
        $total = $m + $dur;
        $h += floor($total/60); $m = $total % 60;
        $t_fin = sprintf("%02d:%02d", $h, $m);
        if ($i == $d1h) { $total_d = $m + $d1m; $h += floor($total_d/60); $m = $total_d % 60; }
        if ($i == $d2h) { $total_d = $m + $d2m; $h += floor($total_d/60); $m = $total_d % 60; }
    }
    return [$t_ini, $t_fin];
}

switch ($accion) {
    case 'asignar':
    case 'mover':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
        $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0;
        $docente_id = filter_input(INPUT_POST, 'docente_id', FILTER_VALIDATE_INT) ?? 0;
        $especialidad_id = filter_input(INPUT_POST, 'especialidad_id', FILTER_VALIDATE_INT) ?? 0;
        $dia = filter_input(INPUT_POST, 'dia_semana', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $hora = filter_input(INPUT_POST, 'hora_numero', FILTER_VALIDATE_INT) ?? 0;
        [$ini, $fin] = getTiempos($db, $hora, $curso_id);

        if ($id > 0) {
            $stmt = $db->prepare("UPDATE khronos_horarios SET dia_semana = :dia, hora_numero = :hora, hora_inicio = :ini, hora_fin = :fin WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        } else {
            $stmt = $db->prepare("INSERT INTO khronos_horarios (curso_id, docente_id, especialidad_id, dia_semana, hora_numero, hora_inicio, hora_fin) VALUES (:cid, :did, :eid, :dia, :hora, :ini, :fin)");
            $stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
            $stmt->bindValue(':did', $docente_id, PDO::PARAM_INT);
            $stmt->bindValue(':eid', $especialidad_id, PDO::PARAM_INT);
        }
        $stmt->bindValue(':dia', $dia, PDO::PARAM_STR); 
        $stmt->bindValue(':hora', $hora, PDO::PARAM_INT);
        $stmt->bindValue(':ini', $ini, PDO::PARAM_STR); 
        $stmt->bindValue(':fin', $fin, PDO::PARAM_STR);
        echo json_encode(['status' => $stmt->execute() ? 'success' : 'error']);
        break;

    case 'swap':
        $id_a = filter_input(INPUT_POST, 'id_a', FILTER_VALIDATE_INT) ?? 0;
        $id_b = filter_input(INPUT_POST, 'id_b', FILTER_VALIDATE_INT) ?? 0;
        $dia_a = filter_input(INPUT_POST, 'dia_a', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $hora_a = filter_input(INPUT_POST, 'hora_a', FILTER_VALIDATE_INT) ?? 0;
        $dia_b = filter_input(INPUT_POST, 'dia_b', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $hora_b = filter_input(INPUT_POST, 'hora_b', FILTER_VALIDATE_INT) ?? 0;
        
        $stmt_c_a = $db->prepare("SELECT curso_id FROM khronos_horarios WHERE id = ?");
        $stmt_c_a->execute([$id_a]);
        $curso_id_a = (int)$stmt_c_a->fetchColumn();

        [$ini_a, $fin_a] = getTiempos($db, $hora_a, $curso_id_a);
        [$ini_b, $fin_b] = getTiempos($db, $hora_b, $curso_id_a);

        try {
            $db->beginTransaction();
            $stmt1 = $db->prepare("UPDATE khronos_horarios SET dia_semana = :dia, hora_numero = :hora, hora_inicio = :ini, hora_fin = :fin WHERE id = :id");
            $stmt1->execute([':dia' => $dia_a, ':hora' => $hora_a, ':ini' => $ini_a, ':fin' => $fin_a, ':id' => $id_a]);
            
            $stmt2 = $db->prepare("UPDATE khronos_horarios SET dia_semana = :dia, hora_numero = :hora, hora_inicio = :ini, hora_fin = :fin WHERE id = :id");
            $stmt2->execute([':dia' => $dia_b, ':hora' => $hora_b, ':ini' => $ini_b, ':fin' => $fin_b, ':id' => $id_b]);
            
            $db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'reemplazar':
        $id_eliminar = filter_input(INPUT_POST, 'id_eliminar', FILTER_VALIDATE_INT) ?? 0;
        $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0;
        $docente_id = filter_input(INPUT_POST, 'docente_id', FILTER_VALIDATE_INT) ?? 0;
        $especialidad_id = filter_input(INPUT_POST, 'especialidad_id', FILTER_VALIDATE_INT) ?? 0;
        $dia = filter_input(INPUT_POST, 'dia_semana', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        $hora = filter_input(INPUT_POST, 'hora_numero', FILTER_VALIDATE_INT) ?? 0;
        [$ini, $fin] = getTiempos($db, $hora, $curso_id);

        try {
            $db->beginTransaction();
            $db->prepare("DELETE FROM khronos_horarios WHERE id = :id")->execute([':id' => $id_eliminar]);
            
            $stmt = $db->prepare("INSERT INTO khronos_horarios (curso_id, docente_id, especialidad_id, dia_semana, hora_numero, hora_inicio, hora_fin) VALUES (:cid, :did, :eid, :dia, :hora, :ini, :fin)");
            $stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
            $stmt->bindValue(':did', $docente_id, PDO::PARAM_INT);
            $stmt->bindValue(':eid', $especialidad_id, PDO::PARAM_INT);
            $stmt->bindValue(':dia', $dia, PDO::PARAM_STR); 
            $stmt->bindValue(':hora', $hora, PDO::PARAM_INT);
            $stmt->bindValue(':ini', $ini, PDO::PARAM_STR); 
            $stmt->bindValue(':fin', $fin, PDO::PARAM_STR);
            $stmt->execute();
            
            $db->commit();
            echo json_encode(['status' => 'success']);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'eliminar':
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;
        $stmt = $db->prepare("DELETE FROM khronos_horarios WHERE id = :id");
        if ($stmt->execute([':id' => $id])) echo json_encode(['status' => 'success']);
        break;

    case 'limpiar':
        $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0;
        $stmt = $db->prepare("DELETE FROM khronos_horarios WHERE curso_id = :cid");
        if ($stmt->execute([':cid' => $curso_id])) echo json_encode(['status' => 'success']);
        break;
}
?>