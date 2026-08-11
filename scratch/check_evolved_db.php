<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

function check_column($db, $table, $column) {
    $stmt = $db->prepare("PRAGMA table_info($table)");
    $stmt->execute();
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        if ($c['name'] === $column) {
            echo "✅ Table '$table' HAS column '$column' (Type: {$c['type']})\n";
            return;
        }
    }
    echo "❌ Table '$table' MISSING column '$column'\n";
}

echo "=== VERIFYING EVOLVED SCHEMAS ===\n";
check_column($db, 'ares_clases_nota', 'min_evaluaciones');
check_column($db, 'ares_calificaciones_desglose', 'nota_recuperacion');
check_column($db, 'eval_respuestas', 'calificacion_recuperacion');

$stmt_cfg = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'politica_recuperacion'");
$stmt_cfg->execute();
$val = $stmt_cfg->fetchColumn();
if ($val !== false) {
    echo "✅ Seeded 'politica_recuperacion' with value: '$val'\n";
} else {
    echo "❌ Missing 'politica_recuperacion' in configuracion_global\n";
}
