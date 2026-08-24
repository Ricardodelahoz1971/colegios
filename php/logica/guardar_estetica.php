<?php

declare(strict_types=1);
ob_start();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
session_write_close();

function registrar_evento_elite(string $categoria, string $mensaje): void {
    $fecha = date('Y-m-d H:i:s');
    $log = "[{$fecha}] [{$categoria}] {$mensaje}" . PHP_EOL;
    file_put_contents(__DIR__ . '/../logs/elite_trace.log', $log, FILE_APPEND);
}

function obtener_post(string $clave, int $filtro = FILTER_DEFAULT, array $opciones = []): mixed {
    $valor = filter_input(INPUT_POST, $clave, $filtro, $opciones);
    return $valor !== null && $valor !== false ? $valor : null;
}

try {
    $input = json_encode($_POST);
    registrar_evento_elite('INPUT', "Petición recibida. Datos: {$input}");

    proteccion_extrema();

    if (!tiene_permiso('configuracion')) {
        registrar_evento_elite('SEGURIDAD', "Intento de acceso no autorizado por ID: {$_SESSION['usuario_id']}");
        throw new Exception("Acceso denegado.");
    }
    $accion = obtener_post('accion');

    if ($accion === 'aplicar_paleta') {
        $pid = obtener_post('paleta_id', FILTER_VALIDATE_INT);
        if ($pid === null || $pid === false) {
            throw new Exception("ID de paleta inválido.");
        }
        registrar_evento_elite('LOGICA', "Iniciando aplicación de paleta ID: {$pid}");
        
        $stmt = $db->prepare("SELECT * FROM paletas_elite WHERE id = ?");
        $stmt->execute([$pid]);
        $paleta = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($paleta) {
            registrar_evento_elite('LOGICA', "Paleta encontrada: {$paleta['nombre']}. Aplicando...");
            $configs = [
                'brand_color'   => $paleta['primary_color'],
                'accent_color'  => $paleta['accent_color'],
                'info_color'    => $paleta['info_color'],
                'success_color' => $paleta['success_color'],
                'danger_color'  => $paleta['danger_color']
            ];
            
            $db->beginTransaction();
            foreach ($configs as $k => $v) {
                $db->prepare("DELETE FROM ajustes_estetica WHERE clave = ?")->execute([$k]);
                $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES (?, ?)")->execute([$k, $v]);
            }
            $db->commit();
            registrar_evento_elite('EXITO', "Paleta '{$paleta['nombre']}' aplicada con éxito.");
            
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'message' => "Identidad visual '{$paleta['nombre']}' restaurada al núcleo."]);
            exit();
        } else {
            registrar_evento_elite('LOGICA_FAIL', "Error de lógica: El ID {$pid} no existe en paletas_elite.");
            throw new Exception("La identidad seleccionada no existe.");
        }
    }

    if ($accion === 'guardar_paleta') {
        $nombre = trim(limpiar_texto_utf8(obtener_post('nombre')) ?: 'Nueva Identidad');
        $p = limpiar_texto_utf8(obtener_post('primary_color')) ?: '';
        $a = limpiar_texto_utf8(obtener_post('accent_color')) ?: '';
        $i = limpiar_texto_utf8(obtener_post('info_color')) ?: '';
        $s = limpiar_texto_utf8(obtener_post('success_color')) ?: '';
        $d_color = limpiar_texto_utf8(obtener_post('danger_color')) ?: '';
        $id = obtener_post('id', FILTER_VALIDATE_INT);
        $id = ($id !== null && $id !== false && $id > 0) ? $id : null;

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

    if ($accion === 'borrar_paleta') {
        $id = obtener_post('id', FILTER_VALIDATE_INT);
        if ($id === null || $id === false) {
            throw new Exception("ID de paleta inválido.");
        }
        
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

    $menu_style = obtener_post('menu_style');
    if ($menu_style !== null) {
        $valor = trim($menu_style);
        registrar_evento_elite('LOGICA', "Cambiando Arquitectura a: {$valor}");
        $db->prepare("DELETE FROM ajustes_estetica WHERE clave = 'menu_style'")->execute();
        $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES ('menu_style', ?)")->execute([$valor]);
    }

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
    $postData = filter_input_array(INPUT_POST, FILTER_DEFAULT) ?? [];
    foreach ($postData as $clave => $valor) {
        $es_valido = false;
        if (in_array($clave, $whiteList)) {
            $es_valido = true;
        } elseif (strpos($clave, 'khronos_') === 0) {
            $es_valido = true;
        }
        if (!$es_valido) continue;
        
        $valor = is_array($valor) ? implode(',', $valor) : $valor;
        
        $db->prepare("DELETE FROM ajustes_estetica WHERE clave = ?")->execute([$clave]);
        $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES (?, ?)")->execute([$clave, $valor]);
        $procesados++;
    }

    // 🏛️ PROCESAR SUBIDA DE LOGO INSTITUCIONAL
    $logo_actualizado = null;
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $max_size = 5 * 1024 * 1024; // 5MB
        $allowed_ext = ['png', 'jpg', 'jpeg', 'svg', 'webp'];
        
        if ($_FILES['school_logo']['size'] > $max_size) {
            throw new Exception("El logo excede el tamaño máximo permitido (5MB).");
        }
        
        $file_ext = strtolower(pathinfo($_FILES['school_logo']['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Formato de archivo no permitido. Use: " . implode(', ', $allowed_ext));
        }
        
        $upload_dir = __DIR__ . '/../../uploads/branding/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = 'logo_institucional_' . time() . '.' . $file_ext;
        $destination = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['school_logo']['tmp_name'], $destination)) {
            $logo_path = 'uploads/branding/' . $file_name;
            
            $db->prepare("DELETE FROM ajustes_estetica WHERE clave = 'school_logo'")->execute();
            $db->prepare("INSERT INTO ajustes_estetica (clave, valor) VALUES ('school_logo', ?)")->execute([$logo_path]);
            
            $logo_actualizado = $logo_path;
            $procesados++;
            registrar_evento_elite('SUCCESS', "Logo institucional actualizado con éxito: {$logo_path}");
        } else {
            throw new Exception("Error al mover el archivo de logo al directorio de destino.");
        }
    }

    registrar_evento_elite('SUCCESS', "Sincronización finalizada. {$procesados} variables procesadas.");

    if (ob_get_length()) ob_clean();
    $resp = ['status' => 'success', 'message' => 'Sincronización Élite Finalizada.'];
    if ($logo_actualizado !== null) {
        $resp['logo_path'] = $logo_actualizado;
    }
    echo json_encode($resp);

} catch (Throwable $e) {
    registrar_evento_elite('FATAL_ERROR', $e->getMessage());
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();