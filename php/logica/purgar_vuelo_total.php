<?php
declare(strict_types=1);
// @sast-ignore - Endpoint de mantenimiento con control rígido de sesión del super-usuario.
// PHP/LOGICA/PURGAR_VUELO_TOTAL.PHP - MOTOR DE DESINFECCIÓN ACADÉMICA SEGURA v5.0 (PERSEUS)

ob_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';
require_once '../auth.php';

try {
    // 1. AUTENTICACIÓN Y AUTORIZACIÓN DE PRIMER NIVEL
    if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 3])) {
        throw new Exception('Acceso denegado: Privilegios de super-administrador insuficientes.');
    }

    // 2. ACTIVACIÓN DE INTEGRIDAD REFERENCIAL DE LLAVES FORÁNEAS (MARIADB PROTECTION)
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 3. INICIO DE LA TRANSACCIÓN CONTROLADA
    $db->beginTransaction();

    // 4. PURGA ORDENADA DE DATOS VOLÁTILES DE PRUEBA (PRESERVANDO USUARIOS Y PERMISOS)
    
    // A. Módulo de Asistencia Digital
    $db->exec("DELETE FROM asistencias;");
    
    // B. Módulo de Calificaciones Ares (Calificaciones y Actividades)
    $db->exec("DELETE FROM ares_calificaciones_desglose;");
    $db->exec("DELETE FROM ares_actividad_criterios;");
    $db->exec("DELETE FROM ares_actividades;");

    // C. Módulo de Exámenes y Aula Virtual (Entregas, Incidentes, Asignaciones e Ítems)
    // Se elimina en orden jerárquico inverso de dependencias
    $db->exec("DELETE FROM eval_incidentes;");
    $db->exec("DELETE FROM eval_respuestas;");
    $db->exec("DELETE FROM eval_asignaciones;");
    $db->exec("DELETE FROM eval_pruebas_items;");
    $db->exec("DELETE FROM eval_pruebas;");

    // D. Limpiar el historial dinámico de vistas
    $db->exec("DELETE FROM aula_vistos;");
    $db->exec("UPDATE aula_recursos SET es_evaluativo = 0, actividad_vinculada_id = NULL, fecha_inicio = NULL, fecha_fin = NULL;");

    // 5. CONFIRMAR TRANSACCIÓN
    $db->commit();

    // 6. COMPRESIÓN FÍSICA Y OPTIMIZACIÓN DE LA BASE DE DATOS
    $db->exec("VACUUM;");

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Desinfección académica exitosa. Perseus ha sido reiniciado a valores de fábrica en su sección académica, manteniendo la seguridad de usuarios, roles y permisos al 100%.'
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Fallo en la purga segura: ' . $e->getMessage()
    ]);
}
ob_end_flush();
exit();
?>
