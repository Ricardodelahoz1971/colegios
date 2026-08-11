<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

echo "=== TABLAS EN LA BASE DE DATOS ===\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) {
    echo "- $t\n";
}

$messaging_tables = ['mensajes', 'vistas_canales', 'grupos', 'miembros_grupo'];
foreach ($messaging_tables as $table) {
    if (in_array($table, $tables)) {
        echo "\n=== ESTRUCTURA DE LA TABLA: $table ===\n";
        $columns = $db->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $c) {
            echo "Columna: {$c['Field']} | Tipo: {$c['Type']} | Null: {$c['Null']} | Key: {$c['Key']} | Default: {$c['Default']} | Extra: {$c['Extra']}\n";
        }
    } else {
        echo "\n⚠️ La tabla '$table' no existe en la base de datos.\n";
    }
}
