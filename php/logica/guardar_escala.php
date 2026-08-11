<?php
declare(strict_types=1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

/**
 * 🏛️ CONTROLADOR DE ESCALAS Y REGULACIÓN ACADÉMICA (PERSEUS SYSTEM ENGINE)
 * Persistencia atómica de escala de notas, ponderaciones de dimensiones y planes de evaluación obligatoria.
 * 
 * @author Ingeniería Élite v9.5
 * @version 1.0 (Strict Security & Architecture)
 */

require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
    session_write_close();

try {
    // 🛡️ CAPA 1: PROTECCIÓN CSRF E INTEGRIDAD
    proteccion_extrema();

    // 🛡️ CAPA 2: AUTORIZACIÓN DE PERMISOS
    if (!tiene_permiso('configuracion')) {
        throw new Exception("Acceso denegado: Privilegios insuficientes.");
    }

    // 🛡️ CAPA 3: AUTORIZACIÓN POR ROL (Coordinador / Administrador / Rector)
    $mi_rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
    if ($mi_rol_nombre !== 'administrador' && $mi_rol_nombre !== 'coordinador' && $mi_rol_nombre !== 'rector') {
        throw new Exception("Acceso denegado: Su rol no posee autoría para esta acción.");
    }

    // 🏗️ PROCESAMIENTO DE PETICIÓN (TRANSACCIÓN PDO ATÓMICA CON AISLAMIENTO DE BLOQUEO)
    // Forzar bloqueo exclusivo de lectura/escritura en las tablas de notas en MariaDB para evitar condiciones de carrera concurrentes
    $db->exec("LOCK TABLES eval_config_escala WRITE, ares_clases_nota WRITE, ares_calificaciones_desglose READ, eval_respuestas READ");

    // 🛡️ CAPA 4: SEGURIDAD ACADÉMICA (BLOQUEO DE EDICIÓN CON CALIFICACIONES REGISTRADAS)
    // Verificamos si existen calificaciones en actividades diarias
    $stmt_check_act = $db->prepare("SELECT COUNT(*) FROM ares_calificaciones_desglose WHERE calificacion IS NOT NULL");
    $stmt_check_act->execute();
    $cant_notas_act = (int)$stmt_check_act->fetchColumn();

    // Verificamos si existen calificaciones en exámenes en línea
    $stmt_check_ex = $db->prepare("SELECT COUNT(*) FROM eval_respuestas WHERE calificacion_manual > 0 OR calificacion_automatica > 0");
    $stmt_check_ex->execute();
    $cant_notas_ex = (int)$stmt_check_ex->fetchColumn();

    if ($cant_notas_act > 0 || $cant_notas_ex > 0) {
        $db->exec("UNLOCK TABLES");
        throw new Exception("Seguridad Académica: No es posible modificar las escalas, pesos o planes mínimos porque ya existen calificaciones registradas en el periodo actual. Se ha bloqueado la acción para proteger el historial académico de los estudiantes.");
    }

    // Iniciar transacción de datos
    $db->beginTransaction();

    // 1. OBTENER Y VALIDAR PARÁMETROS DE LA ESCALA
    $nota_minima       = (float)($_POST['nota_minima'] ?? 0.0);
    $nota_maxima       = (float)($_POST['nota_maxima'] ?? 0.0);
    $nota_aprobacion   = (float)($_POST['nota_aprobacion'] ?? 0.0);
    $rango_superior_min = (float)($_POST['rango_superior_min'] ?? 0.0);
    $rango_alto_min     = (float)($_POST['rango_alto_min'] ?? 0.0);
    $rango_basico_min   = (float)($_POST['rango_basico_min'] ?? 0.0);

    // Validaciones Matemáticas Estrictas de Escala
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

    // Guardar Escala
    $stmt_up_esc = $db->prepare("UPDATE eval_config_escala 
                                 SET nota_minima = ?, nota_maxima = ?, nota_aprobacion = ?, 
                                     rango_superior_min = ?, rango_alto_min = ?, rango_basico_min = ? 
                                 WHERE activo = 1");
    $stmt_up_esc->execute([
        $nota_minima, $nota_maxima, $nota_aprobacion, 
        $rango_superior_min, $rango_alto_min, $rango_basico_min
    ]);

    // 2. OBTENER Y VALIDAR PARÁMETROS DE DIMENSIONES (PLAN DE NOTAS MÍNIMAS)
    $pesos        = $_POST['peso_dim'] ?? [];      // Array asociativo [id => peso_float]
    $min_evals    = $_POST['min_eval_dim'] ?? [];  // Array asociativo [id => int]
    
    if (!empty($pesos)) {
        $suma_pesos = 0.0;
        foreach ($pesos as $id => $p) {
            $suma_pesos += (float)$p;
        }

        // Permitimos tolerancia de coma flotante ínfima
        if (abs($suma_pesos - 100.0) > 0.01) {
            $db->exec("UNLOCK TABLES");
            throw new Exception("La suma de los pesos de las dimensiones debe ser exactamente 100% (Suma actual: {$suma_pesos}%).");
        }

        // Actualizar dimensiones
        $stmt_up_dim = $db->prepare("UPDATE ares_clases_nota SET peso_global = ?, min_evaluaciones = ? WHERE id = ?");
        foreach ($pesos as $id => $p) {
            $dim_id = (int)$id;
            $peso = (float)$p;
            $min_e = isset($min_evals[$dim_id]) ? max(1, (int)$min_evals[$dim_id]) : 2;
            $stmt_up_dim->execute([$peso, $min_e, $dim_id]);
        }
    }

    // 3. FINALIZACIÓN SOBERANA DE LA TRANSACCIÓN
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
