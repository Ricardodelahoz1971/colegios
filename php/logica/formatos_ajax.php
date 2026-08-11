<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('configuracion')) { 
        throw new Exception("Acceso denegado: Permisos de configuración requeridos."); 
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'listar') {
        $stmt = $db->prepare("SELECT id, nombre, descripcion, tipo, activo, margen_superior, margen_inferior, tipo_documento, tamano_lienzo FROM formatos_matricula ORDER BY id DESC");
        $stmt->execute();
        $formatos = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $formatos]);
        exit();
    }

    if ($action === 'obtener') {
        $id = (int)($_REQUEST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception("Identificador de formato no válido.");
        }
        $stmt = $db->prepare("SELECT * FROM formatos_matricula WHERE id = ?");
        $stmt->execute([$id]);
        $formato = $stmt->fetch();
        if (!$formato) {
            throw new Exception("El formato solicitado no existe.");
        }
        echo json_encode(['status' => 'success', 'data' => $formato]);
        exit();
    }

    if ($action === 'guardar') {
        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $contenido_html = trim($_POST['contenido_html'] ?? '');
        $configuracion_json = trim($_POST['configuracion_json'] ?? '');
        $margen_superior = (int)($_POST['margen_superior'] ?? 20);
        $margen_inferior = (int)($_POST['margen_inferior'] ?? 20);
        $margen_izquierdo = (int)($_POST['margen_izquierdo'] ?? 20);
        $margen_derecho = (int)($_POST['margen_derecho'] ?? 20);
        $tipo_documento = trim($_POST['tipo_documento'] ?? 'matricula');
        $tamano_lienzo = trim($_POST['tamano_lienzo'] ?? 'carta');

        $tipos_permitidos = ['matricula', 'carne', 'certificado', 'constancia'];
        if (!in_array($tipo_documento, $tipos_permitidos, true)) {
            $tipo_documento = 'matricula';
        }

        $tamanos_permitidos = ['carta', 'media_carta', 'carne_v', 'carne_h'];
        if (!in_array($tamano_lienzo, $tamanos_permitidos, true)) {
            $tamano_lienzo = 'carta';
        }

        if (empty($nombre)) {
            throw new Exception("El nombre del formato es obligatorio.");
        }
        if (empty($contenido_html)) {
            throw new Exception("El contenido de la plantilla no puede estar vacío.");
        }

        if ($id > 0) {
            // Validar que exista
            $stmt_check = $db->prepare("SELECT tipo FROM formatos_matricula WHERE id = ?");
            $stmt_check->execute([$id]);
            $formato_ex = $stmt_check->fetch();
            if (!$formato_ex) {
                throw new Exception("El formato a actualizar no existe.");
            }

            $stmt = $db->prepare("UPDATE formatos_matricula SET nombre = ?, descripcion = ?, contenido_html = ?, configuracion_json = ?, margen_superior = ?, margen_inferior = ?, margen_izquierdo = ?, margen_derecho = ?, tipo_documento = ?, tamano_lienzo = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $contenido_html, $configuracion_json, $margen_superior, $margen_inferior, $margen_izquierdo, $margen_derecho, $tipo_documento, $tamano_lienzo, $id]);
            echo json_encode(['status' => 'success', 'message' => 'Formato actualizado correctamente.']);
        } else {
            $stmt = $db->prepare("INSERT INTO formatos_matricula (nombre, descripcion, contenido_html, configuracion_json, tipo, margen_superior, margen_inferior, margen_izquierdo, margen_derecho, tipo_documento, tamano_lienzo) VALUES (?, ?, ?, ?, 'personalizado', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $contenido_html, $configuracion_json, $margen_superior, $margen_inferior, $margen_izquierdo, $margen_derecho, $tipo_documento, $tamano_lienzo]);
            echo json_encode(['status' => 'success', 'message' => 'Nuevo formato guardado con éxito.']);
        }
        exit();
    }

    if ($action === 'eliminar') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            throw new Exception("Identificador no válido.");
        }

        // No permitir eliminar formatos prediseñados
        $stmt_check = $db->prepare("SELECT tipo FROM formatos_matricula WHERE id = ?");
        $stmt_check->execute([$id]);
        $tipo = $stmt_check->fetchColumn();
        if ($tipo === 'predisenado') {
            throw new Exception("No es posible eliminar un formato institucional prediseñado.");
        }

        $stmt = $db->prepare("DELETE FROM formatos_matricula WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status' => 'success', 'message' => 'Formato eliminado de la base de datos.']);
        exit();
    }

    if ($action === 'cambiar_estado') {
        $id = (int)($_POST['id'] ?? 0);
        $activo = (int)($_POST['activo'] ?? 1);
        if ($id <= 0) {
            throw new Exception("Identificador no válido.");
        }

        $stmt = $db->prepare("UPDATE formatos_matricula SET activo = ? WHERE id = ?");
        $stmt->execute([$activo, $id]);
        echo json_encode(['status' => 'success', 'message' => 'Estado del formato actualizado.']);
        exit();
    }

    throw new Exception("Acción no definida.");

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
