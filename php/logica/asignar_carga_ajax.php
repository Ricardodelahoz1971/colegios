<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
proteccion_extrema();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit;
}

require_once '../db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}

$docente_id = filter_var($input['docente_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$materia_id = filter_var($input['materia_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$curso_dest = filter_var($input['curso_dest'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$curso_orig = filter_var($input['curso_orig'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$accion = filter_var($input['accion'] ?? 'asignar', FILTER_SANITIZE_SPECIAL_CHARS) ?: 'asignar';
$confirmar = filter_var($input['confirmar'] ?? false, FILTER_VALIDATE_BOOLEAN);

if (!$docente_id || !$materia_id) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

try {
    if ($accion === 'eliminar' && $curso_orig > 0) {
        $stmt_del = $db->prepare("DELETE FROM carga_academica WHERE curso_id = :orig AND especialidad_id = :esp AND docente_id = :doc");
        $stmt_del->bindValue(':orig', $curso_orig, PDO::PARAM_INT);
        $stmt_del->bindValue(':esp', $materia_id, PDO::PARAM_INT);
        $stmt_del->bindValue(':doc', $docente_id, PDO::PARAM_INT);
        $stmt_del->execute();
        echo json_encode(['success' => true, 'message' => 'Asignación eliminada con éxito']);
        exit;
    }

    if (!$curso_dest) {
        echo json_encode(['success' => false, 'message' => 'Curso destino no especificado']);
        exit;
    }

    if ($accion !== 'eliminar') {
        $stmt_horas = $db->prepare("
            SELECT COALESCE(SUM(pm.intensidad_horaria), 0)
            FROM carga_academica ca
            JOIN cursos c ON ca.curso_id = c.id
            JOIN zulu_plan_maestro pm ON pm.especialidad_id = ca.especialidad_id AND CAST(pm.nivel_nombre AS INT) = c.nivel_id
            WHERE ca.docente_id = :doc AND NOT (ca.curso_id = :cur AND ca.especialidad_id = :esp)
        ");
        $stmt_horas->bindValue(':doc', $docente_id, PDO::PARAM_INT);
        $stmt_horas->bindValue(':cur', $curso_dest, PDO::PARAM_INT);
        $stmt_horas->bindValue(':esp', $materia_id, PDO::PARAM_INT);
        $stmt_horas->execute();
        $horas_actuales = (int)$stmt_horas->fetchColumn();

        $stmt_nueva_hora = $db->prepare("
            SELECT pm.intensidad_horaria
            FROM zulu_plan_maestro pm
            JOIN cursos c ON c.id = :cur
            WHERE pm.especialidad_id = :esp AND CAST(pm.nivel_nombre AS INT) = c.nivel_id
            LIMIT 1
        ");
        $stmt_nueva_hora->bindValue(':cur', $curso_dest, PDO::PARAM_INT);
        $stmt_nueva_hora->bindValue(':esp', $materia_id, PDO::PARAM_INT);
        $stmt_nueva_hora->execute();
        $horas_nuevas = (int)$stmt_nueva_hora->fetchColumn();

        $total_proyectado = $horas_actuales + $horas_nuevas;

        $limite_horas = 24;
        $stmt_lim = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'limite_horas_docente' LIMIT 1");
        $stmt_lim->execute();
        $db_lim = $stmt_lim->fetchColumn();
        if ($db_lim !== false) {
            $limite_horas = (int)$db_lim;
        }

        if ($total_proyectado > $limite_horas && !$confirmar) {
            $stmt_usr = $db->prepare("SELECT nombre FROM usuarios WHERE id = :id");
            $stmt_usr->bindValue(':id', $docente_id, PDO::PARAM_INT);
            $stmt_usr->execute();
            $nombre_docente = $stmt_usr->fetch(PDO::FETCH_ASSOC)['nombre'] ?? 'Docente';

            echo json_encode([
                'success' => false,
                'conflict' => true,
                'message' => "El docente $nombre_docente ya cuenta con $horas_actuales horas. Asignarle esta materia superará el límite configurado de $limite_horas horas semanales (Total proyectado: $total_proyectado horas). ¿Deseas autorizar esta carga como Horas Extras?"
            ]);
            exit;
        }
    }

    $stmt_chk = $db->prepare("SELECT id, docente_id FROM carga_academica WHERE curso_id = :cur AND especialidad_id = :esp");
    $stmt_chk->bindValue(':cur', $curso_dest, PDO::PARAM_INT);
    $stmt_chk->bindValue(':esp', $materia_id, PDO::PARAM_INT);
    $stmt_chk->execute();
    $existente = $stmt_chk->fetch(PDO::FETCH_ASSOC);

    if ($existente && !$confirmar) {
        $stmt_usr = $db->prepare("SELECT nombre FROM usuarios WHERE id = :id");
        $stmt_usr->bindValue(':id', $existente['docente_id'], PDO::PARAM_INT);
        $stmt_usr->execute();
        $docente_actual = $stmt_usr->fetch(PDO::FETCH_ASSOC)['nombre'] ?? 'Desconocido';

        echo json_encode([
            'success' => false,
            'conflict' => true,
            'message' => "La materia ya está asignada a: $docente_actual. ¿Deseas reemplazarlo?"
        ]);
        exit;
    }

    if ($accion === 'trasladar' && $curso_orig > 0) {
        $stmt_del = $db->prepare("DELETE FROM carga_academica WHERE curso_id = :orig AND especialidad_id = :esp AND docente_id = :doc");
        $stmt_del->bindValue(':orig', $curso_orig, PDO::PARAM_INT);
        $stmt_del->bindValue(':esp', $materia_id, PDO::PARAM_INT);
        $stmt_del->bindValue(':doc', $docente_id, PDO::PARAM_INT);
        $stmt_del->execute();
    }

    if ($existente) {
        $stmt_upd = $db->prepare("UPDATE carga_academica SET docente_id = :doc WHERE id = :id");
        $stmt_upd->bindValue(':doc', $docente_id, PDO::PARAM_INT);
        $stmt_upd->bindValue(':id', $existente['id'], PDO::PARAM_INT);
        $stmt_upd->execute();
    } else {
        $stmt_ins = $db->prepare("INSERT INTO carga_academica (curso_id, especialidad_id, docente_id) VALUES (:cur, :esp, :doc)");
        $stmt_ins->bindValue(':cur', $curso_dest, PDO::PARAM_INT);
        $stmt_ins->bindValue(':esp', $materia_id, PDO::PARAM_INT);
        $stmt_ins->bindValue(':doc', $docente_id, PDO::PARAM_INT);
        $stmt_ins->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Carga académica actualizada correctamente']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>