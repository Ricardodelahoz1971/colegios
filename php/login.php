<?php
declare(strict_types=1);
/**
 * PHP/LOGIN.PHP - PROCESAMIENTO DE ACCESO v9.3
 * Este archivo actúa como el punto de entrada lógico para las peticiones de acceso,
 * delegando la validación y gestión de sesiones al AuthController.
 */

require_once 'db.php';
require_once 'security.php';
require_once 'logica/AuthController.php';

// Blindaje contra ataques CSRF y Method Hijacking
proteccion_extrema();

// Captura de datos del formulario institucional
$usuario = filter_input(INPUT_POST, 'usuario', FILTER_DEFAULT) ?? '';
$password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT) ?? '';

// Ejecutar protocolo de autenticación Senior
$auth = AuthController::login($db, $usuario, $password);

// Gestión de redireccionamiento basada en el resultado del controlador
if ($auth['status'] === 'success') {
    header("Location: " . $auth['redirect']);
} else {
    // Los códigos de error (usuario/clave/db_fail) se propagan de vuelta a la vista
    header("Location: ../index.php?error=" . $auth['code']);
}
exit();
?>