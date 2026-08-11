<?php
declare(strict_types=1);
// SCRATCH/VERIFICAR_ESTUDIANTE_ID.PHP - CAPA FORENSE DE ROLES v1.0
require_once __DIR__ . '/../php/db.php';

try {
    // Buscar los últimos 5 usuarios creados con su rol respectivo
    $stmt = $db->prepare("
        SELECT u.id, u.usuario, u.nombre, u.rol_id, r.nombre_rol 
        FROM usuarios u
        JOIN roles r ON u.rol_id = r.id
        ORDER BY u.id DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "===================================================================================\n";
    echo "🏛️ AUDITORÍA DE IDENTIDADES: ÚLTIMOS USUARIOS MATRICULADOS EN EL SISTEMA\n";
    echo "===================================================================================\n";
    foreach ($usuarios as $u) {
        echo "ID Usuario: {$u['id']} | Login: {$u['usuario']} | Nombre: {$u['nombre']} | Rol ID: {$u['rol_id']} ({$u['nombre_rol']})\n";
    }
    echo "===================================================================================\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
