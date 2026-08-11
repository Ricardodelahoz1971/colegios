<?php
declare(strict_types=1);
// PHP/PROCESAR_MATRICULA.PHP - MOTOR FUNCIONAL ELITE
header('Content-Type: application/json');
session_start();
require_once 'db.php';
require_once 'auth.php';

try {
    proteccion_extrema();

    if (!tiene_permiso('matricula')) {
        throw new Exception('Acceso denegado o permisos insuficientes.');
    }
    // Liberar sesión para permitir concurrencia
    session_write_close();

    $identificacion = $_POST['identificacion'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $apellido = $_POST['apellido'] ?? '';
    $curso_id = $_POST['curso_id'] ?? 0;
    
    // Validar datos mínimos
    if (empty($identificacion) || empty($nombre) || empty($apellido)) {
        throw new Exception('Faltan datos obligatorios para matricular.');
    }

    // Validar por duplicados (v9.2 PDO)
    $check = $db->prepare("SELECT id FROM estudiantes WHERE identificacion = :id");
    $check->bindValue(':id', $identificacion, PDO::PARAM_STR);
    $check->execute();
    if ($check->fetch()) {
        throw new Exception('Este estudiante ya se encuentra registrado con la identificación: ' . $identificacion);
    }
    
    // Obtener nombre del curso para compatibilidad
    $c_stmt = $db->prepare("SELECT nombre_curso FROM cursos WHERE id = :cid");
    $c_stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $c_stmt->execute();
    $c_data = $c_stmt->fetch(PDO::FETCH_ASSOC);
    $nombre_curso = $c_data['nombre_curso'] ?? 'Sin Asignar';

    // Insertar
    $sql = "INSERT INTO estudiantes (identificacion, nombre, apellido, curso_id, curso, tipo_documento, rh, genero, email, celular, es_antiguo) 
            VALUES (:id, :nom, :ape, :cid, :cur, :tdoc, :rh, :gen, :mail, :cel, :ant)";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $identificacion, PDO::PARAM_STR);
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':ape', $apellido, PDO::PARAM_STR);
    $stmt->bindValue(':cid', $curso_id, PDO::PARAM_INT);
    $stmt->bindValue(':cur', $nombre_curso, PDO::PARAM_STR);
    $stmt->bindValue(':tdoc', $_POST['tipo_documento'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':rh', $_POST['tipo_sangre'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':gen', $_POST['genero'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':mail', $_POST['email'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':cel', $_POST['celular'] ?? '', PDO::PARAM_STR);
    $stmt->bindValue(':ant', isset($_POST['es_antiguo']) ? 1 : 0, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $nuevo_id = $db->lastInsertId();
        
        // Insertar datos adicionales y acudientes en la tabla relacionada
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
        
        $fecha_nac = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : null;
        $edad_val = !empty($_POST['edad']) ? (int)$_POST['edad'] : null;
        
        $stmt_adicional->execute([
            ':estudiante_id' => $nuevo_id,
            ':lugar_nacimiento' => !empty($_POST['lugar_nacimiento']) ? trim($_POST['lugar_nacimiento']) : null,
            ':fecha_nacimiento' => $fecha_nac,
            ':edad' => $edad_val,
            ':nacionalidad' => !empty($_POST['nacionalidad']) ? trim($_POST['nacionalidad']) : null,
            ':colegio_anterior' => !empty($_POST['colegio_anterior']) ? trim($_POST['colegio_anterior']) : null,
            ':direccion_estudiante' => !empty($_POST['direccion_estudiante']) ? trim($_POST['direccion_estudiante']) : null,
            ':folio_matricula' => !empty($_POST['folio_matricula']) ? trim($_POST['folio_matricula']) : null,
            ':padre_nombre' => !empty($_POST['padre_nombre']) ? trim($_POST['padre_nombre']) : null,
            ':padre_tipo_documento' => !empty($_POST['padre_tipo_documento']) ? trim($_POST['padre_tipo_documento']) : null,
            ':padre_documento' => !empty($_POST['padre_documento']) ? trim($_POST['padre_documento']) : null,
            ':padre_documento_expedicion' => !empty($_POST['padre_documento_expedicion']) ? trim($_POST['padre_documento_expedicion']) : null,
            ':padre_nacionalidad' => !empty($_POST['padre_nacionalidad']) ? trim($_POST['padre_nacionalidad']) : null,
            ':padre_celular' => !empty($_POST['padre_celular']) ? trim($_POST['padre_celular']) : null,
            ':padre_telefono' => !empty($_POST['padre_telefono']) ? trim($_POST['padre_telefono']) : null,
            ':padre_direccion' => !empty($_POST['padre_direccion']) ? trim($_POST['padre_direccion']) : null,
            ':padre_profesion' => !empty($_POST['padre_profesion']) ? trim($_POST['padre_profesion']) : null,
            ':padre_email' => !empty($_POST['padre_email']) ? trim($_POST['padre_email']) : null,
            ':madre_nombre' => !empty($_POST['madre_nombre']) ? trim($_POST['madre_nombre']) : null,
            ':madre_tipo_documento' => !empty($_POST['madre_tipo_documento']) ? trim($_POST['madre_tipo_documento']) : null,
            ':madre_documento' => !empty($_POST['madre_documento']) ? trim($_POST['madre_documento']) : null,
            ':madre_documento_expedicion' => !empty($_POST['madre_documento_expedicion']) ? trim($_POST['madre_documento_expedicion']) : null,
            ':madre_nacionalidad' => !empty($_POST['madre_nacionalidad']) ? trim($_POST['madre_nacionalidad']) : null,
            ':madre_celular' => !empty($_POST['madre_celular']) ? trim($_POST['madre_celular']) : null,
            ':madre_telefono' => !empty($_POST['madre_telefono']) ? trim($_POST['madre_telefono']) : null,
            ':madre_direccion' => !empty($_POST['madre_direccion']) ? trim($_POST['madre_direccion']) : null,
            ':madre_profesion' => !empty($_POST['madre_profesion']) ? trim($_POST['madre_profesion']) : null,
            ':madre_email' => !empty($_POST['madre_email']) ? trim($_POST['madre_email']) : null
        ]);

        $pass_hash = password_hash($identificacion, PASSWORD_DEFAULT);
        $stmt_usr = $db->prepare("INSERT INTO usuarios (usuario, password, email, nombre, rol_id, estudiante_id) VALUES (:u, :p, :e, :n, 5, :eid)");
        $stmt_usr->execute([
            ':u' => $identificacion,
            ':p' => $pass_hash,
            ':e' => $_POST['email'] ?? '',
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
    if (isset($db)) $db = null; // En PDO se cierra asignando null
}
?>

