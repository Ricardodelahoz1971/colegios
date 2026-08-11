<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

$stmt = $db->query("SELECT id, nombre, contenido_html FROM formatos_matricula ORDER BY id DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    echo "ID: " . $row['id'] . "\n";
    echo "Nombre: " . $row['nombre'] . "\n";
    echo "Contiene 'salto_pagina': " . (strpos($row['contenido_html'], 'salto_pagina') !== false ? 'SÍ' : 'NO') . "\n";
    echo "Contenido HTML:\n";
    echo htmlspecialchars(substr($row['contenido_html'], 0, 1000)) . "...\n";
} else {
    echo "No hay formatos guardados.\n";
}
