<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
/**
 * 🎓 PROCESADOR DE EDICIÓN DE ESTUDIANTES v1.1 (ELITE)
 */
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE AUTORIDAD
    if (!tiene_permiso('estudiantes')) {
        throw new Exception('Permisos insuficientes para esta operación.');
    }

    $id = $_POST['id'] ?? null;
    if (!$id) throw new Exception('Identificador de estudiante ausente.');

    // Mapeo manual para asegurar integridad
    $params = [
        ':ide'  => $_POST['identificacion'] ?? '',
        ':nom'  => mb_strtoupper($_POST['nombre'] ?? '', 'UTF-8'),
        ':ape'  => mb_strtoupper($_POST['apellido'] ?? '', 'UTF-8'),
        ':cid'  => ($_POST['curso_id'] === '' || $_POST['curso_id'] === 'null' || $_POST['curso_id'] === '0') ? null : (int)$_POST['curso_id'],
        ':cur'  => 'ACTUALIZANDO...',
        ':pro'  => (float)($_POST['promedio'] ?? 0),
        ':tdoc' => $_POST['tipo_documento'] ?? '',
        ':rh'   => $_POST['tipo_sangre'] ?? '',
        ':gen'  => $_POST['genero'] ?? '',
        ':mail' => mb_strtolower($_POST['email'] ?? '', 'UTF-8'),
        ':cel'  => $_POST['celular'] ?? '',
        ':ant'  => (isset($_POST['es_antiguo']) && $_POST['es_antiguo'] == 1) ? 1 : 0,
        ':id'   => (int)$id
    ];

    // Sincronizar nombre de curso para compatibilidad legacy
    if ($params[':cid']) {
        $stmt_c = $db->prepare("SELECT nombre_curso FROM cursos WHERE id = ?");
        $stmt_c->execute([$params[':cid']]);
        $params[':cur'] = $stmt_c->fetchColumn() ?: 'SIN ASIGNAR';
    } else {
        $params[':cur'] = 'SIN ASIGNAR';
    }

    $sql = "UPDATE estudiantes SET 
                identificacion = :ide, nombre = :nom, apellido = :ape, 
                curso_id = :cid, curso = :cur, promedio = :pro,
                tipo_documento = :tdoc, rh = :rh, genero = :gen,
                email = :mail, celular = :cel, es_antiguo = :ant
            WHERE id = :id";

    $stmt = $db->prepare($sql);
    
    if ($stmt->execute($params)) {
        // 🎓 PERSISTENCIA DE DATOS ADICIONALES Y ACUDIENTES
        $estudiante_id = (int)$id;
        
        // Comprobar existencia previa de registro
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM estudiantes_datos_adicionales WHERE estudiante_id = ?");
        $stmt_check->execute([$estudiante_id]);
        $existe_adicional = ((int)$stmt_check->fetchColumn() > 0);
        
        // Calcular edad dinámicamente si se suministra fecha de nacimiento
        $edad_val = null;
        if (!empty($_POST['fecha_nacimiento'])) {
            try {
                $birthDate = new DateTime($_POST['fecha_nacimiento']);
                $today = new DateTime();
                $edad_val = $today->diff($birthDate)->y;
            } catch (Throwable $t) {}
        }
        
        $params_adicional = [
            ':lugar_nac'   => !empty($_POST['lugar_nacimiento']) ? trim($_POST['lugar_nacimiento']) : null,
            ':fecha_nac'   => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':edad'        => $edad_val,
            ':nacionalidad'=> !empty($_POST['nacionalidad']) ? trim($_POST['nacionalidad']) : null,
            ':col_ant'     => !empty($_POST['colegio_anterior']) ? trim($_POST['colegio_anterior']) : null,
            ':dir_est'     => !empty($_POST['direccion_estudiante']) ? trim($_POST['direccion_estudiante']) : null,
            ':folio'       => !empty($_POST['folio_matricula']) ? trim($_POST['folio_matricula']) : null,
            
            ':padre_nom'   => !empty($_POST['padre_nombre']) ? trim($_POST['padre_nombre']) : null,
            ':padre_tdoc'  => !empty($_POST['padre_tipo_documento']) ? trim($_POST['padre_tipo_documento']) : null,
            ':padre_doc'   => !empty($_POST['padre_documento']) ? trim($_POST['padre_documento']) : null,
            ':padre_doc_exp'=> !empty($_POST['padre_documento_expedicion']) ? trim($_POST['padre_documento_expedicion']) : null,
            ':padre_nac'   => !empty($_POST['padre_nacionalidad']) ? trim($_POST['padre_nacionalidad']) : null,
            ':padre_cel'   => !empty($_POST['padre_celular']) ? trim($_POST['padre_celular']) : null,
            ':padre_tel'   => !empty($_POST['padre_telefono']) ? trim($_POST['padre_telefono']) : null,
            ':padre_dir'   => !empty($_POST['padre_direccion']) ? trim($_POST['padre_direccion']) : null,
            ':padre_prof'  => !empty($_POST['padre_profesion']) ? trim($_POST['padre_profesion']) : null,
            ':padre_email' => !empty($_POST['padre_email']) ? trim($_POST['padre_email']) : null,
            
            ':madre_nom'   => !empty($_POST['madre_nombre']) ? trim($_POST['madre_nombre']) : null,
            ':madre_tdoc'  => !empty($_POST['madre_tipo_documento']) ? trim($_POST['madre_tipo_documento']) : null,
            ':madre_doc'   => !empty($_POST['madre_documento']) ? trim($_POST['madre_documento']) : null,
            ':madre_doc_exp'=> !empty($_POST['madre_documento_expedicion']) ? trim($_POST['madre_documento_expedicion']) : null,
            ':madre_nac'   => !empty($_POST['madre_nacionalidad']) ? trim($_POST['madre_nacionalidad']) : null,
            ':madre_cel'   => !empty($_POST['madre_celular']) ? trim($_POST['madre_celular']) : null,
            ':madre_tel'   => !empty($_POST['madre_telefono']) ? trim($_POST['madre_telefono']) : null,
            ':madre_dir'   => !empty($_POST['madre_direccion']) ? trim($_POST['madre_direccion']) : null,
            ':madre_prof'  => !empty($_POST['madre_profesion']) ? trim($_POST['madre_profesion']) : null,
            ':madre_email' => !empty($_POST['madre_email']) ? trim($_POST['madre_email']) : null,
            
            ':eid'         => $estudiante_id
        ];
        
        if ($existe_adicional) {
            $sql_adicional = "UPDATE estudiantes_datos_adicionales SET
                lugar_nacimiento = :lugar_nac, fecha_nacimiento = :fecha_nac, edad = :edad, nacionalidad = :nacionalidad, 
                colegio_anterior = :col_ant, direccion_estudiante = :dir_est, folio_matricula = :folio,
                padre_nombre = :padre_nom, padre_tipo_documento = :padre_tdoc, padre_documento = :padre_doc, padre_documento_expedicion = :padre_doc_exp,
                padre_nacionalidad = :padre_nac, padre_celular = :padre_cel, padre_telefono = :padre_tel, padre_direccion = :padre_dir, padre_profesion = :padre_prof, padre_email = :padre_email,
                madre_nombre = :madre_nom, madre_tipo_documento = :madre_tdoc, madre_documento = :madre_doc, madre_documento_expedicion = :madre_doc_exp,
                madre_nacionalidad = :madre_nac, madre_celular = :madre_cel, madre_telefono = :madre_tel, madre_direccion = :madre_dir, madre_profesion = :madre_prof, madre_email = :madre_email
                WHERE estudiante_id = :eid";
        } else {
            $sql_adicional = "INSERT INTO estudiantes_datos_adicionales (
                estudiante_id, lugar_nacimiento, fecha_nacimiento, edad, nacionalidad, colegio_anterior, direccion_estudiante, folio_matricula,
                padre_nombre, padre_tipo_documento, padre_documento, padre_documento_expedicion, padre_nacionalidad, padre_celular, padre_telefono, padre_direccion, padre_profesion, padre_email,
                madre_nombre, madre_tipo_documento, madre_documento, madre_documento_expedicion, madre_nacionalidad, madre_celular, madre_telefono, madre_direccion, madre_profesion, madre_email
            ) VALUES (
                :eid, :lugar_nac, :fecha_nac, :edad, :nacionalidad, :col_ant, :dir_est, :folio,
                :padre_nom, :padre_tdoc, :padre_doc, :padre_doc_exp, :padre_nac, :padre_cel, :padre_tel, :padre_dir, :padre_prof, :padre_email,
                :madre_nom, :madre_tdoc, :madre_doc, :madre_doc_exp, :madre_nac, :madre_cel, :madre_tel, :madre_dir, :madre_prof, :madre_email
            )";
        }
        
        $stmt_adicional = $db->prepare($sql_adicional);
        $stmt_adicional->execute($params_adicional);

        // Procesar carga de Fotografía Digital 3x4 del Estudiante si se ha adjuntado
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            if (in_array($ext, $allowed, true)) {
                $upload_dir = __DIR__ . '/../../uploads/fotos/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'estudiante_' . $id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $relative_path = 'uploads/fotos/' . $filename;
                    
                    // Asegurar idempotencia de columna foto en la tabla
                    try {
                        $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN foto VARCHAR(255) NULL");
                    } catch (Throwable $th) {}

                    // Guardar/Actualizar en estudiantes_datos_adicionales
                    $stmt_check_f = $db->prepare("SELECT COUNT(*) FROM estudiantes_datos_adicionales WHERE estudiante_id = ?");
                    $stmt_check_f->execute([(int)$id]);
                    $exists_f = ((int)$stmt_check_f->fetchColumn() > 0);

                    if ($exists_f) {
                        $stmt_f = $db->prepare("UPDATE estudiantes_datos_adicionales SET foto = ? WHERE estudiante_id = ?");
                        $stmt_f->execute([$relative_path, (int)$id]);
                    } else {
                        $stmt_f = $db->prepare("INSERT INTO estudiantes_datos_adicionales (estudiante_id, foto) VALUES (?, ?)");
                        $stmt_f->execute([(int)$id, $relative_path]);
                    }
                }
            }
        }

        echo json_encode([
            'status' => 'success', 
            'message' => '¡Expediente estudiantil actualizado!'
        ]);
    } else {
        throw new Exception('Fallo crítico en la actualización de la bóveda.');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}
exit();
?>

