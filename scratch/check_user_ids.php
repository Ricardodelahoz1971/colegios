<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $rows = $db->query("SELECT id, usuario, nombre, rol_id FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo "ID: {$r['id']} | Username: {$r['usuario']} | Name: {$r['nombre']} | RolID: {$r['rol_id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
