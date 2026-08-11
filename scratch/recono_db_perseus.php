<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/../php/database/usuarios.db';
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- TABLAS ENCONTRADAS ---\n";
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }

    echo "\n--- ESQUEMA DE TABLAS CLAVE ---\n";
    $keyTables = ['eval_pruebas', 'eval_pruebas_items', 'men_dba', 'eval_unidades_tematicas', 'config_materias'];
    foreach ($keyTables as $table) {
        if (in_array($table, $tables)) {
            echo "\nTable: $table\n";
            $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cols as $col) {
                echo "  [{$col['name']}] {$col['type']}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
