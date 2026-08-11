<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

$stmt = $db->query("
    SELECT id, usuario, password, nombre, rol_id 
    FROM usuarios 
    WHERE usuario IN ('LEV', 'LRP', 'LEL', 'LFV', 'ADMIN')
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "========================================================\n";
echo "📊 HASHES COMPARATIVOS DE USUARIOS CLAVE\n";
echo "========================================================\n";
foreach ($users as $u) {
    echo "Usuario: " . $u['usuario'] . " | Hash: " . $u['password'] . "\n";
}
echo "========================================================\n";
