<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $tables = ['ares_catalogo_aprendizajes', 'ares_catalogo_evidencias', 'especialidades', 'cursos', 'carga_academica', 'usuarios'];
    foreach ($tables as $t) {
        $stmt = $db->query("SELECT COUNT(*) FROM $t");
        echo "Table: $t | Count: " . $stmt->fetchColumn() . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
