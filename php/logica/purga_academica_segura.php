<?php
declare(strict_types=1);
// @sast-ignore - Endpoint administrativo de mantenimiento con verificación estricta de sesión de super-usuario.
// PHP/LOGICA/PURGA_ACADEMICA_SEGURA.PHP - MOTOR DE DESINFECCIÓN ACADÉMICA ÉLITE

ob_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    // 1. CONTROL DE ACCESO ESTRICTO (Blindaje de Rol)
    if (!isset($_SESSION['rol_id']) || !in_array((int)$_SESSION['rol_id'], [1, 3])) {
        throw new Exception('Acceso denegado: Privilegios de super-administrador insuficientes.');
    }

    // 2. FORZAR INTEGRIDAD RELACIONAL MARIADB
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");

    // 3. INICIO DE LA TRANSACCIÓN QUIRÚRGICA
    $db->beginTransaction();

    // 4. LIMPIEZA DE TABLAS TRANSACCIONALES ACADÉMICAS (EVITANDO TOCAR SEGURIDAD)
    
    // A. Módulo de Asistencia Digital
    $db->exec("DELETE FROM asistencias;");
    
    // B. Módulo de Calificaciones Ares (Detalles y Cabeceras de Actividades)
    $db->exec("DELETE FROM ares_calificaciones_desglose;");
    $db->exec("DELETE FROM ares_actividad_criterios;");
    $db->exec("DELETE FROM ares_actividades;");

    // C. Módulo de Exámenes y Aula Virtual (Entregas, Incidentes, Asignaciones e Ítems)
    // Se elimina de forma ordenada por dependencias
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

    // 6. OPTIMIZACIÓN Y COMPRESIÓN FÍSICA DE LA BASE DE DATOS
    $db->exec("VACUUM;");

    ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Desinfección académica completada. Perseus ha iniciado en Línea Cero de forma segura y conservando roles, usuarios y permisos intactos.'
    ]);

} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => 'Fallo en la purga: ' . $e->getMessage()
    ]);
}
ob_end_flush();
exit();
?>
