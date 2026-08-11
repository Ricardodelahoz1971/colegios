<?php
declare(strict_types=1);
// PHP/LOGICA/HELPERS_RECESOS.PHP - MOTOR LÓGICO DE TIEMPOS ESCALABLE (PROTOCOLO MERCURIO)
require_once __DIR__ . '/helpers_centinela.php';


function obtener_todos_los_recesos(PDO $db): array {
    static $recesos = null;
    if ($recesos === null) {
        try {
            $stmt = $db->prepare("SELECT id, nombre, fecha_inicio, fecha_fin FROM recesos_escolares ORDER BY fecha_inicio ASC");
            $stmt->execute();
            $recesos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $recesos = [];
        }
    }
    return $recesos;
}

function es_dia_receso(PDO $db, string $fecha): bool {
    $time = strtotime($fecha);
    if ($time === false) return false;
    $fecha_ymd = date('Y-m-d', $time);
    
    $recesos = obtener_todos_los_recesos($db);
    foreach ($recesos as $r) {
        if ($fecha_ymd >= $r['fecha_inicio'] && $fecha_ymd <= $r['fecha_fin']) {
            return true;
        }
    }
    return false;
}

/**
 * Calcula la fecha límite sumando N días hábiles, saltándose sábados, domingos y días de receso escolar.
 */
function calcular_fecha_limite_con_recesos(PDO $db, string $fecha_origen, int $dias_habiles): string {
    $current_time = strtotime($fecha_origen);
    if ($current_time === false) return $fecha_origen;
    
    $added = 0;
    while ($added < $dias_habiles) {
        $current_time = strtotime('+1 day', $current_time);
        $w = (int)date('w', $current_time);
        $fecha_str = date('Y-m-d', $current_time);
        
        // Excluir sábados (6), domingos (0) y días de receso
        if ($w !== 0 && $w !== 6 && !es_dia_receso($db, $fecha_str)) {
            $added++;
        }
    }
    // Conservar la hora original
    $hora_origen = date('H:i:s', strtotime($fecha_origen));
    return date('Y-m-d ', $current_time) . $hora_origen;
}

/**
 * Cuenta los días hábiles reales entre dos fechas (excluyendo fines de semana y recesos).
 */
function contar_dias_habiles_reales(PDO $db, string $fecha_inicio, string $fecha_fin): int {
    $t_start = strtotime($fecha_inicio);
    $t_end = strtotime($fecha_fin);
    if ($t_start === false || $t_end === false || $t_start > $t_end) return 0;
    
    $habiles = 0;
    $curr = $t_start;
    $end_date_str = date('Y-m-d', $t_end);
    
    while (date('Y-m-d', $curr) <= $end_date_str) {
        $w = (int)date('w', $curr);
        $fecha_str = date('Y-m-d', $curr);
        
        if ($w !== 0 && $w !== 6 && !es_dia_receso($db, $fecha_str)) {
            $habiles++;
        }
        $curr = strtotime('+1 day', $curr);
    }
    return $habiles;
}
