<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
proteccion_extrema();

// PHP/LOGICA/PROCESAR_CARGA_MASIVA.PHP - MOTOR DE INGESTIÓN MASIVA ELITE v2.0
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Acceso no permitido.');
    }

    if (!tiene_permiso('matricula') && !tiene_permiso('estudiantes')) {
        throw new Exception('Permisos insuficientes para realizar esta operación.');
    }

    if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error al subir el archivo o archivo no seleccionado.');
    }

    $fileTmpPath = $_FILES['archivo_csv']['tmp_name'];
    $handle = fopen($fileTmpPath, 'r');
    if (!$handle) {
        throw new Exception('No se pudo abrir el archivo CSV.');
    }

    // 1. Detectar y saltar BOM UTF-8 si existe
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    // 2. Leer Cabeceras y normalizar
    $cabeceras = fgetcsv($handle, 4000, ';');
    if (!$cabeceras) {
        fclose($handle);
        throw new Exception('El archivo CSV está vacío o no contiene cabeceras válidas.');
    }

    $cabeceras_norm = array_map(function($c) {
        return strtolower(trim((string)$c));
    }, $cabeceras);

    // 3. CARGAR MAPA DE CURSOS Y MAPA DE ESTUDIANTES PARA BÚSQUEDA RÁPIDA EN RAM
    $cursos_map = [];
    $sql_cursos = "SELECT id, nombre_curso FROM cursos";
    $stmt_c = $db->prepare($sql_cursos);
    $stmt_c->execute();
    while ($c = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
        $cursos_map[mb_strtoupper(trim($c['nombre_curso']), 'UTF-8')] = (int)$c['id'];
    }

    $estudiantes_existentes = [];
    $sql_estudiantes = "SELECT identificacion FROM estudiantes";
    $stmt_est = $db->prepare($sql_estudiantes);
    $stmt_est->execute();
    while ($id_existente = $stmt_est->fetchColumn()) {
        $estudiantes_existentes[trim((string)$id_existente)] = true;
    }

    // 4. PREPARAR ESTADÍSTICAS
    $exitos = 0;
    $duplicados = 0;
    $total_filas = 0;
    $errores = [];

    // 5. PREPARAR STATEMENTS DUALES PARA TRANSACCIÓN
    $db->beginTransaction();

    $stmt_est = $db->prepare("INSERT INTO estudiantes (
        identificacion, nombre, apellido, curso_id, curso, tipo_documento, rh, genero, email, celular, es_antiguo, promedio
    ) VALUES (
        :id, :nom, :ape, :cid, :cur, :tdoc, :rh, :gen, :mail, :cel, :ant, :pro
    )");

    $stmt_adic = $db->prepare("INSERT INTO estudiantes_datos_adicionales (
        estudiante_id, lugar_nacimiento, fecha_nacimiento, edad, nacionalidad, direccion_estudiante, folio_matricula, colegio_anterior,
        padre_nombre, padre_tipo_documento, padre_documento, padre_documento_expedicion, padre_nacionalidad, padre_celular, padre_telefono, padre_direccion, padre_profesion, padre_email,
        madre_nombre, madre_tipo_documento, madre_documento, madre_documento_expedicion, madre_nacionalidad, madre_celular, madre_telefono, madre_direccion, madre_profesion, madre_email
    ) VALUES (
        :eid, :lnac, :fnac, :edad, :nac, :dir, :folio, :colant,
        :p_nom, :p_tdoc, :p_doc, :p_docexp, :p_nac, :p_cel, :p_tel, :p_dir, :p_prof, :p_mail,
        :m_nom, :m_tdoc, :m_doc, :m_docexp, :m_nac, :m_cel, :m_tel, :m_dir, :m_prof, :m_mail
    )");

    $fila_num = 1;

    while (($data = fgetcsv($handle, 4000, ';')) !== FALSE) {
        $fila_num++;
        if (empty($data) || (count($data) === 1 && trim((string)$data[0]) === '')) {
            continue; // Saltar líneas vacías
        }

        // Mapear por nombres de columna si coinciden con cabeceras, o por posición
        $row = [];
        if (count($data) === count($cabeceras_norm)) {
            $row = array_combine($cabeceras_norm, $data);
        } else {
            // Asignación posicional segura
            $row = [
                'tipo_documento'        => $data[0] ?? 'TI',
                'identificacion'        => $data[1] ?? '',
                'tipo_sangre'           => $data[2] ?? 'O+',
                'nombre'                => $data[3] ?? '',
                'apellido'              => $data[4] ?? '',
                'genero'                => $data[5] ?? 'M',
                'email'                 => $data[6] ?? '',
                'celular'               => $data[7] ?? '',
                'fecha_nacimiento'      => $data[8] ?? null,
                'lugar_nacimiento'      => $data[9] ?? null,
                'nacionalidad'          => $data[10] ?? 'COLOMBIANA',
                'direccion_estudiante'  => $data[11] ?? null,
                'folio_matricula'       => $data[12] ?? null,
                'colegio_anterior'      => $data[13] ?? null,
                'curso'                 => $data[14] ?? 'SIN ASIGNAR',
                'es_antiguo'            => $data[15] ?? 0,
                // Padre
                'padre_nombre'          => $data[16] ?? null,
                'padre_tipo_documento' => $data[17] ?? null,
                'padre_documento'       => $data[18] ?? null,
                'padre_documento_expedicion' => $data[19] ?? null,
                'padre_nacionalidad'    => $data[20] ?? null,
                'padre_celular'         => $data[21] ?? null,
                'padre_telefono'        => $data[22] ?? null,
                'padre_direccion'       => $data[23] ?? null,
                'padre_profesion'       => $data[24] ?? null,
                'padre_email'           => $data[25] ?? null,
                // Madre
                'madre_nombre'          => $data[26] ?? null,
                'madre_tipo_documento' => $data[27] ?? null,
                'madre_documento'       => $data[28] ?? null,
                'madre_documento_expedicion' => $data[29] ?? null,
                'madre_nacionalidad'    => $data[30] ?? null,
                'madre_celular'         => $data[31] ?? null,
                'madre_telefono'        => $data[32] ?? null,
                'madre_direccion'       => $data[33] ?? null,
                'madre_profesion'       => $data[34] ?? null,
                'madre_email'           => $data[35] ?? null
            ];
        }

        $identificacion = trim((string)($row['identificacion'] ?? ''));
        $nombre = mb_strtoupper(trim((string)($row['nombre'] ?? '')), 'UTF-8');
        $apellido = mb_strtoupper(trim((string)($row['apellido'] ?? '')), 'UTF-8');

        if (empty($identificacion) || empty($nombre) || empty($apellido)) {
            continue; // Campos obligatorios mínimos
        }

        $total_filas++;

        // A. Verificar Duplicados
        if (isset($estudiantes_existentes[$identificacion])) {
            $duplicados++;
            continue;
        }
        $estudiantes_existentes[$identificacion] = true;

        // B. Mapeo de Curso
        $nom_curso = trim((string)($row['curso'] ?? 'SIN ASIGNAR'));
        $curso_key = mb_strtoupper($nom_curso, 'UTF-8');
        $curso_id = $cursos_map[$curso_key] ?? null;
        $nom_curso_final = $curso_id !== null ? $nom_curso : ($nom_curso !== '' ? $nom_curso : 'SIN ASIGNAR');

        $tdoc = trim((string)($row['tipo_documento'] ?? 'TI'));
        $rh = trim((string)($row['tipo_sangre'] ?? 'O+'));
        $gen = mb_strtoupper(trim((string)($row['genero'] ?? 'M')), 'UTF-8');
        $email = mb_strtolower(trim((string)($row['email'] ?? '')), 'UTF-8');
        $celular = trim((string)($row['celular'] ?? ''));
        $es_antiguo = intval($row['es_antiguo'] ?? 0) === 1 ? 1 : 0;
        $promedio = 0.0;

        // C. Inserción en 'estudiantes'
        $stmt_est->execute([
            ':id'   => $identificacion,
            ':nom'  => $nombre,
            ':ape'  => $apellido,
            ':cid'  => $curso_id,
            ':cur'  => $nom_curso_final,
            ':tdoc' => $tdoc,
            ':rh'   => $rh,
            ':gen'  => $gen,
            ':mail' => $email,
            ':cel'  => $celular,
            ':ant'  => $es_antiguo,
            ':pro'  => $promedio
        ]);

        $estudiante_id = (int)$db->lastInsertId();

        // D. Calcular edad si viene fecha de nacimiento
        $fecha_nac = !empty($row['fecha_nacimiento']) ? trim((string)$row['fecha_nacimiento']) : null;
        $edad_val = null;
        if (!empty($fecha_nac)) {
            try {
                $birthDate = new DateTime($fecha_nac);
                $today = new DateTime();
                $edad_val = $today->diff($birthDate)->y;
            } catch (Throwable $t) {}
        }

        // E. Inserción en 'estudiantes_datos_adicionales'
        $stmt_adic->execute([
            ':eid'      => $estudiante_id,
            ':lnac'     => !empty($row['lugar_nacimiento']) ? trim((string)$row['lugar_nacimiento']) : null,
            ':fnac'     => $fecha_nac,
            ':edad'     => $edad_val,
            ':nac'      => !empty($row['nacionalidad']) ? trim((string)$row['nacionalidad']) : 'COLOMBIANA',
            ':dir'      => !empty($row['direccion_estudiante']) ? trim((string)$row['direccion_estudiante']) : null,
            ':folio'    => !empty($row['folio_matricula']) ? trim((string)$row['folio_matricula']) : null,
            ':colant'   => !empty($row['colegio_anterior']) ? trim((string)$row['colegio_anterior']) : null,

            ':p_nom'    => !empty($row['padre_nombre']) ? trim((string)$row['padre_nombre']) : null,
            ':p_tdoc'   => !empty($row['padre_tipo_documento']) ? trim((string)$row['padre_tipo_documento']) : null,
            ':p_doc'    => !empty($row['padre_documento']) ? trim((string)$row['padre_documento']) : null,
            ':p_docexp' => !empty($row['padre_documento_expedicion']) ? trim((string)$row['padre_documento_expedicion']) : null,
            ':p_nac'    => !empty($row['padre_nacionalidad']) ? trim((string)$row['padre_nacionalidad']) : null,
            ':p_cel'    => !empty($row['padre_celular']) ? trim((string)$row['padre_celular']) : null,
            ':p_tel'    => !empty($row['padre_telefono']) ? trim((string)$row['padre_telefono']) : null,
            ':p_dir'    => !empty($row['padre_direccion']) ? trim((string)$row['padre_direccion']) : null,
            ':p_prof'   => !empty($row['padre_profesion']) ? trim((string)$row['padre_profesion']) : null,
            ':p_mail'   => !empty($row['padre_email']) ? trim((string)$row['padre_email']) : null,

            ':m_nom'    => !empty($row['madre_nombre']) ? trim((string)$row['madre_nombre']) : null,
            ':m_tdoc'   => !empty($row['madre_tipo_documento']) ? trim((string)$row['madre_tipo_documento']) : null,
            ':m_doc'    => !empty($row['madre_documento']) ? trim((string)$row['madre_documento']) : null,
            ':m_docexp' => !empty($row['madre_documento_expedicion']) ? trim((string)$row['madre_documento_expedicion']) : null,
            ':m_nac'    => !empty($row['madre_nacionalidad']) ? trim((string)$row['madre_nacionalidad']) : null,
            ':m_cel'    => !empty($row['madre_celular']) ? trim((string)$row['madre_celular']) : null,
            ':m_tel'    => !empty($row['madre_telefono']) ? trim((string)$row['madre_telefono']) : null,
            ':m_dir'    => !empty($row['madre_direccion']) ? trim((string)$row['madre_direccion']) : null,
            ':m_prof'   => !empty($row['madre_profesion']) ? trim((string)$row['madre_profesion']) : null,
            ':m_mail'   => !empty($row['madre_email']) ? trim((string)$row['madre_email']) : null
        ]);

        $exitos++;
    }

    $db->commit();
    fclose($handle);

    echo json_encode([
        'status' => 'success',
        'message' => "Proceso completado: {$exitos} estudiante(s) importado(s) exitosamente." . ($duplicados > 0 ? " ({$duplicados} omitido(s) por duplicidad)." : ""),
        'stats' => [
            'total' => $total_filas,
            'exitos' => $exitos,
            'duplicados' => $duplicados
        ]
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit();
