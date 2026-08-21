<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
proteccion_extrema();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

if (!tiene_permiso('agenda')) {
    echo json_encode(['status' => 'error', 'message' => 'Sin permisos']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    
    $id = filter_var($input['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $curso_id = filter_var($input['curso_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $especialidad_id = filter_var($input['especialidad_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
    $titulo = trim(filter_var($input['titulo'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $desc = trim(filter_var($input['descripcion'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '');
    $fecha = filter_var($input['fecha_entrega'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? '';
    $usuario_id = $_SESSION['usuario_id'];
    session_write_close();

    if (!$curso_id || !$especialidad_id || empty($titulo) || empty($fecha)) {
        throw new Exception("Todos los campos marcados son obligatorios.");
    }

    require_once __DIR__ . '/helpers_centinela.php';
    $confirmado_fuerza = filter_var($input['confirmar_centinela'] ?? false, FILTER_VALIDATE_BOOLEAN) ?? false;

    if (!$confirmado_fuerza) {
        $colision = verificar_fecha_receso_fin_semana($db, $fecha);
        if ($colision['colision']) {
            $motivo = $colision['tipo'] === 'fin_semana' ? 'un fin de semana' : 'un periodo de receso escolar';
            echo json_encode([
                'status' => 'centinela_advertencia',
                'message' => "La fecha de entrega cae en {$motivo}. ¿Desea continuar de todas formas?"
            ]);
            exit;
        }

        if (verificar_tiempo_critico($fecha)) {
            echo json_encode([
                'status' => 'centinela_advertencia',
                'message' => "La diferencia entre la fecha actual y la fecha de entrega es inferior a 12 horas. ¿Desea continuar de todas formas?"
            ]);
            exit;
        }
    }

    if ($id > 0) {
        $sql = "UPDATE agenda_escolar SET curso_id = :cid, especialidad_id = :eid, titulo = :tit, descripcion = :des, fecha_entrega = :fec 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    } else {
        $final_docente_id = $usuario_id;
        
        $sql = "INSERT INTO agenda_escolar (curso_id, docente_id, especialidad_id, titulo, descripcion, fecha_entrega) 
                VALUES (:cid, :did, :eid, :tit, :des, :fec)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':did', $final_docente_id, PDO::PARAM_INT);
    }

    $stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt->bindValue(':eid', $especialidad_id, PDO::PARAM_INT);
    $stmt->bindValue(':tit', $titulo, PDO::PARAM_STR);
    $stmt->bindValue(':des', $desc, PDO::PARAM_STR);
    $stmt->bindValue(':fec', $fecha, PDO::PARAM_STR);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Compromiso guardado correctamente']);
    } else {
        throw new Exception("Error al guardar en base de datos.");
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>