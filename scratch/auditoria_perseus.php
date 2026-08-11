<?php
require_once __DIR__ . '/../php/db.php';

echo "--- DIAGNÓSTICO DE INTEGRIDAD PERSEUS ---\n";

// 1. Verificar existencia y SQL de la vista
$stmt = $db->prepare("SELECT sql FROM sqlite_master WHERE name = 'view_perseus_cobertura'");
$stmt->execute();
$view = $stmt->fetch();

if ($view) {
    echo "✅ Vista 'view_perseus_cobertura' DETECTADA.\n";
    echo "SQL:\n" . $view['sql'] . "\n";
} else {
    echo "❌ ERROR: La vista 'view_perseus_cobertura' NO EXISTE en la base de datos.\n";
}

// 2. Verificar tablas de soporte
$tablas = ['ares_catalogo_aprendizajes', 'eval_preguntas', 'eval_pruebas_items', 'eval_asignaciones'];
foreach ($tablas as $t) {
    $c = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$t'")->fetchColumn();
    echo ($c > 0 ? "✅ Tabla '$t' lista.\n" : "❌ Tabla '$t' FALTANTE.\n");
}
