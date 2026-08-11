<?php
declare(strict_types=1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

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

    // 🛡️ CAPA 3: AUTORIZACIÓN POR ROL (Coordinador / Administrador)
    $mi_rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
    if ($mi_rol_nombre !== 'administrador' && $mi_rol_nombre !== 'coordinador') {
        throw new Exception("Acceso denegado: Su rol no posee autoría para esta acción.");
    }

    $accion = $_POST['accion'] ?? '';
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');

        if (empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception("Todos los campos del receso son requeridos.");
        }

        $t_inicio = strtotime($fecha_inicio);
        $t_fin = strtotime($fecha_fin);

        if ($t_inicio === false || $t_fin === false) {
            throw new Exception("Las fechas provistas no son válidas.");
        }

        if ($t_inicio > $t_fin) {
            throw new Exception("La fecha de inicio debe ser anterior o igual a la fecha de fin.");
        }

        // 🏛️ DETECTAR PROCESOS / RECESOS DUPLICADOS POR NOMBRE
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM recesos_escolares WHERE LOWER(nombre) = LOWER(:nom)");
        $stmt_check->bindValue(':nom', $nombre, PDO::PARAM_STR);
        $stmt_check->execute();
        if ((int)$stmt_check->fetchColumn() > 0) {
            throw new Exception("Ya existe un receso escolar registrado bajo el nombre '{$nombre}'.");
        }

        // 🏛️ VALIDACIÓN DE CLASE SUPERIOR: EVITAR SUPERPOSICIÓN / COLISIÓN DE FECHAS
        $ini_date = date('Y-m-d', $t_inicio);
        $fin_date = date('Y-m-d', $t_fin);
        
        $stmt_overlap = $db->prepare("
            SELECT nombre, fecha_inicio, fecha_fin 
            FROM recesos_escolares 
            WHERE (:ini BETWEEN fecha_inicio AND fecha_fin)
               OR (:fin BETWEEN fecha_inicio AND fecha_fin)
               OR (fecha_inicio BETWEEN :ini2 AND :fin2)
            LIMIT 1
        ");
        $stmt_overlap->bindValue(':ini', $ini_date, PDO::PARAM_STR);
        $stmt_overlap->bindValue(':fin', $fin_date, PDO::PARAM_STR);
        $stmt_overlap->bindValue(':ini2', $ini_date, PDO::PARAM_STR);
        $stmt_overlap->bindValue(':fin2', $fin_date, PDO::PARAM_STR);
        $stmt_overlap->execute();
        
        $colision = $stmt_overlap->fetch(PDO::FETCH_ASSOC);
        if ($colision) {
            $col_ini = date('d/m/Y', strtotime($colision['fecha_inicio']));
            $col_fin = date('d/m/Y', strtotime($colision['fecha_fin']));
            throw new Exception("Superposición de Fechas: El rango seleccionado colisiona con el receso '{$colision['nombre']}' ({$col_ini} a {$col_fin}).");
        }

        // Insertar en la BD
        $stmt = $db->prepare("INSERT INTO recesos_escolares (nombre, fecha_inicio, fecha_fin) VALUES (:nom, :ini, :fin)");
        $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':ini', date('Y-m-d', $t_inicio), PDO::PARAM_STR);
        $stmt->bindValue(':fin', date('Y-m-d', $t_fin), PDO::PARAM_STR);
        $stmt->execute();
        $nuevo_id = (int)$db->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'id' => $nuevo_id,
            'message' => 'El receso escolar ha sido registrado exitosamente.'
        ]);
        exit;

    } elseif ($accion === 'eliminar') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            throw new Exception("ID de receso inválido.");
        }

        $stmt = $db->prepare("DELETE FROM recesos_escolares WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'El receso escolar ha sido eliminado exitosamente.'
        ]);
        exit;

    } elseif ($accion === 'editar') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = trim($_POST['nombre'] ?? '');
        $fecha_inicio = trim($_POST['fecha_inicio'] ?? '');
        $fecha_fin = trim($_POST['fecha_fin'] ?? '');

        if ($id <= 0 || empty($nombre) || empty($fecha_inicio) || empty($fecha_fin)) {
            throw new Exception("Todos los campos del receso son requeridos.");
        }

        $t_inicio = strtotime($fecha_inicio);
        $t_fin = strtotime($fecha_fin);

        if ($t_inicio === false || $t_fin === false) {
            throw new Exception("Las fechas provistas no son válidas.");
        }

        if ($t_inicio > $t_fin) {
            throw new Exception("La fecha de inicio debe ser anterior o igual a la fecha de fin.");
        }

        // 🏛️ DETECTAR PROCESOS / RECESOS DUPLICADOS POR NOMBRE (excluyendo el propio registro)
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM recesos_escolares WHERE LOWER(nombre) = LOWER(:nom) AND id != :id");
        $stmt_check->bindValue(':nom', $nombre, PDO::PARAM_STR);
        $stmt_check->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt_check->execute();
        if ((int)$stmt_check->fetchColumn() > 0) {
            throw new Exception("Ya existe otro receso escolar registrado bajo el nombre '{$nombre}'.");
        }

        // 🏛️ VALIDACIÓN DE CLASE SUPERIOR: EVITAR SUPERPOSICIÓN / COLISIÓN DE FECHAS (excluyendo el propio registro)
        $ini_date = date('Y-m-d', $t_inicio);
        $fin_date = date('Y-m-d', $t_fin);
        
        $stmt_overlap = $db->prepare("
            SELECT nombre, fecha_inicio, fecha_fin 
            FROM recesos_escolares 
            WHERE id != :id 
              AND ((:ini BETWEEN fecha_inicio AND fecha_fin)
               OR (:fin BETWEEN fecha_inicio AND fecha_fin)
               OR (fecha_inicio BETWEEN :ini2 AND :fin2))
            LIMIT 1
        ");
        $stmt_overlap->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt_overlap->bindValue(':ini', $ini_date, PDO::PARAM_STR);
        $stmt_overlap->bindValue(':fin', $fin_date, PDO::PARAM_STR);
        $stmt_overlap->bindValue(':ini2', $ini_date, PDO::PARAM_STR);
        $stmt_overlap->bindValue(':fin2', $fin_date, PDO::PARAM_STR);
        $stmt_overlap->execute();
        
        $colision = $stmt_overlap->fetch(PDO::FETCH_ASSOC);
        if ($colision) {
            $col_ini = date('d/m/Y', strtotime($colision['fecha_inicio']));
            $col_fin = date('d/m/Y', strtotime($colision['fecha_fin']));
            throw new Exception("Superposición de Fechas: El rango seleccionado colisiona con el receso '{$colision['nombre']}' ({$col_ini} a {$col_fin}).");
        }

        // Actualizar en la BD
        $stmt = $db->prepare("UPDATE recesos_escolares SET nombre = :nom, fecha_inicio = :ini, fecha_fin = :fin WHERE id = :id");
        $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
        $stmt->bindValue(':ini', $ini_date, PDO::PARAM_STR);
        $stmt->bindValue(':fin', $fin_date, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'El receso escolar ha sido modificado exitosamente.'
        ]);
        exit;

    } else {
        throw new Exception("Acción no soportada.");
    }

} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
    exit;
}
