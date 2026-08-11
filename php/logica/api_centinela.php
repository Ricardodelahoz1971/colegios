<?php
declare(strict_types=1);
// PHP/LOGICA/API_CENTINELA.PHP - INTERFAZ DE CONSULTA PREVENTIVA DEL CENTINELA DE INTEGRIDAD

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

    $accion = $_POST['accion'] ?? $_GET['accion'] ?? 'consultar';

    switch ($accion) {
        case 'validar_tarea':
            $fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? '';
            if (empty($fecha)) {
                throw new Exception('Fecha no proporcionada.');
            }

            // 1. Validar receso / fin de semana
            $colision_receso = verificar_fecha_receso_fin_semana($db, $fecha);

            // 2. Validar tiempo crítico (< 12 horas)
            $es_critico = verificar_tiempo_critico($fecha);

            echo json_encode([
                'status' => 'success',
                'colision_receso' => $colision_receso['colision'],
                'tipo_receso' => $colision_receso['tipo'],
                'tiempo_critico' => $es_critico
            ]);
            break;

        case 'validar_examen':
            $fecha = $_GET['fecha'] ?? $_POST['fecha'] ?? '';
            $curso_id = (int)($_GET['curso_id'] ?? $_POST['curso_id'] ?? 0);

            if (empty($fecha) || !$curso_id) {
                throw new Exception('Parámetros incompletos.');
            }

            // 1. Validar receso / fin de semana
            $colision_receso = verificar_fecha_receso_fin_semana($db, $fecha);

            // 2. Validar cruces (máximo 2 por día)
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
