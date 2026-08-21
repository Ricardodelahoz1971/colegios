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
    if ($mi_rol_nombre !== 'administrador' && $mi_rol_nombre !== 'coordinador' && $mi_rol_nombre !== 'rector') {
        throw new Exception("Acceso denegado: Su rol no posee autoría para esta acción.");
    }

    $db->exec("LOCK TABLES eval_config_escala WRITE, ares_clases_nota WRITE, ares_calificaciones_desglose READ, eval_respuestas READ");

    $stmt_check_act = $db->prepare("SELECT COUNT(*) FROM ares_calificaciones_desglose WHERE calificacion IS NOT NULL");
    $stmt_check_act->execute();
    $cant_notas_act = (int)$stmt_check_act->fetchColumn();

    $stmt_check_ex = $db->prepare("SELECT COUNT(*) FROM eval_respuestas WHERE calificacion_manual > 0 OR calificacion_automatica > 0");
    $stmt_check_ex->execute();
    $cant_notas_ex = (int)$stmt_check_ex->fetchColumn();

    if ($cant_notas_act > 0 || $cant_notas_ex > 0) {
        $db->exec("UNLOCK TABLES");
        throw new Exception("Seguridad Académica: No es posible modificar las escalas, pesos o planes mínimos porque ya existen calificaciones registradas en el periodo actual. Se ha bloqueado la acción para proteger el historial académico de los estudiantes.");
    }

    $db->beginTransaction();

    $nota_minima = filter_input(INPUT_POST, 'nota_minima', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);
    $nota_maxima = filter_input(INPUT_POST, 'nota_maxima', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);
    $nota_aprobacion = filter_input(INPUT_POST, 'nota_aprobacion', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);
    $rango_superior_min = filter_input(INPUT_POST, 'rango_superior_min', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);
    $rango_alto_min = filter_input(INPUT_POST, 'rango_alto_min', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);
    $rango_basico_min = filter_input(INPUT_POST, 'rango_basico_min', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0.0]]);

    if ($nota_minima === false || $nota_maxima === false || $nota_aprobacion === false || 
        $rango_superior_min === false || $rango_alto_min === false || $rango_basico_min === false) {
        throw new Exception("Parámetros de escala inválidos.");
    }

    if ($nota_minima >= $nota_maxima) {
        $db->exec("UNLOCK TABLES");
        throw new Exception("La nota mínima no puede ser mayor o igual a la nota máxima.");
    }
    if ($nota_aprobacion <= $nota_minima || $nota_aprobacion >= $nota_maxima) {
        $db->exec("UNLOCK TABLES");
        throw new Exception("La nota de aprobación debe estar estrictamente contenida entre el rango mínimo y máximo.");
    }
    if ($rango_basico_min != $nota_aprobacion) {
        $db->exec("UNLOCK TABLES");
        throw new Exception("El límite inferior del Desempeño Básico debe coincidir exactamente con la nota de aprobación institucional.");
    }
    if ($rango_superior_min <= $rango_alto_min || $rango_alto_min <= $rango_basico_min || $rango_superior_min >= $nota_maxima) {
        $db->exec("UNLOCK TABLES");
        throw new Exception("Discrepancia de rangos: Rango Superior > Rango Alto > Rango Básico.");
    }

    $stmt_up_esc = $db->prepare("UPDATE eval_config_escala 
                                 SET nota_minima = ?, nota_maxima = ?, nota_aprobacion = ?, 
                                     rango_superior_min = ?, rango_alto_min = ?, rango_basico_min = ? 
                                 WHERE activo = 1");
    $stmt_up_esc->execute([
        $nota_minima, $nota_maxima, $nota_aprobacion, 
        $rango_superior_min, $rango_alto_min, $rango_basico_min
    ]);

    $pesos_raw = filter_input(INPUT_POST, 'peso_dim', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];
    $min_evals_raw = filter_input(INPUT_POST, 'min_eval_dim', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY) ?? [];

    $pesos = [];
    foreach ($pesos_raw as $id => $p) {
        $peso_val = filter_var($p, FILTER_VALIDATE_FLOAT);
        if ($peso_val !== false) {
            $pesos[(int)$id] = $peso_val;
        }
    }

    $min_evals = [];
    foreach ($min_evals_raw as $id => $m) {
        $min_val = filter_var($m, FILTER_VALIDATE_INT);
        if ($min_val !== false) {
            $min_evals[(int)$id] = $min_val;
        }
    }

    if (!empty($pesos)) {
        $suma_pesos = array_sum($pesos);

        if (abs($suma_pesos - 100.0) > 0.01) {
            $db->exec("UNLOCK TABLES");
            throw new Exception("La suma de los pesos de las dimensiones debe ser exactamente 100% (Suma actual: {$suma_pesos}%).");
        }

        $stmt_up_dim = $db->prepare("UPDATE ares_clases_nota SET peso_global = ?, min_evaluaciones = ? WHERE id = ?");
        foreach ($pesos as $dim_id => $peso) {
            $min_e = isset($min_evals[$dim_id]) ? max(1, $min_evals[$dim_id]) : 2;
            $stmt_up_dim->execute([$peso, $min_e, $dim_id]);
        }
    }

    $db->commit();
    $db->exec("UNLOCK TABLES");

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Escala institucional y plan de notas mínimas guardados con éxito.'
    ]);

} catch (Throwable $e) {
    if (isset($db)) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        try {
            $db->exec("UNLOCK TABLES");
        } catch (Exception $unlockEx) {
            // Ignorar si no había bloqueos activos
        }
    }
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit();
?>