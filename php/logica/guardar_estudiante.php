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

    if (!tiene_permiso('matricula')) {
        throw new Exception("Acceso denegado: Rango institucional insuficiente.");
    }

    $input = filter_input_array(INPUT_POST, [
        'identificacion' => FILTER_DEFAULT,
        'nombre' => FILTER_DEFAULT,
        'apellido' => FILTER_DEFAULT,
        'curso_id' => FILTER_VALIDATE_INT,
        'promedio' => FILTER_VALIDATE_FLOAT,
        'tipo_documento' => FILTER_DEFAULT,
        'tipo_sangre' => FILTER_DEFAULT,
        'genero' => FILTER_DEFAULT,
        'email' => FILTER_SANITIZE_EMAIL,
        'celular' => FILTER_DEFAULT,
        'es_antiguo' => FILTER_VALIDATE_BOOLEAN,
        'fecha_nacimiento' => FILTER_DEFAULT,
        'edad' => FILTER_VALIDATE_INT,
        'folio_matricula' => FILTER_DEFAULT,
        'lugar_nacimiento' => FILTER_DEFAULT,
        'nacionalidad' => FILTER_DEFAULT,
        'colegio_anterior' => FILTER_DEFAULT,
        'direccion_estudiante' => FILTER_DEFAULT,
        'padre_nombre' => FILTER_DEFAULT,
        'padre_tipo_documento' => FILTER_DEFAULT,
        'padre_documento' => FILTER_DEFAULT,
        'padre_documento_expedicion' => FILTER_DEFAULT,
        'padre_nacionalidad' => FILTER_DEFAULT,
        'padre_celular' => FILTER_DEFAULT,
        'padre_telefono' => FILTER_DEFAULT,
        'padre_direccion' => FILTER_DEFAULT,
        'padre_profesion' => FILTER_DEFAULT,
        'padre_email' => FILTER_SANITIZE_EMAIL,
        'madre_nombre' => FILTER_DEFAULT,
        'madre_tipo_documento' => FILTER_DEFAULT,
        'madre_documento' => FILTER_DEFAULT,
        'madre_documento_expedicion' => FILTER_DEFAULT,
        'madre_nacionalidad' => FILTER_DEFAULT,
        'madre_celular' => FILTER_DEFAULT,
        'madre_telefono' => FILTER_DEFAULT,
        'madre_direccion' => FILTER_DEFAULT,
        'madre_profesion' => FILTER_DEFAULT,
        'madre_email' => FILTER_SANITIZE_EMAIL
    ]);

    $identificacion = $input['identificacion'] ?? '';
    if (empty($identificacion)) throw new Exception("La identificación es obligatoria.");

    $nombre = mb_strtoupper($input['nombre'] ?? '', 'UTF-8');
    $apellido = mb_strtoupper($input['apellido'] ?? '', 'UTF-8');
    $curso_id = ($input['curso_id'] === '' || $input['curso_id'] === null) ? null : (int)$input['curso_id'];
    $promedio = (float)($input['promedio'] ?? 0);
    $tipo_documento = $input['tipo_documento'] ?? '';
    $rh = $input['tipo_sangre'] ?? '';
    $genero = $input['genero'] ?? '';
    $email = mb_strtolower($input['email'] ?? '', 'UTF-8');
    $celular = $input['celular'] ?? '';
    $es_antiguo = ($input['es_antiguo'] ?? false) ? 1 : 0;

    $nombre_curso = 'SIN ASIGNAR';
    if ($curso_id) {
        $stmt_c = $db->prepare("SELECT nombre_curso FROM cursos WHERE id = ?");
        $stmt_c->execute([$curso_id]);
        $nombre_curso = $stmt_c->fetchColumn() ?: 'SIN ASIGNAR';
    }

    $check = $db->prepare('SELECT COUNT(*) FROM estudiantes WHERE identificacion = :ide');
    $check->bindValue(':ide', $identificacion, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("La identificación " . htmlspecialchars($identificacion) . " ya está registrada en el sistema.");
    }

    $sql = "INSERT INTO estudiantes (identificacion, nombre, apellido, curso_id, curso, promedio, tipo_documento, rh, genero, email, celular, es_antiguo) 
            VALUES (:ide, :nom, :ape, :cid, :cur, :pro, :tdoc, :rh, :gen, :mail, :cel, :ant)";
    $msg_exito = "¡Nuevo estudiante matriculado en el sistema!";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':ide', $identificacion, PDO::PARAM_STR);
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':ape', $apellido, PDO::PARAM_STR);
    $stmt->bindValue(':cid', $curso_id, $curso_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':cur', $nombre_curso, PDO::PARAM_STR);
    $stmt->bindValue(':pro', $promedio, PDO::PARAM_STR);
    $stmt->bindValue(':tdoc', $tipo_documento, PDO::PARAM_STR);
    $stmt->bindValue(':rh', $rh, PDO::PARAM_STR);
    $stmt->bindValue(':gen', $genero, PDO::PARAM_STR);
    $stmt->bindValue(':mail', $email, PDO::PARAM_STR);
    $stmt->bindValue(':cel', $celular, PDO::PARAM_STR);
    $stmt->bindValue(':ant', $es_antiguo, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $target_id = (int)$db->lastInsertId();

        $pass_hash = password_hash($identificacion, PASSWORD_DEFAULT);
        
        $check_u = $db->prepare('SELECT id FROM usuarios WHERE usuario = :u');
        $check_u->execute([':u' => $identificacion]);
        $existing_user_id = $check_u->fetchColumn();

        if ($existing_user_id) {
            $stmt_usr = $db->prepare("UPDATE usuarios SET password = :p, email = :e, nombre = :n, rol_id = 5, estudiante_id = :eid WHERE id = :uid");
            $stmt_usr->execute([
                ':p' => $pass_hash,
                ':e' => $email,
                ':n' => $nombre . ' ' . $apellido,
                ':eid' => $target_id,
                ':uid' => $existing_user_id
            ]);
        } else {
            $stmt_usr = $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id, estudiante_id) VALUES (:u, :p, :e, :n, 5, :eid)");
            $stmt_usr->execute([
                ':u' => $identificacion,
                ':p' => $pass_hash,
                ':e' => $email,
                ':n' => $nombre . ' ' . $apellido,
                ':eid' => $target_id
            ]);
        }

        $edad_val = null;
        if (!empty($input['fecha_nacimiento'])) {
            try {
                $birthDate = new DateTime($input['fecha_nacimiento']);
                $today = new DateTime();
                $edad_val = $today->diff($birthDate)->y;
            } catch (Throwable $t) {}
        } else if (!empty($input['edad'])) {
            $edad_val = (int)$input['edad'];
        }

        $folio = !empty($input['folio_matricula']) ? trim($input['folio_matricula']) : '';
        if (empty($folio)) {
            $current_year = date('Y');
            $like_pattern = $current_year . '-%';
            $stmt_folio = $db->prepare("SELECT folio_matricula FROM estudiantes_datos_adicionales WHERE folio_matricula LIKE ? ORDER BY folio_matricula DESC LIMIT 1");
            $stmt_folio->execute([$like_pattern]);
            $last_folio = $stmt_folio->fetchColumn();
            
            $next_num = 1;
            if ($last_folio) {
                $parts = explode('-', $last_folio);
                if (count($parts) === 2) {
                    $next_num = (int)$parts[1] + 1;
                }
            }
            $folio = $current_year . '-' . str_pad((string)$next_num, 4, '0', STR_PAD_LEFT);
        }

        $params_adicional = [
            ':lugar_nac'   => !empty($input['lugar_nacimiento']) ? trim($input['lugar_nacimiento']) : null,
            ':fecha_nac'   => !empty($input['fecha_nacimiento']) ? $input['fecha_nacimiento'] : null,
            ':edad'        => $edad_val,
            ':nacionalidad'=> !empty($input['nacionalidad']) ? trim($input['nacionalidad']) : null,
            ':col_ant'     => !empty($input['colegio_anterior']) ? trim($input['colegio_anterior']) : null,
            ':dir_est'     => !empty($input['direccion_estudiante']) ? trim($input['direccion_estudiante']) : null,
            ':folio'       => $folio,
            ':padre_nom'   => !empty($input['padre_nombre']) ? trim($input['padre_nombre']) : null,
            ':padre_tdoc'  => !empty($input['padre_tipo_documento']) ? trim($input['padre_tipo_documento']) : null,
            ':padre_doc'   => !empty($input['padre_documento']) ? trim($input['padre_documento']) : null,
            ':padre_doc_exp'=> !empty($input['padre_documento_expedicion']) ? trim($input['padre_documento_expedicion']) : null,
            ':padre_nac'   => !empty($input['padre_nacionalidad']) ? trim($input['padre_nacionalidad']) : null,
            ':padre_cel'   => !empty($input['padre_celular']) ? trim($input['padre_celular']) : null,
            ':padre_tel'   => !empty($input['padre_telefono']) ? trim($input['padre_telefono']) : null,
            ':padre_dir'   => !empty($input['padre_direccion']) ? trim($input['padre_direccion']) : null,
            ':padre_prof'  => !empty($input['padre_profesion']) ? trim($input['padre_profesion']) : null,
            ':padre_email' => !empty($input['padre_email']) ? trim($input['padre_email']) : null,
            
            ':madre_nom'   => !empty($input['madre_nombre']) ? trim($input['madre_nombre']) : null,
            ':madre_tdoc'  => !empty($input['madre_tipo_documento']) ? trim($input['madre_tipo_documento']) : null,
            ':madre_doc'   => !empty($input['madre_documento']) ? trim($input['madre_documento']) : null,
            ':madre_doc_exp'=> !empty($input['madre_documento_expedicion']) ? trim($input['madre_documento_expedicion']) : null,
            ':madre_nac'   => !empty($input['madre_nacionalidad']) ? trim($input['madre_nacionalidad']) : null,
            ':madre_cel'   => !empty($input['madre_celular']) ? trim($input['madre_celular']) : null,
            ':madre_tel'   => !empty($input['madre_telefono']) ? trim($input['madre_telefono']) : null,
            ':madre_dir'   => !empty($input['madre_direccion']) ? trim($input['madre_direccion']) : null,
            ':madre_prof'  => !empty($input['madre_profesion']) ? trim($input['madre_profesion']) : null,
            ':madre_email' => !empty($input['madre_email']) ? trim($input['madre_email']) : null,
            
            ':eid'         => $target_id
        ];

        $sql_adicional = "INSERT INTO estudiantes_datos_adicionales (
            estudiante_id, lugar_nacimiento, fecha_nacimiento, edad, nacionalidad, colegio_anterior, direccion_estudiante, folio_matricula,
            padre_nombre, padre_tipo_documento, padre_documento, padre_documento_expedicion, padre_nacionalidad, padre_celular, padre_telefono, padre_direccion, padre_profesion, padre_email,
            madre_nombre, madre_tipo_documento, madre_documento, madre_documento_expedicion, madre_nacionalidad, madre_celular, madre_telefono, madre_direccion, madre_profesion, madre_email
        ) VALUES (
            :eid, :lugar_nac, :fecha_nac, :edad, :nacionalidad, :col_ant, :dir_est, :folio,
            :padre_nom, :padre_tdoc, :padre_doc, :padre_doc_exp, :padre_nac, :padre_cel, :padre_tel, :padre_dir, :padre_prof, :padre_email,
            :madre_nom, :madre_tdoc, :madre_doc, :madre_doc_exp, :madre_nac, :madre_cel, :madre_tel, :madre_dir, :madre_prof, :madre_email
        )";
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
                $filename = 'estudiante_' . $target_id . '_' . time() . '.' . $ext;
                $destination = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $relative_path = 'uploads/fotos/' . $filename;
                    
                    try {
                        $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN foto VARCHAR(255) NULL");
                    } catch (Throwable $th) {}

                    $stmt_check_f = $db->prepare("SELECT COUNT(*) FROM estudiantes_datos_adicionales WHERE estudiante_id = ?");
                    $stmt_check_f->execute([$target_id]);
                    $exists_f = ((int)$stmt_check_f->fetchColumn() > 0);

                    if ($exists_f) {
                        $stmt_f = $db->prepare("UPDATE estudiantes_datos_adicionales SET foto = ? WHERE estudiante_id = ?");
                        $stmt_f->execute([$relative_path, $target_id]);
                    } else {
                        $stmt_f = $db->prepare("INSERT INTO estudiantes_datos_adicionales (estudiante_id, foto) VALUES (?, ?)");
                        $stmt_f->execute([$target_id, $relative_path]);
                    }
                }
            }
        }

        echo json_encode(['status' => 'success', 'message' => $msg_exito]);
    } else {
        throw new Exception("Error al procesar en la bóveda de datos.");
    }

} catch (PDOException $pe) {
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
        $msg = 'La identificación o usuario ingresado ya existe en la base de datos.';
    }
    echo json_encode(['status' => 'error', 'message' => $msg]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>