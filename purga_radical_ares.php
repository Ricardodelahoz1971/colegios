<?php
declare(strict_types=1);

/**
 * 🛡️ MOTOR DE PURGA RADICAL ARES
 * Limpieza absoluta para pruebas físicas desde Línea Cero.
 * Preserva: Roles, Permisos, Catálogos del MEN, Configuración y Cuenta Admin.
 */

header('Content-Type: text/plain; charset=utf-8');
require_once __DIR__ . '/php/db.php';

echo "🌟 INICIANDO LIMPIEZA QUIRÚRGICA DEL SISTEMA...\n\n";

try {
    // Desactivar restricciones de clave foránea temporalmente
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "🔓 Restricciones de claves foráneas desactivadas.\n";

    // Tablas transaccionales y académicas a vaciar por completo
    $tablas_a_vaciar = [
        'estudiantes',
        'cursos',
        'carga_academica',
        'especialidades',
        'areas',
        'zulu_plan_maestro',
        'ares_actividades',
        'ares_actividad_criterios',
        'ares_calificaciones_desglose',
        'ares_auditoria_cambios_notas',
        'eval_pruebas',
        'eval_preguntas',
        'eval_pruebas_items',
        'eval_respuestas',
        'eval_respuestas_detalles',
        'eval_incidentes',
        'eval_asignaciones',
        'aula_recursos',
        'aula_vistos',
        'mensajes',
        'grupos',
        'miembros_grupo',
        'asistencias',
        'cronograma',
        'agenda_escolar',
        'khronos_horarios',
        'khronos_disponibilidad',
        'vistas_canales',
        'log_auditoria_seguridad',
        'logs_auditoria'
    ];

    foreach ($tablas_a_vaciar as $tabla) {
        try {
            $db->exec("TRUNCATE TABLE `$tabla`");
            echo "✔ Tabla `$tabla` vaciada con éxito.\n";
        } catch (Exception $e) {
            // Si TRUNCATE falla por alguna restricción, intentamos DELETE
            $db->exec("DELETE FROM `$tabla`");
            echo "✔ Tabla `$tabla` limpiada vía DELETE.\n";
        }
    }

    // Limpieza de usuarios preservando únicamente al super-administrador 'admin'
    $db->exec("DELETE FROM usuarios WHERE usuario != 'admin'");
    echo "✔ Usuarios no administrativos removidos.\n";

    // Validar y asegurar existencia del usuario 'admin' maestro
    $stmt = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $pass_hash = password_hash('1234', PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id) VALUES (?, ?, ?, ?, ?)")
           ->execute(['admin', $pass_hash, 'admin@colegio.com', 'Director General', 1]);
        echo "➕ Usuario 'admin' maestro creado exitosamente (Clave: 1234).\n";
    } else {
        echo "✔ Usuario 'admin' maestro verificado e intacto.\n";
    }

    // Activar de nuevo restricciones de clave foránea
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "🔒 Restricciones de claves foráneas reactivadas.\n\n";

    echo "🏆 PROCESO TERMINADO CON ÉXITO.\n";
    echo "El sistema ha quedado en blanco y listo para pruebas físicas.\n";

} catch (Exception $e) {
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\n❌ ERROR CRÍTICO DURANTE LA PURGA: " . $e->getMessage() . "\n";
}
