<?php
declare(strict_types=1);
header('Content-Type: application/json');
session_start();
require_once 'db.php';
require_once 'auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('matricula')) {
        throw new Exception('Acceso denegado o permisos insuficientes.');
    }
    session_write_close();

    $identificacion = filter_input(INPUT_POST, 'identificacion', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $curso_id = filter_input(INPUT_POST, 'curso_id', FILTER_VALIDATE_INT) ?? 0;
    
    if (empty($identificacion) || empty($nombre) || empty($apellido)) {
        throw new Exception('Faltan datos obligatorios para matricular.');
    }

    $check = $db->prepare("SELECT id FROM estudiantes WHERE identificacion = :id");
    $check->bindValue(':id', $identificacion, PDO::PARAM_STR);
    $check->execute();
    if ($check->fetch()) {
        throw new Exception('Este estudiante ya se encuentra registrado con la identificación: ' . $identificacion);
    }
    
    $c_stmt = $db->prepare("SELECT nombre_curso FROM cursos WHERE id = :cid");
    $c_stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $c_stmt->execute();
    $c_data = $c_stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_curso = $c_data['nombre_curso'] ?? 'Sin Asignar';

    $tipo_documento = filter_input(INPUT_POST, 'tipo_documento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $tipo_sangre = filter_input(INPUT_POST, 'tipo_sangre', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $genero = filter_input(INPUT_POST, 'genero', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
    $celular = filter_input(INPUT_POST, 'celular', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $es_antiguo = filter_input(INPUT_POST, 'es_antiguo', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    $sql = "INSERT INTO estudiantes (identificacion, nombre, apellido, curso_id, curso, tipo_documento, rh, genero, email, celular, es_antiguo) 
            VALUES (:id, :nom, :ape, :cid, :cur, :tdoc, :rh, :gen, :mail, :cel, :ant)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $identificacion, PDO::PARAM_STR);
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':ape', $apellido, PDO::PARAM_STR);
    $stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt->bindValue(':cur', $nombre_curso, PDO::PARAM_STR);
    $stmt->bindValue(':tdoc', $tipo_documento, PDO::PARAM_STR);
    $stmt->bindValue(':rh', $tipo_sangre, PDO::PARAM_STR);
    $stmt->bindValue(':gen', $genero, PDO::PARAM_STR);
    $stmt->bindValue(':mail', $email, PDO::PARAM_STR);
    $stmt->bindValue(':cel', $celular, PDO::PARAM_STR);
    $stmt->bindValue(':ant', $es_antiguo, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $nuevo_id = $db->lastInsertId();
        
        $sql_adicional = "INSERT INTO estudiantes_datos_adicionales (
            estudiante_id, lugar_nacimiento, fecha_nacimiento, edad, nacionalidad, colegio_anterior, direccion_estudiante, folio_matricula,
            padre_nombre, padre_tipo_documento, padre_documento, padre_documento_expedicion, padre_nacionalidad, padre_celular, padre_telefono, padre_direccion, padre_profesion, padre_email,
            madre_nombre, madre_tipo_documento, madre_documento, madre_documento_expedicion, madre_nacionalidad, madre_celular, madre_telefono, madre_direccion, madre_profesion, madre_email
        ) VALUES (
            :estudiante_id, :lugar_nacimiento, :fecha_nacimiento, :edad, :nacionalidad, :colegio_anterior, :direccion_estudiante, :folio_matricula,
            :padre_nombre, :padre_tipo_documento, :padre_documento, :padre_documento_expedicion, :padre_nacionalidad, :padre_celular, :padre_telefono, :padre_direccion, :padre_profesion, :padre_email,
            :madre_nombre, :madre_tipo_documento, :madre_documento, :madre_documento_expedicion, :madre_nacionalidad, :madre_celular, :madre_telefono, :madre_direccion, :madre_profesion, :madre_email
        )";
        $stmt_adicional = $db->prepare($sql_adicional);
        
        $fecha_nac = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_SPECIAL_CHARS) ?: null;
        $edad_val = filter_input(INPUT_POST, 'edad', FILTER_VALIDATE_INT) ?: null;
        
        $stmt_adicional->execute([
            ':estudiante_id' => $nuevo_id,
            ':lugar_nacimiento' => filter_input(INPUT_POST, 'lugar_nacimiento', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':fecha_nacimiento' => $fecha_nac,
            ':edad' => $edad_val,
            ':nacionalidad' => filter_input(INPUT_POST, 'nacionalidad', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':colegio_anterior' => filter_input(INPUT_POST, 'colegio_anterior', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':direccion_estudiante' => filter_input(INPUT_POST, 'direccion_estudiante', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':folio_matricula' => filter_input(INPUT_POST, 'folio_matricula', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_nombre' => filter_input(INPUT_POST, 'padre_nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_tipo_documento' => filter_input(INPUT_POST, 'padre_tipo_documento', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_documento' => filter_input(INPUT_POST, 'padre_documento', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_documento_expedicion' => filter_input(INPUT_POST, 'padre_documento_expedicion', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_nacionalidad' => filter_input(INPUT_POST, 'padre_nacionalidad', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_celular' => filter_input(INPUT_POST, 'padre_celular', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_telefono' => filter_input(INPUT_POST, 'padre_telefono', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_direccion' => filter_input(INPUT_POST, 'padre_direccion', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_profesion' => filter_input(INPUT_POST, 'padre_profesion', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':padre_email' => filter_input(INPUT_POST, 'padre_email', FILTER_SANITIZE_EMAIL) ?: null,
            ':madre_nombre' => filter_input(INPUT_POST, 'madre_nombre', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_tipo_documento' => filter_input(INPUT_POST, 'madre_tipo_documento', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_documento' => filter_input(INPUT_POST, 'madre_documento', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_documento_expedicion' => filter_input(INPUT_POST, 'madre_documento_expedicion', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_nacionalidad' => filter_input(INPUT_POST, 'madre_nacionalidad', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_celular' => filter_input(INPUT_POST, 'madre_celular', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_telefono' => filter_input(INPUT_POST, 'madre_telefono', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_direccion' => filter_input(INPUT_POST, 'madre_direccion', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_profesion' => filter_input(INPUT_POST, 'madre_profesion', FILTER_SANITIZE_SPECIAL_CHARS) ?: null,
            ':madre_email' => filter_input(INPUT_POST, 'madre_email', FILTER_SANITIZE_EMAIL) ?: null
        ]);

        $pass_hash = password_hash($identificacion, PASSWORD_DEFAULT);
        $stmt_usr = $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id, estudiante_id) VALUES (:u, :p, :e, :n, 5, :eid)");
        $stmt_usr->execute([
            ':u' => $identificacion,
            ':p' => $pass_hash,
            ':e' => $email,
            ':n' => $nombre . ' ' . $apellido,
            ':eid' => $nuevo_id
        ]);
        echo json_encode(['status' => 'success', 'message' => '¡Estudiante matriculado con éxito!']);
    } else {
        throw new Exception('Fallo crítico al insertar en la base de datos.');
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db)) $db = null;
}
?>