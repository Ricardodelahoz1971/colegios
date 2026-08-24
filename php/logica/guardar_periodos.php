<?php
declare(strict_types=1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
session_write_close();

try {
    proteccion_extrema();

    if (!tiene_permiso('configuracion')) {
        throw new Exception("Acceso denegado: Privilegios insuficientes.");
    }

    $mi_rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
    if ($mi_rol_nombre !== 'administrador' && $mi_rol_nombre !== 'coordinador') {
        throw new Exception("Acceso denegado: Su rol no posee autoría para esta acción.");
    }

    $periodos_raw = filter_input(INPUT_POST, 'periodo', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
    if (!is_array($periodos_raw)) {
        throw new Exception("Parámetros de entrada inválidos.");
    }

    $periodo_activo_raw = filter_input(INPUT_POST, 'periodo_activo', FILTER_VALIDATE_INT);
    $periodo_activo_id = $periodo_activo_raw !== false && $periodo_activo_raw !== null ? (int)$periodo_activo_raw : null;
    if ($periodo_activo_id === null || !array_key_exists($periodo_activo_id, $periodos_raw)) {
        throw new Exception("Debe seleccionar un periodo como activo.");
    }

    $periodos_procesados = [];
    foreach ($periodos_raw as $id => $fechas) {
        $pid = (int)$id;
        $f_inicio = trim((string)($fechas['inicio'] ?? ''));
        $f_fin = trim((string)($fechas['fin'] ?? ''));

        if (empty($f_inicio) || empty($f_fin)) {
            throw new Exception("Las fechas de inicio y fin del Periodo $pid no pueden estar vacías.");
        }

        $t_inicio = strtotime($f_inicio);
        $t_fin = strtotime($f_fin);

        if ($t_inicio === false || $t_fin === false) {
            throw new Exception("El formato de fecha provisto para el Periodo $pid no es válido.");
        }

        if ($t_inicio >= $t_fin) {
            throw new Exception("La fecha de inicio del Periodo $pid debe ser anterior a su fecha de terminación.");
        }

        $periodos_procesados[] = [
            'id' => $pid,
            'inicio' => $t_inicio,
            'fin' => $t_fin,
            'db_inicio' => date('Y-m-d 00:00:00', $t_inicio),
            'db_fin' => date('Y-m-d 23:59:59', $t_fin)
        ];
    }

    usort($periodos_procesados, function ($a, $b) {
        return $a['id'] <=> $b['id'];
    });

    $total_p = count($periodos_procesados);
    for ($i = 1; $i < $total_p; $i++) {
        if ($periodos_procesados[$i]['inicio'] < $periodos_procesados[$i - 1]['fin']) {
            throw new Exception("Existe superposición: el Periodo " . $periodos_procesados[$i]['id'] . " inicia antes de la finalización del Periodo " . $periodos_procesados[$i - 1]['id'] . ".");
        }
    }

    $db->beginTransaction();
    try {
        foreach ($periodos_procesados as $p) {
            $activo = ($p['id'] === $periodo_activo_id) ? 1 : 0;
            $stmt = $db->prepare("UPDATE eval_periodos_academicos SET fecha_inicio = ?, fecha_fin = ?, activo = ? WHERE id = ?");
            $stmt->execute([$p['db_inicio'], $p['db_fin'], $activo, $p['id']]);
        }
        $db->commit();
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Fechas del calendario escolar guardadas y consolidadas.'
    ]);

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit();
?>