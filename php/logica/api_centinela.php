<?php

declare(strict_types=1);

ob_start();
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
require_once __DIR__ . '/helpers_centinela.php';
guardia_sesion();
session_write_close();

try {
    if (!tiene_permiso('evaluacion') && !tiene_permiso('agenda') && !tiene_permiso('cronograma')) {
        throw new Exception('Acceso denegado.');
    }

    $accion = filter_input(INPUT_POST, 'accion', FILTER_SANITIZE_SPECIAL_CHARS) 
              ?? filter_input(INPUT_GET, 'accion', FILTER_SANITIZE_SPECIAL_CHARS) 
              ?? 'consultar';

    switch ($accion) {
        case 'validar_tarea':
            $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) 
                     ?? filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) 
                     ?? '';
            if (empty($fecha)) {
                throw new Exception('Fecha no proporcionada.');
            }

            $colision_receso = verificar_fecha_receso_fin_semana($db, $fecha);
            $es_critico = verificar_tiempo_critico($fecha);

            echo json_encode([
                'status' => 'success',
                'colision_receso' => $colision_receso['colision'],
                'tipo_receso' => $colision_receso['tipo'],
                'tiempo_critico' => $es_critico
            ]);
            break;

        case 'validar_examen':
            $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) 
                     ?? filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_SPECIAL_CHARS) 
                     ?? '';
            $curso_id = (int)(filter_input(INPUT_GET, 'curso_id', FILTER_VALIDATE_INT) 
                     ?? filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) 
                     ?? 0);

            if (empty($fecha) || !$curso_id) {
                throw new Exception('Parámetros incompletos.');
            }

            $colision_receso = verificar_fecha_receso_fin_semana($db, $fecha);
            $cruce_examenes = verificar_cruces_examenes($db, $curso_id, $fecha);

            echo json_encode([
                'status' => 'success',
                'colision_receso' => $colision_receso['colision'],
                'tipo_receso' => $colision_receso['tipo'],
                'cruce_examenes' => $cruce_examenes['cruce'],
                'cantidad_examenes' => $cruce_examenes['cantidad'],
                'examenes_existentes' => $cruce_examenes['titulos']
            ]);
            break;

        default:
            throw new Exception('Acción no soportada.');
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}