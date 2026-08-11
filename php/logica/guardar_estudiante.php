<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/GUARDAR_ESTUDIANTE.PHP - PROCESADOR DE ALTA/ACTUALIZACIÓN ELITE v1.1
header('Content-Type: application/json');
require_once '../db.php';
require_once '../auth.php';

try {
    proteccion_extrema();

    // 🛡️ CAPA 2: VALIDACIÓN DE PERMISOS
    if (!tiene_permiso('matricula')) { 
        throw new Exception("Acceso denegado: Rango institucional insuficiente."); 
    }

    $identificacion = $_POST['identificacion'] ?? '';
    if (empty($identificacion)) throw new Exception("La identificación es obligatoria.");

    $nombre = mb_strtoupper($_POST['nombre'] ?? '', 'UTF-8');
    $apellido = mb_strtoupper($_POST['apellido'] ?? '', 'UTF-8');
    $curso_id = ($_POST['curso_id'] === '' || $_POST['curso_id'] === 'null') ? null : (int)$_POST['curso_id'];
    $promedio = (float)($_POST['promedio'] ?? 0);
    $tipo_documento = $_POST['tipo_documento'] ?? '';
    $rh = $_POST['tipo_sangre'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $email = mb_strtolower($_POST['email'] ?? '', 'UTF-8');
    $celular = $_POST['celular'] ?? '';
    $es_antiguo = (isset($_POST['es_antiguo']) && $_POST['es_antiguo'] == 1) ? 1 : 0;

    // Obtener nombre del curso para compatibilidad legacy
    $nombre_curso = 'SIN ASIGNAR';
    if ($curso_id) {
        $stmt_c = $db->prepare("SELECT nombre_curso FROM cursos WHERE id = ?");
        $stmt_c->execute([$curso_id]);
        $nombre_curso = $stmt_c->fetchColumn() ?: 'SIN ASIGNAR';
    }

    // 🛡️ DETECTOR DE EXISTENCIA
    $check = $db->prepare('SELECT COUNT(*) FROM estudiantes WHERE identificacion = :ide');
    $check->bindValue(':ide', $identificacion, PDO::PARAM_STR);
    $check->execute();
    if ((int)$check->fetchColumn() > 0) {
        throw new Exception("La identificación " . htmlspecialchars($identificacion) . " ya está registrada en el sistema.");
    }

    // INSERCIÓN
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

        // 🏛️ SOBERANÍA: Crear cuenta de usuario de estudiante si es un registro nuevo
        $pass_hash = password_hash($identificacion, PASSWORD_DEFAULT);
        $stmt_usr = $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id, estudiante_id) VALUES (:u, :p, :e, :n, 5, :eid)");
        $stmt_usr->execute([
            ':u' => $identificacion,
            ':p' => $pass_hash,
            ':e' => $email,
            ':n' => $nombre . ' ' . $apellido,
            ':eid' => $target_id
        ]);

        // 🎓 PERSISTENCIA DE DATOS ADICIONALES Y ACUDIENTES
        $edad_val = null;
        if (!empty($_POST['fecha_nacimiento'])) {
            try {
                $birthDate = new DateTime($_POST['fecha_nacimiento']);
                $today = new DateTime();
                $edad_val = $today->diff($birthDate)->y;
            } catch (Throwable $t) {}
        } else if (!empty($_POST['edad'])) {
            $edad_val = (int)$_POST['edad'];
        }

        // Autoincrementar y estructurar Folio de Matrícula (ej: 2026-0001) si no se especifica
        $folio = !empty($_POST['folio_matricula']) ? trim($_POST['folio_matricula']) : '';
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
            ':lugar_nac'   => !empty($_POST['lugar_nacimiento']) ? trim($_POST['lugar_nacimiento']) : null,
            ':fecha_nac'   => !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null,
            ':edad'        => $edad_val,
            ':nacionalidad'=> !empty($_POST['nacionalidad']) ? trim($_POST['nacionalidad']) : null,
            ':col_ant'     => !empty($_POST['colegio_anterior']) ? trim($_POST['colegio_anterior']) : null,
            ':dir_est'     => !empty($_POST['direccion_estudiante']) ? trim($_POST['direccion_estudiante']) : null,
            ':folio'       => $folio,
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

        // Procesar carga de Fotografía Digital 3x4 del Estudiante si se ha adjuntado desde el formulario de matrícula
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
                    
                    // Asegurar idempotencia de columna foto en la tabla
                    try {
                        $db->exec("ALTER TABLE estudiantes_datos_adicionales ADD COLUMN foto VARCHAR(255) NULL");
                    } catch (Throwable $th) {}

                    // Guardar/Actualizar en estudiantes_datos_adicionales
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

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit();
?>
