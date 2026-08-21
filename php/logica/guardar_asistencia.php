<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('asistencia')) {
        throw new Exception('Acceso denegado: No posee permisos de control de asistencia.');
    }

    $mi_id = (int)($_SESSION['usuario_id'] ?? 0);
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT);
    $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
    $asistencias_raw = filter_input(INPUT_POST, 'asistencias', FILTER_SANITIZE_STRING);
    $asistencias = json_decode($asistencias_raw ?? '[]', true);
    session_write_close();

    if (!$curso_id || !$fecha || empty($asistencias)) {
        throw new Exception('Datos incompletos para procesar la sincronización.');
    }

    $db->beginTransaction();

    $stmt_del = $db->prepare("DELETE FROM asistencias WHERE curso_id = :cid AND fecha = :fec");
    $stmt_del->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt_del->bindValue(':fec', $fecha, PDO::PARAM_STR);
    $stmt_del->execute();

    $ha_faltado = false;
    $nombres_faltantes = [];

    $stmt_ins = $db->prepare("INSERT INTO asistencias (estudiante_id, curso_id, fecha, estado, observaciones, registrado_por) 
                              VALUES (:eid, :cid, :fec, :est, :obs, :reg)");

    foreach ($asistencias as $a) {
        $estado = filter_var($a['estado'] ?? '', FILTER_SANITIZE_STRING);
        $obs = filter_var($a['obs'] ?? '', FILTER_SANITIZE_STRING);
        $estudiante_id = filter_var($a['id'] ?? 0, FILTER_VALIDATE_INT);

        if ($estado === 'F') {
            $ha_faltado = true;
            $stmt_nom = $db->prepare("SELECT CONCAT(apellido, ', ', nombre) FROM estudiantes WHERE id = :eid");
            $stmt_nom->bindValue(':eid', $estudiante_id, PDO::PARAM_INT);
            $stmt_nom->execute();
            $nom_alumno = $stmt_nom->fetchColumn() ?: 'Estudiante Desconocido';
            $nombres_faltantes[] = $nom_alumno;
        }

        $stmt_ins->bindValue(':eid', $estudiante_id, PDO::PARAM_INT);
        $stmt_ins->bindValue(':cid', $curso_id, PDO::PARAM_INT);
        $stmt_ins->bindValue(':fec', $fecha, PDO::PARAM_STR);
        $stmt_ins->bindValue(':est', $estado, PDO::PARAM_STR);
        $stmt_ins->bindValue(':obs', $obs, PDO::PARAM_STR);
        $stmt_ins->bindValue(':reg', $mi_id, PDO::PARAM_INT);
        $stmt_ins->execute();
    }

    if ($ha_faltado) {
        $stmt_curso = $db->prepare("SELECT nombre_curso FROM cursos WHERE id = :cid");
        $stmt_curso->bindValue(':cid', $curso_id, PDO::PARAM_INT);
        $stmt_curso->execute();
        $nombre_curso = $stmt_curso->fetchColumn() ?: 'Curso';
        $cantidad = count($nombres_faltantes);
        $lista_str = implode(', ', $nombres_faltantes);
        
        $asunto = "⚠️ ALERTA DE INASISTENCIA: $nombre_curso";
        $contenido = "El sistema ha detectado $cantidad inasistencias en el grado $nombre_curso para la fecha $fecha.\n\nEstudiantes:\n$lista_str\n\nRegistrado por: " . ($_SESSION['nombre_usuario'] ?? 'Sistema');

        $sql_admins = "SELECT u.id FROM usuarios u JOIN roles r ON u.rol_id = r.id WHERE r.nombre_rol IN ('Administrador', 'Coordinador', 'Secretaria')";
        $stmt_adm = $db->prepare($sql_admins);
        $stmt_adm->execute();
        
        $stmt_msg = $db->prepare("INSERT INTO mensajes (remitente_id, destinatario_id, asunto, contenido, leido) VALUES (:rem, :dest, :asu, :con, 0)");
        $stmt_msg->bindValue(':rem', $mi_id, PDO::PARAM_INT);
        $stmt_msg->bindValue(':asu', $asunto, PDO::PARAM_STR);
        $stmt_msg->bindValue(':con', $contenido, PDO::PARAM_STR);

        while ($adm = $stmt_adm->fetch(PDO::FETCH_ASSOC)) {
            if ($adm['id'] != $mi_id) {
                $stmt_msg->bindValue(':dest', $adm['id'], PDO::PARAM_INT);
                $stmt_msg->execute();
            }
        }
    }

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Asistencia sincronizada y alertas procesadas!',
        'ha_faltado' => $ha_faltado
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>