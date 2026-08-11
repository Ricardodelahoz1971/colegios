<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/../php/database/usuarios.db';
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tables = ['eval_preguntas', 'ares_catalogo_evidencias', 'ares_catalogo_aprendizajes'];
    foreach ($tables as $table) {
        echo "\n--- Schema: $table ---\n";
        $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  [{$col['name']}] {$col['type']}\n";
        }
    }

    echo "\n--- Muestra de una pregunta (eval_preguntas) ---\n";
    $sample = $pdo->query("SELECT * FROM eval_preguntas LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    print_r($sample);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
