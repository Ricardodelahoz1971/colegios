<?php
declare(strict_types=1);
require_once '../db.php';
$tables = ['eval_tipos', 'eval_preguntas', 'eval_pruebas', 'eval_pruebas_items', 'eval_respuestas', 'eval_incidentes'];
echo "DIAGNÓSTICO ARES\n";
foreach ($tables as $t) {
    try {
        $stmt_check = $db->prepare("SHOW TABLES LIKE :tn");
        $stmt_check->execute([':tn' => $t]);
        $exists = $stmt_check->fetchColumn();
        if ($exists) {
            echo "[OK] $t existe.\n";
        } else {
            throw new Exception("Tabla no encontrada");
        }
    } catch (Exception $e) {
        echo "[ERROR] $t NO EXISTE o falló: " . $e->getMessage() . "\n";
    }
}
$stmt_tipos = $db->prepare("SELECT COUNT(*) FROM eval_tipos"); $stmt_tipos->execute(); $tipos = $stmt_tipos->fetchColumn();
echo "Tipos de pregunta encontrados: $tipos\n";

