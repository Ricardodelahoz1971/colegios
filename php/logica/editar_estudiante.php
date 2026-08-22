<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
session_write_close();
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('estudiantes')) {
        throw new Exception('Permisos insuficientes para esta operación.');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) throw new Exception('Identificador de estudiante ausente.');

    $identificacion = trim(filter_var($input['identificacion'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $nombre = mb_strtoupper(trim(filter_var($input['nombre'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)), 'UTF-8');
    $apellido = mb_strtoupper(trim(filter_var($input['apellido'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS)), 'UTF-8');
    $curso_id_raw = trim(filter_var($input['curso_id'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $curso_id = ($curso_id_raw === '' || $curso_id_raw === 'null' || $curso_id_raw === '0') ? null : (int)$curso_id_raw;
    $promedio = (float)(filter_var($input['promedio'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
    $tipo_documento = trim(filter_var($input['tipo_documento'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $tipo_sangre = trim(filter_var($input['tipo_sangre'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $genero = trim(filter_var($input['genero'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $email = mb_strtolower(trim(filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL)), 'UTF-8');
    $celular = trim(filter_var($input['celular'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
    $es_antiguo = (filter_var($input['es_antiguo'] ?? 0, FILTER_VALIDATE_INT) === 1) ? 1 : 0;

    $params = [
        ':ide'  => $identificacion,
        ':nom'  => $nombre,
        ':ape'  => $apellido,
        ':cid'  => $curso_id,
        ':cur'  => 'ACTUALIZANDO...',
        ':pro'  => $promedio,
        ':tdoc' => $tipo_documento,
        ':rh'   => $tipo_sangre,
        ':gen'  => $genero,
        ':mail' => $email,
        ':cel'  => $celular,
        ':ant'  => $es_antiguo,
        ':id'   => $id
    ];

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
        $estudiante_id = $id;
        
        $stmt_check = $db->prepare("SELECT COUNT(*) FROM estudiantes_datos_adicionales WHERE estudiante_id = ?");
        $stmt_check->execute([$estudiante_id]);
        $existe_adicional = ((int)$stmt_check->fetchColumn() > 0);
        
        $fecha_nacimiento = trim(filter_var($input['fecha_nacimiento'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS));
        $edad_val = null;
        if (!empty($fecha_nacimiento)) {
            try {
                $birthDate = new DateTime($fecha_nacimiento);
                $today = new DateTime();
                $edad_val = $today->diff($birthDate)->y;
            } catch (Throwable $t) {}
        }
        
        $params_adicional = [
            ':lugar_nac'   => !empty($input['lugar_nacimiento']) ? trim(filter_var($input['lugar_nacimiento'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':fecha_nac'   => !empty($fecha_nacimiento) ? $fecha_nacimiento : null,
            ':edad'        => $edad_val,
            ':nacionalidad'=> !empty($input['nacionalidad']) ? trim(filter_var($input['nacionalidad'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':col_ant'     => !empty($input['colegio_anterior']) ? trim(filter_var($input['colegio_anterior'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':dir_est'     => !empty($input['direccion_estudiante']) ? trim(filter_var($input['direccion_estudiante'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':folio'       => !empty($input['folio_matricula']) ? trim(filter_var($input['folio_matricula'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            
            ':padre_nom'   => !empty($input['padre_nombre']) ? trim(filter_var($input['padre_nombre'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_tdoc'  => !empty($input['padre_tipo_documento']) ? trim(filter_var($input['padre_tipo_documento'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_doc'   => !empty($input['padre_documento']) ? trim(filter_var($input['padre_documento'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_doc_exp'=> !empty($input['padre_documento_expedicion']) ? trim(filter_var($input['padre_documento_expedicion'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_nac'   => !empty($input['padre_nacionalidad']) ? trim(filter_var($input['padre_nacionalidad'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_cel'   => !empty($input['padre_celular']) ? trim(filter_var($input['padre_celular'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_tel'   => !empty($input['padre_telefono']) ? trim(filter_var($input['padre_telefono'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_dir'   => !empty($input['padre_direccion']) ? trim(filter_var($input['padre_direccion'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_prof'  => !empty($input['padre_profesion']) ? trim(filter_var($input['padre_profesion'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':padre_email' => !empty($input['padre_email']) ? trim(filter_var($input['padre_email'], FILTER_SANITIZE_EMAIL)) : null,
            
            ':madre_nom'   => !empty($input['madre_nombre']) ? trim(filter_var($input['madre_nombre'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_tdoc'  => !empty($input['madre_tipo_documento']) ? trim(filter_var($input['madre_tipo_documento'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_doc'   => !empty($input['madre_documento']) ? trim(filter_var($input['madre_documento'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_doc_exp'=> !empty($input['madre_documento_expedicion']) ? trim(filter_var($input['madre_documento_expedicion'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_nac'   => !empty($input['madre_nacionalidad']) ? trim(filter_var($input['madre_nacionalidad'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_cel'   => !empty($input['madre_celular']) ? trim(filter_var($input['madre_celular'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_tel'   => !empty($input['madre_telefono']) ? trim(filter_var($input['madre_telefono'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_dir'   => !empty($input['madre_direccion']) ? trim(filter_var($input['madre_direccion'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_prof'  => !empty($input['madre_profesion']) ? trim(filter_var($input['madre_profesion'], FILTER_SANITIZE_SPECIAL_CHARS)) : null,
            ':madre_email' => !empty($input['madre_email']) ? trim(filter_var($input['madre_email'], FILTER_SANITIZE_EMAIL)) : null,
            
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
                    
                    try {
                        $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN foto VARCHAR(255) NULL");
                    } catch (Throwable $th) {}

                    $stmt_check_f = $db->prepare("SELECT COUNT(*) FROM estudiantes_datos_adicionales WHERE estudiante_id = ?");
                    $stmt_check_f->execute([$id]);
                    $exists_f = ((int)$stmt_check_f->fetchColumn() > 0);

                    if ($exists_f) {
                        $stmt_f = $db->prepare("UPDATE estudiantes_datos_adicionales SET foto = ? WHERE estudiante_id = ?");
                        $stmt_f->execute([$relative_path, $id]);
                    } else {
                        $stmt_f = $db->prepare("INSERT INTO estudiantes_datos_adicionales (estudiante_id, foto) VALUES (?, ?)");
                        $stmt_f->execute([$id, $relative_path]);
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