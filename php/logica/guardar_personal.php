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

    if (!tiene_permiso('personal')) {
        throw new Exception('Acceso denegado o privilegios insuficientes.');
    }

    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input) && !empty($_POST)) {
        $input = $_POST;
    }

    $nombre = trim(limpiar_texto_utf8((string)($input['nombre'] ?? '')));
    $user = strtoupper(trim(limpiar_texto_utf8((string)($input['user'] ?? ''))));
    $pass = (string)($input['pass'] ?? '');
    $rol = filter_var($input['rol'] ?? 0, FILTER_VALIDATE_INT);
    $esp = filter_var($input['esp'] ?? null, FILTER_VALIDATE_INT) ?: null;

    if ($rol === false || $rol === 0) {
        throw new Exception('Debe seleccionar un rol institucional válido.');
    }

    if ($rol !== 11) {
        $esp = null;
    }

    if (empty($pass)) {
        $pass = '1234';
    }

    if (empty($nombre) || empty($user)) {
        throw new Exception('Faltan datos obligatorios (Nombre y Usuario).');
    }

    $stmt_rol_check = $db->prepare("SELECT nombre_rol FROM roles WHERE id = :rol_id");
    $stmt_rol_check->bindValue(':rol_id', $rol, PDO::PARAM_INT);
    $stmt_rol_check->execute();
    $rol_nombre = strtolower((string)($stmt_rol_check->fetchColumn() ?: ''));

    if (strpos($rol_nombre, 'estudiante') !== false || strpos($rol_nombre, 'alumno') !== false) {
        throw new Exception('Operación Inválida: La matriculación de estudiantes debe realizarse desde el módulo de Matrícula.');
    }

    $stmt_check = $db->prepare('SELECT id FROM usuarios WHERE UPPER(usuario) = UPPER(:usr)');
    $stmt_check->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt_check->execute();
    if ($stmt_check->fetch()) {
        throw new Exception('El nombre de usuario "' . $user . '" ya se encuentra registrado en el sistema.');
    }

    $pass_segura = password_hash($pass, PASSWORD_BCRYPT);
    $email_inst = !empty($input['email']) ? trim((string)$input['email']) : (strtolower($user) . '@elite.edu.co');

    $db->beginTransaction();

    $stmt = $db->prepare('INSERT INTO usuarios (nombre, usuario, password, email, rol_id, especialidad_id, permisos_custom) VALUES (:nom, :usr, :pass, :email, :rol, :esp, 0)');
    $stmt->bindValue(':nom', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':usr', $user, PDO::PARAM_STR);
    $stmt->bindValue(':pass', $pass_segura, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email_inst, PDO::PARAM_STR);
    $stmt->bindValue(':rol', $rol, PDO::PARAM_INT);
    
    if ($esp === null) {
        $stmt->bindValue(':esp', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':esp', $esp, PDO::PARAM_INT);
    }
    $stmt->execute();
    $nuevo_usuario_id = (int)$db->lastInsertId();

    // Procesar foto si fue enviada
    $foto_ruta = null;
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nombre_foto = 'personal_' . $nuevo_usuario_id . '_' . time() . '.' . $ext;
            $dir_subida = __DIR__ . '/../../uploads/fotos/';
            if (!is_dir($dir_subida)) {
                mkdir($dir_subida, 0755, true);
            }
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir_subida . $nombre_foto)) {
                $foto_ruta = $nombre_foto;
            }
        }
    }

    // Datos Adicionales del Expediente
    $tipo_doc = trim((string)($input['tipo_documento'] ?? 'CC'));
    $doc = trim((string)($input['documento'] ?? ''));
    $doc_exp = trim((string)($input['documento_expedicion'] ?? ''));
    $fnac = !empty($input['fecha_nacimiento']) ? (string)$input['fecha_nacimiento'] : null;
    $edad = filter_var($input['edad'] ?? null, FILTER_VALIDATE_INT) ?: null;
    if (!empty($fnac)) {
        try {
            $fecha_nac = new DateTime($fnac);
            $hoy = new DateTime();
            if ($fecha_nac <= $hoy) {
                $edad = $hoy->diff($fecha_nac)->y;
            }
        } catch (Exception $e) {
            // mantener edad enviada
        }
    }
    $genero = in_array($input['genero'] ?? '', ['M', 'F', 'OTRO']) ? (string)$input['genero'] : 'M';
    $rh = trim((string)($input['rh'] ?? ''));

    $celular = trim((string)($input['celular'] ?? ''));
    $telefono = trim((string)($input['telefono_fijo'] ?? ''));
    $email_personal = trim((string)($input['email_personal'] ?? ''));
    $direccion = trim((string)($input['direccion'] ?? ''));
    $ciudad = trim((string)($input['ciudad_residencia'] ?? ''));
    $barrio = trim((string)($input['barrio'] ?? ''));

    $titulo = trim((string)($input['titulo_profesional'] ?? ''));
    $formacion = trim((string)($input['nivel_formacion'] ?? ''));
    $escalafon = trim((string)($input['escalafon_docente'] ?? ''));
    $fingreso = !empty($input['fecha_ingreso']) ? (string)$input['fecha_ingreso'] : date('Y-m-d');
    $contrato = trim((string)($input['tipo_contrato'] ?? 'PLANTA'));
    $estado_lab = trim((string)($input['estado_laboral'] ?? 'ACTIVO'));
    $jornada_lab = trim((string)($input['jornada_laboral'] ?? 'Completa')) ?: 'Completa';

    $eps = trim((string)($input['eps'] ?? ''));
    $pension = trim((string)($input['fondo_pensiones'] ?? ''));
    $arl = trim((string)($input['arl'] ?? ''));
    $sos_nom = trim((string)($input['contacto_emergencia_nombre'] ?? ''));
    $sos_tel = trim((string)($input['contacto_emergencia_telefono'] ?? ''));
    $sos_par = trim((string)($input['contacto_emergencia_parentesco'] ?? ''));

    $sql_adicional = "
        INSERT INTO personal_datos_adicionales (
            usuario_id, tipo_documento, documento, documento_expedicion, fecha_nacimiento, edad, genero, rh, foto,
            celular, telefono_fijo, email_personal, direccion, ciudad_residencia, barrio,
            titulo_profesional, nivel_formacion, escalafon_docente, fecha_ingreso, tipo_contrato, estado_laboral, jornada_laboral,
            eps, fondo_pensiones, arl, contacto_emergencia_nombre, contacto_emergencia_telefono, contacto_emergencia_parentesco
        ) VALUES (
            :uid, :tdoc, :doc, :doc_exp, :fnac, :edad, :gen, :rh, :foto,
            :cel, :tel, :email_p, :dir, :ciudad, :barrio,
            :titulo, :formacion, :escalafon, :fingreso, :contrato, :estado_lab, :jornada_lab,
            :eps, :pension, :arl, :sos_nom, :sos_tel, :sos_par
        )
    ";
    $stmt_ad = $db->prepare($sql_adicional);
    $stmt_ad->execute([
        ':uid' => $nuevo_usuario_id,
        ':tdoc' => $tipo_doc,
        ':doc' => $doc ?: null,
        ':doc_exp' => $doc_exp ?: null,
        ':fnac' => $fnac,
        ':edad' => $edad,
        ':gen' => $genero,
        ':rh' => $rh ?: null,
        ':foto' => $foto_ruta,
        ':cel' => $celular ?: null,
        ':tel' => $telefono ?: null,
        ':email_p' => $email_personal ?: null,
        ':dir' => $direccion ?: null,
        ':ciudad' => $ciudad ?: null,
        ':barrio' => $barrio ?: null,
        ':titulo' => $titulo ?: null,
        ':formacion' => $formacion ?: null,
        ':escalafon' => $escalafon ?: null,
        ':fingreso' => $fingreso,
        ':contrato' => $contrato,
        ':estado_lab' => $estado_lab,
        ':jornada_lab' => $jornada_lab,
        ':eps' => $eps ?: null,
        ':pension' => $pension ?: null,
        ':arl' => $arl ?: null,
        ':sos_nom' => $sos_nom ?: null,
        ':sos_tel' => $sos_tel ?: null,
        ':sos_par' => $sos_par ?: null,
    ]);

    $db->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => '¡Personal registrado y expediente laboral creado con éxito!',
        'id' => $nuevo_usuario_id
    ]);

} catch (PDOException $pe) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $msg = $pe->getMessage();
    if ($pe->getCode() === '23000' || strpos($msg, '1062') !== false || strpos($msg, 'Duplicate entry') !== false) {
        $msg = 'El nombre de usuario o documento ya se encuentra registrado. Por favor ingrese uno diferente.';
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $msg
    ]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode([
        'status' => 'error', 
        'message' => 'Error de Bóveda: ' . $e->getMessage()
    ]);
}
exit();