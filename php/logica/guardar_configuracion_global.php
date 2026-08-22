<?php
declare(strict_types=1);
ob_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../db.php';
require_once '../auth.php';
require_once '../security.php';
guardia_sesion();
session_write_close();

function registrar_evento_global(string $categoria, string $mensaje) {
    $fecha = date('Y-m-d H:i:s');
    $log = "[{$fecha}] [GLOBAL_CONFIG:{$categoria}] {$mensaje}" . PHP_EOL;
    @file_put_contents(__DIR__ . '/../logs/elite_trace.log', $log, FILE_APPEND);
}

try {
    $input = json_encode($_POST);
    registrar_evento_global('INPUT', "Petición de guardado recibida: {$input}");

    proteccion_extrema();

    if (!tiene_permiso('configuracion')) {
        registrar_evento_global('SECURITY_VIOLATION', "Intento de escritura sin permisos por usuario ID: " . ($_SESSION['usuario_id'] ?? 0));
        throw new Exception("Acceso denegado: Privilegios insuficientes.");
    }

    $mi_rol_nombre = strtolower($_SESSION['rol_nombre'] ?? '');
    if ($mi_rol_nombre !== 'administrador' && $mi_rol_nombre !== 'coordinador') {
        registrar_evento_global('ROLE_VIOLATION', "Intento de escritura por rol denegado: {$mi_rol_nombre}");
        throw new Exception("Acceso denegado: Su rol no posee autoría para esta acción.");
    }

    $clave = filter_input(INPUT_POST, 'clave', FILTER_SANITIZE_SPECIAL_CHARS);
    $valor = filter_input(INPUT_POST, 'valor', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($clave === null || $clave === false || trim($clave) === '') {
        throw new Exception("Parámetro inválido: Nombre de clave ausente.");
    }
    $clave = trim($clave);

    if ($valor === null || $valor === false) {
        $valor = '';
    }
    $valor = trim($valor);

    $lista_blanca = [
        'privacidad_catedratico_sabana',
        'anio_lectivo_oficial',
        'politica_recuperacion',
        'dias_gracia_recuperaciones'
    ];

    if (!in_array($clave, $lista_blanca)) {
        registrar_evento_global('FORGERY_DETECTED', "Intento de inyectar clave no autorizada: {$clave}");
        throw new Exception("Acceso denegado: Parámetro fuera de lista blanca.");
    }

    if ($clave === 'privacidad_catedratico_sabana') {
        if ($valor !== 'estricto' && $valor !== 'abierto') {
            throw new Exception("Valor no válido para la privacidad transversal.");
        }
    }

    if ($clave === 'anio_lectivo_oficial') {
        if ($valor !== '0' && $valor !== '1') {
            throw new Exception("Valor no válido para el estado de sellado del año lectivo.");
        }
    }

    if ($clave === 'politica_recuperacion') {
        if ($valor !== 'reemplazo' && $valor !== 'promedio' && $valor !== 'tope_aprobacion') {
            throw new Exception("Valor no válido para la política de recuperaciones.");
        }
    }

    if ($clave === 'dias_gracia_recuperaciones') {
        $val_int = filter_var($valor, FILTER_VALIDATE_INT);
        if ($val_int === false || $val_int < 0 || $val_int > 30) {
            throw new Exception("El valor para los días de holgura debe ser un número entero entre 0 y 30.");
        }
        $valor = (string)$val_int;
    }

    $stmt = $db->prepare("UPDATE configuracion_global SET valor = ?, updated_at = CURRENT_TIMESTAMP WHERE clave = ?");
    $stmt->execute([$valor, $clave]);

    registrar_evento_global('SUCCESS', "Configuración '{$clave}' actualizada a '{$valor}' exitosamente.");

    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'success',
        'message' => 'Configuración sincronizada exitosamente en el núcleo del oráculo.'
    ]);

} catch (Throwable $e) {
    registrar_evento_global('FATAL_ERROR', $e->getMessage());
    if (ob_get_length()) ob_clean();
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
exit();
?>