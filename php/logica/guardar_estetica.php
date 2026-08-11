<?php
declare(strict_types=1);
ob_start();
// PHP/LOGICA/GUARDAR_ESTETICA.PHP - HUB DE IDENTIDAD v3.5 (TRAZABILIDAD TOTAL)
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
    session_write_close();

// 📔 SISTEMA DE TRAZABILIDAD ÉLITE (Caja Negra Proactiva)
function registrar_evento_elite(string $categoria, string $mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $log = "[{$fecha}] [{$categoria}] {$mensaje}" . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/elite_trace.log', $log, FILE_APPEND);
}

try {
    // 1. INICIO DE TRAZA
    $input = json_encode($_POST);
    registrar_evento_elite('INPUT', "Petición recibida. Datos: {$input}");

    // 🛡️ CAPA 1: PROTECCIÓN CSRF INVALORABLE
    proteccion_extrema();

    if (!tiene_permiso('configuracion')) {
        registrar_evento_elite('SEGURIDAD', "Intento de acceso no autorizado por ID: {$_SESSION['usuario_id']}");
        throw new Exception("Acceso denegado.");
    }

    // 🏗️ MÓDULO A: APLICAR PALETA
    if (isset($_POST['accion']) && $_POST['accion'] === 'aplicar_paleta' && isset($_POST['paleta_id'])) {
        $pid = (int)$_POST['paleta_id'];
        registrar_evento_elite('LOGICA', "Iniciando aplicación de paleta ID: {$pid}");
        
        $stmt = $db->prepare("SELECT * FROM paletas_elite WHERE id = ?");
        $stmt->execute([$pid]);
        $paleta = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($paleta) {
            registrar_evento_elite('LOGICA', "Paleta '{$paleta['nombre']}' localizada. Iniciando mapeo de colores.");
            $mapeo = [
                'brand_color'   => $paleta['primary_color'],
                'brand_accent'  => $paleta['accent_color'],
                'brand_info'    => $paleta['info_color'],
                'sidebar_bg'    => $paleta['primary_color'],
                'active_palette_id' => $pid
            ];

            $cambios = 0;
            foreach ($mapeo as $clave => $valor) {
                $db->prepare("DELETE FROM ajustes_estetica WHERE clave = ?")->execute([$clave]);
                $stmt_ins = $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES (?, ?)");
                if ($stmt_ins->execute([$clave, $valor])) $cambios++;
            }
            
            registrar_evento_elite('SUCCESS', "Identidad aplicada. {$cambios} variables actualizadas en la bóveda.");
            
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Identidad "' . $paleta['nombre'] . '" sincronizada.']);
            exit();
        } else {
            registrar_evento_elite('LOGICA_FAIL', "Error de lógica: El ID {$pid} no existe en paletas_elite.");
            throw new Exception("La identidad seleccionada no existe.");
        }
    }

    // 🏗️ MÓDULO C: GESTIÓN DE PALETAS (FORJADO DE IDENTIDAD)
    if (isset($_POST['accion']) && $_POST['accion'] === 'guardar_paleta') {
        $nombre = trim($_POST['nombre'] ?? 'Nueva Identidad');
        $p = $_POST['primary_color'] ?? '#204192';
        $a = $_POST['accent_color'] ?? '#f0bb1c';
        $i = $_POST['info_color'] ?? '#0098da';
        $s = $_POST['success_color'] ?? '#059669'; // Fallback a estándar élite
        $d_color = $_POST['danger_color'] ?? '#dc2626'; // Fallback a estándar élite
        $id = (isset($_POST['id']) && (int)$_POST['id'] > 0) ? (int)$_POST['id'] : null;

        if ($id) {
            registrar_evento_elite('LOGICA', "Actualizando paleta ID: {$id} ({$nombre})");
            $stmt = $db->prepare("UPDATE paletas_elite SET nombre = ?, primary_color = ?, accent_color = ?, info_color = ? WHERE id = ?");
            $stmt->execute([$nombre, $p, $a, $i, $id]);
            $msg = "Identidad '{$nombre}' refinada exitosamente.";
        } else {
            registrar_evento_elite('LOGICA', "Forjando nueva paleta: {$nombre}");
            $stmt = $db->prepare("INSERT INTO paletas_elite (nombre, primary_color, accent_color, info_color, success_color, danger_color) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $p, $a, $i, $s, $d_color]);
            $msg = "Nueva identidad '{$nombre}' forjada en el catálogo.";
        }

        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'message' => $msg]);
        exit();
    }

    // 🏗️ MÓDULO D: ELIMINACIÓN DE PALETAS
    if (isset($_POST['accion']) && $_POST['accion'] === 'borrar_paleta' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // Protección contra purga de paletas maestras (1-4)
        if ($id <= 4) {
            throw new Exception("Las identidades maestras son inmutables y no pueden ser purgadas.");
        }

        registrar_evento_elite('LOGICA', "Purgando paleta ID: {$id}");
        $stmt = $db->prepare("DELETE FROM paletas_elite WHERE id = ?");
        $stmt->execute([$id]);

        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Identidad eliminada del catálogo.']);
        exit();
    }

    // 🏗️ MÓDULO B: ARQUITECTURA
    if (isset($_POST['menu_style'])) {
        $valor = trim($_POST['menu_style']);
        registrar_evento_elite('LOGICA', "Cambiando Arquitectura a: {$valor}");
        $db->prepare("DELETE FROM ajustes_estetica WHERE clave = 'menu_style'")->execute();
        $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES ('menu_style', ?)")->execute([$valor]);
    }

    // 🛡️ LISTA BLANCA ÉLITE
    $whiteList = [
        'school_name', 'school_motto', 'school_font', 'brand_color', 
        'brand_accent', 'brand_info', 'brand_success', 'brand_danger',
        'sidebar_bg', 'body_bg', 'surface_bg', 'text_main', 'brand_hover',
        'menu_style', 'dark_mode', 'active_palette_id',
        'khronos_inicio', 'khronos_duracion', 'khronos_max_horas', 
        'khronos_dias', 'khronos_descanso_h', 'khronos_descanso_m',
        'khronos_descanso2_h', 'khronos_descanso2_m', 'khronos_max_diario_materia',
        'colegio_nit', 'colegio_resolucion'
    ];

    $procesados = 0;
    foreach ($_POST as $clave => $valor) {
        $es_valido = false;
        if (in_array($clave, $whiteList)) {
            $es_valido = true;
        } elseif (strpos($clave, 'khronos_') === 0) {
            $es_valido = true;
        }
        if (!$es_valido) continue;
        // Almacenar datos puros sin escapar HTML con e() (Anti-patrón de Saneamiento)
        $valor = is_array($valor) ? implode(',', $valor) : $valor;
        
        $db->prepare("DELETE FROM ajustes_estetica WHERE clave = ?")->execute([$clave]);
        $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES (?, ?)")->execute([$clave, $valor]);
        $procesados++;
    }

    registrar_evento_elite('SUCCESS', "Sincronización finalizada. {$procesados} variables procesadas.");

    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'success', 'message' => 'Sincronización Élite Finalizada.']);

} catch (Throwable $e) {
    registrar_evento_elite('FATAL_ERROR', $e->getMessage());
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
