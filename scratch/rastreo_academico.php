<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/../php/database/usuarios.db';
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['areas', 'especialidades', 'carga_academica', 'cursos'];
    foreach ($tables as $table) {
        echo "\n--- Schema: $table ---\n";
        $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  [{$col['name']}] {$col['type']}\n";
        }
        echo "--- Sample data ---\n";
        $sample = $pdo->query("SELECT * FROM $table LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
        print_r($sample);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
