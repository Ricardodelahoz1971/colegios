<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();

header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('configuracion')) {
        throw new Exception("Acceso denegado: Privilegios insuficientes.");
    }

    session_write_close();

    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) ?? filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING) ?? '';

    if ($action === 'listar') {
        $stmt = $db->prepare("SELECT id, nombre, descripcion, tipo, activo, margen_superior, margen_inferior, tipo_documento, tamano_lienzo FROM formatos_matricula ORDER BY id DESC");
        $stmt->execute();
        $formatos = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $formatos]);
        exit();
    }

    if ($action === 'obtener_datos_preview') {
        $sqlEstudiante = "SELECT e.*, c.nombre_curso, da.*
                          FROM estudiantes e
                          LEFT JOIN cursos c ON e.curso_id = c.id
                          LEFT JOIN estudiantes_datos_adicionales da ON e.id = da.estudiante_id
                          ORDER BY e.id ASC
                          LIMIT 1";
        $stmtEstudiante = $db->prepare($sqlEstudiante);
        $stmtEstudiante->execute();
        $estudiante = $stmtEstudiante->fetch(PDO::FETCH_ASSOC);

        if (!$estudiante) {
            throw new Exception("No hay estudiantes registrados en la base de datos.");
        }

        $sqlConfig = "SELECT clave, valor FROM configuracion_global WHERE clave IN 
                      ('school_name', 'school_motto', 'colegio_nit', 'colegio_resolucion', 'rector_nombre', 'secretaria_nombre', 'anio_lectivo_oficial')";
        $stmtConfig = $db->prepare($sqlConfig);
        $stmtConfig->execute();
        $configRows = $stmtConfig->fetchAll(PDO::FETCH_ASSOC);
        $config = [];
        foreach ($configRows as $row) {
            $config[$row['clave']] = $row['valor'];
        }

        $stmtAjustes = $db->prepare('SELECT clave, valor FROM ajustes_estetica');
        $stmtAjustes->execute();
        $ajustesEstetica = $stmtAjustes->fetchAll(PDO::FETCH_KEY_PAIR);

        $school_name = $ajustesEstetica['school_name'] ?? $config['school_name'] ?? 'SISTEMA ESCOLAR ÉLITE';
        $school_motto = $ajustesEstetica['school_motto'] ?? $config['school_motto'] ?? 'Excelencia en Gestión Educativa';
        $colegio_nit = $ajustesEstetica['colegio_nit'] ?? $config['colegio_nit'] ?? '';
        $colegio_resolucion = $ajustesEstetica['colegio_resolucion'] ?? $config['colegio_resolucion'] ?? '';

        $rector_nombre = '';
        $stmtRector = $db->prepare('SELECT nombre FROM usuarios WHERE rol_id = 3 LIMIT 1');
        $stmtRector->execute();
        $rector_nombre = $stmtRector->fetchColumn() ?: 'Rector Institucional';

        $secretaria_nombre = '';
        $stmtSecretaria = $db->prepare('SELECT nombre FROM usuarios WHERE rol_id = 4 LIMIT 1');
        $stmtSecretaria->execute();
        $secretaria_nombre = $stmtSecretaria->fetchColumn() ?: 'Secretaria Académica';

        $fechaNacimiento = $estudiante['fecha_nacimiento'] ?? '';
        $edad = '';
        if (!empty($fechaNacimiento)) {
            $fechaNac = new DateTime($fechaNacimiento);
            $hoy = new DateTime();
            $diferencia = $hoy->diff($fechaNac);
            $edad = $diferencia->y . ' años';
        }

        $tipoDocEstudiante = $estudiante['tipo_documento'] ?? 'T.I.';
        $identificacionEstudiante = $estudiante['identificacion'] ?? '';
        $documentoCompleto = trim($tipoDocEstudiante . ' ' . $identificacionEstudiante);

        $tipoDocPadre = $estudiante['padre_tipo_documento'] ?? 'C.C.';
        $documentoPadre = $estudiante['padre_documento'] ?? '';
        $documentoPadreCompleto = trim($tipoDocPadre . ' ' . $documentoPadre);

        $tipoDocMadre = $estudiante['madre_tipo_documento'] ?? 'C.C.';
        $documentoMadre = $estudiante['madre_documento'] ?? '';
        $documentoMadreCompleto = trim($tipoDocMadre . ' ' . $documentoMadre);

        $fechaActual = !empty($estudiante['fecha_registro']) ? date('d/m/Y', strtotime($estudiante['fecha_registro'])) : date('d/m/Y');
        $anioLectivo = $config['anio_lectivo_oficial'] ?? date('Y');

        $data = [
            'estudiante_nombre' => trim(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? '')),
            'estudiante_documento' => $documentoCompleto,
            'estudiante_tipo_documento' => $tipoDocEstudiante,
            'estudiante_rh' => $estudiante['rh'] ?? '',
            'estudiante_genero' => $estudiante['genero'] ?? '',
            'estudiante_celular' => $estudiante['celular'] ?? '',
            'estudiante_email' => $estudiante['email'] ?? '',
            'estudiante_fecha_nacimiento' => $estudiante['fecha_nacimiento'] ?? '',
            'estudiante_edad' => $edad,
            'estudiante_lugar_nacimiento' => $estudiante['lugar_nacimiento'] ?? '',
            'estudiante_nacionalidad' => $estudiante['nacionalidad'] ?? 'COLOMBIANA',
            'estudiante_colegio_anterior' => $estudiante['colegio_anterior'] ?? 'Ninguno',
            'estudiante_direccion' => $estudiante['direccion_estudiante'] ?? '',
            'estudiante_folio' => $estudiante['folio_matricula'] ?? '',
            'padre_nombre' => $estudiante['padre_nombre'] ?? '',
            'padre_documento' => $documentoPadreCompleto,
            'padre_documento_expedicion' => $estudiante['padre_documento_expedicion'] ?? '',
            'padre_celular' => $estudiante['padre_celular'] ?? '',
            'padre_telefono' => $estudiante['padre_telefono'] ?? '',
            'padre_direccion' => $estudiante['padre_direccion'] ?? '',
            'padre_profesion' => $estudiante['padre_profesion'] ?? '',
            'padre_email' => $estudiante['padre_email'] ?? '',
            'madre_nombre' => $estudiante['madre_nombre'] ?? '',
            'madre_documento' => $documentoMadreCompleto,
            'madre_documento_expedicion' => $estudiante['madre_documento_expedicion'] ?? '',
            'madre_celular' => $estudiante['madre_celular'] ?? '',
            'madre_telefono' => $estudiante['madre_telefono'] ?? '',
            'madre_direccion' => $estudiante['madre_direccion'] ?? '',
            'madre_profesion' => $estudiante['madre_profesion'] ?? '',
            'madre_email' => $estudiante['madre_email'] ?? '',
            'colegio_nombre' => $school_name,
            'colegio_lema' => $school_motto,
            'colegio_nit' => $colegio_nit,
            'colegio_resolucion' => $colegio_resolucion,
            'rector_nombre' => $rector_nombre,
            'secretaria_nombre' => $secretaria_nombre,
            'curso_asignado' => $estudiante['nombre_curso'] ?? 'Sin Curso',
            'jornada_escolar' => $estudiante['jornada'] ?? '',
            'fecha_registro' => $fechaActual,
            'fecha_impresion' => date('d/m/Y'),
            'anio_lectivo' => $anioLectivo
        ];

        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit();
    }

    if ($action === 'obtener') {
        $id = (int)(filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT) ?? filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) ?? 0);
        if ($id <= 0) {
            throw new Exception("Identificador de formato no válido.");
        }
        $stmt = $db->prepare("SELECT * FROM formatos_matricula WHERE id = ?");
        $stmt->execute([$id]);
        $formato = $stmt->fetch();
        if (!$formato) {
            throw new Exception("El formato solicitado no existe.");
        }

        if (!empty($formato['configuracion_json'])) {
            $config_parsed = json_decode($formato['configuracion_json'], true);
            if (is_array($config_parsed) && isset($config_parsed['bloques'])) {
                $formato['configuracion_json'] = $config_parsed['bloques'];
                $formato['zonas_config'] = $config_parsed['zonas'] ?? null;
            } else {
                $formato['configuracion_json'] = $config_parsed;
            }
        }

        echo json_encode(['status' => 'success', 'data' => $formato]);
        exit();
    }

    if ($action === 'guardar') {
        $id = (int)(filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) ?? 0);
        $nombre = trim(filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING) ?? '');
        $descripcion = trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING) ?? '');
        $contenido_html = trim(filter_input(INPUT_POST, 'contenido_html', FILTER_SANITIZE_STRING) ?? '');
        $configuracion_json = trim(filter_input(INPUT_POST, 'configuracion_json', FILTER_SANITIZE_STRING) ?? '');
        $zonas_config = trim(filter_input(INPUT_POST, 'zonas_config', FILTER_SANITIZE_STRING) ?? '');
        $margen_superior = (int)(filter_input(INPUT_POST, 'margen_superior', FILTER_SANITIZE_NUMBER_INT) ?? 20);
        $margen_inferior = (int)(filter_input(INPUT_POST, 'margen_inferior', FILTER_SANITIZE_NUMBER_INT) ?? 20);
        $margen_izquierdo = (int)(filter_input(INPUT_POST, 'margen_izquierdo', FILTER_SANITIZE_NUMBER_INT) ?? 20);
        $margen_derecho = (int)(filter_input(INPUT_POST, 'margen_derecho', FILTER_SANITIZE_NUMBER_INT) ?? 20);
        $tipo_documento = trim(filter_input(INPUT_POST, 'tipo_documento', FILTER_SANITIZE_STRING) ?? 'matricula');
        $tamano_lienzo = trim(filter_input(INPUT_POST, 'tamano_lienzo', FILTER_SANITIZE_STRING) ?? 'carta');

        if (!empty($zonas_config)) {
            $bloques = json_decode($configuracion_json, true) ?? [];
            $zonas_data = json_decode($zonas_config, true) ?? [];
            $config_final = [
                'bloques' => $bloques,
                'zonas' => $zonas_data
            ];
            $configuracion_json = json_encode($config_final, JSON_UNESCAPED_UNICODE);
        }

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
        if (empty($configuracion_json)) {
            throw new Exception("El esquema JSON no puede estar vacío.");
        }

        if ($id > 0) {
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
        $id = (int)(filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) ?? 0);
        if ($id <= 0) {
            throw new Exception("Identificador no válido.");
        }

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
        $id = (int)(filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT) ?? 0);
        $activo = (int)(filter_input(INPUT_POST, 'activo', FILTER_SANITIZE_NUMBER_INT) ?? 1);
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
?>