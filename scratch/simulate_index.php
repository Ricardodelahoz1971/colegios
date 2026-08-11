<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/security.php';

// Simular exactamente lo que hace index.php
echo "=== TEST INDEX.PHP SIMULATION ===\n\n";

// Test 1: ajustes_estetica query
try {
    $stmt = $db->prepare("SELECT clave, valor FROM ajustes_estetica");
    $stmt->execute();
    $cfg = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    echo "✅ ajustes_estetica: OK (" . count($cfg) . " registros)\n";
    echo "   school_name = " . ($cfg['school_name'] ?? 'N/A') . "\n";
    echo "   brand_color = " . ($cfg['brand_color'] ?? 'N/A') . "\n";
} catch (Exception $e) {
    echo "❌ ajustes_estetica ERROR: " . $e->getMessage() . "\n";
}

// Test 2: generar_csrf_token
try {
    $token = generar_csrf_token();
    echo "\n✅ CSRF Token: OK (len=" . strlen($token) . ")\n";
} catch (Exception $e) {
    echo "\n❌ CSRF Token ERROR: " . $e->getMessage() . "\n";
}

// Test 3: usuarios query (simular login)
try {
    $stmt = $db->prepare('
        SELECT u.*, r.nombre_rol 
        FROM usuarios u 
        JOIN roles r ON u.rol_id = r.id 
        WHERE UPPER(u.usuario) = UPPER(?)
    ');
    $stmt->execute(['admin']);
    $user = $stmt->fetch();
    if ($user) {
        echo "\n✅ Admin usuario: ENCONTRADO\n";
        echo "   ID: {$user['id']}, Rol: {$user['nombre_rol']}\n";
        $pwOk = password_verify('123456', $user['password']);
        echo "   Password 123456: " . ($pwOk ? "✅ CORRECTA" : "❌ INCORRECTA") . "\n";
    } else {
        echo "\n❌ Admin usuario: NO ENCONTRADO\n";
    }
} catch (Exception $e) {
    echo "\n❌ Login query ERROR: " . $e->getMessage() . "\n";
}

// Test 4: Roles table
try {
    $roles = $db->query("SELECT id, nombre_rol FROM roles")->fetchAll();
    echo "\n✅ Roles en BD:\n";
    foreach ($roles as $r) echo "   ID {$r['id']}: {$r['nombre_rol']}\n";
} catch (Exception $e) {
    echo "\n❌ Roles ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DEL TEST ===\n";
