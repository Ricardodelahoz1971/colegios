<?php
declare(strict_types=1);
// PHP/LOGICA/HELPERS_CENTINELA.PHP - MOTOR LÓGICO CENTRALIZADO DEL CENTINELA DE INTEGRIDAD

require_once __DIR__ . '/helpers_recesos.php';

/**
 * Verifica si ya hay exámenes programados en el día para ese curso.
 * Retorna un array con 'cruce' => bool, 'cantidad' => int, y 'titulos' => array.
 */
function verificar_cruces_examenes(PDO $db, int $curso_id, string $fecha): array {
    $time = strtotime($fecha);
    if ($time === false) {
        return ['cruce' => false, 'cantidad' => 0, 'titulos' => []];
    }
    $fecha_ymd = date('Y-m-d', $time);
    
    // Consultar exámenes programados para el mismo curso y día
    $stmt = $db->prepare("
        SELECT p.titulo 
        FROM eval_asignaciones a
        JOIN eval_pruebas p ON a.prueba_id = p.id
        WHERE a.curso_id = ? 
          AND DATE(a.fecha_inicio) = ?
    ");
    $stmt->execute([$curso_id, $fecha_ymd]);
    $examenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $cant = count($examenes);
    return [
        'cruce' => ($cant >= 2),
        'cantidad' => $cant,
        'titulos' => $examenes
    ];
}

/**
 * Verifica si la fecha cae en un receso o en fin de semana (sábado/domingo).
 */
function verificar_fecha_receso_fin_semana(PDO $db, string $fecha): array {
    $time = strtotime($fecha);
    if ($time === false) {
        return ['colision' => false, 'tipo' => ''];
    }
    
    $w = (int)date('w', $time);
    if ($w === 0 || $w === 6) {
        return ['colision' => true, 'tipo' => 'fin_semana'];
    }
    
    if (es_dia_receso($db, $fecha)) {
        return ['colision' => true, 'tipo' => 'receso'];
    }
    
    return ['colision' => false, 'tipo' => ''];
}

/**
 * Verifica si la diferencia entre la fecha actual/creación y la fecha de entrega es menor a 12 horas.
 */
function verificar_tiempo_critico(string $fecha_entrega): bool {
    $time_entrega = strtotime($fecha_entrega);
    if ($time_entrega === false) {
        return false;
    }
    $diff = $time_entrega - time();
    $horas = $diff / 3600;
    return ($horas < 12);
}
