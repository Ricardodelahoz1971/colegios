<?php
include_once __DIR__ . '/../php/db.php';

try {
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='eval_tipos'")->fetchAll();
    if (empty($tables)) {
        echo "❌ La tabla eval_tipos NO existe en la base de datos!\n";
    } else {
        echo "📋 COLUMNAS DE eval_tipos:\n";
        $cols = $db->query("PRAGMA table_info(eval_tipos)")->fetchAll(PDO::FETCH_ASSOC);
        foreach($cols as $c) echo "- {$c['name']} ({$c['type']})\n";

        $data = $db->query("SELECT * FROM eval_tipos")->fetchAll(PDO::FETCH_ASSOC);
        echo "\n📊 REGISTROS EN eval_tipos (Total: " . count($data) . "):\n";
        print_r($data);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
