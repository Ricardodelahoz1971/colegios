<?php
declare(strict_types=1);
require_once '../db.php';

// Simulamos los IDs de la primera entrega que exista
$stmt_stmt = $db->prepare("SELECT id, asignacion_id, estudiante_id, respuestas_json FROM eval_respuestas LIMIT 1"); $stmt_stmt->execute(); $stmt = $stmt_stmt;
$entrega = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$entrega) {
    echo "NO HAY ENTREGAS.";
    exit;
}

echo "=== ENTREGA DATA ===\n";
print_r($entrega);
echo "\n=== JSON_DECODE ===\n";
var_dump(json_decode($entrega['respuestas_json'], true));
echo "\n=== JSON ERROR ===\n";
echo json_last_error_msg();

// Estructura de la prueba
$stmt_asig = $db->prepare("SELECT prueba_id FROM eval_asignaciones WHERE id = ?");
$stmt_asig->execute([$entrega['asignacion_id']]);
$prueba_id = $stmt_asig->fetchColumn();

echo "\n=== PRUEBA_ID ===\n" . $prueba_id;

$stmt_p = $db->prepare("SELECT p.id, p.enunciado, p.tipo_id, p.metadata_json, i.peso 
                        FROM eval_pruebas_items i 
                        JOIN eval_preguntas p ON i.pregunta_id = p.id 
                        WHERE i.prueba_id = ? ORDER BY i.orden ASC");
$stmt_p->execute([$prueba_id]);
$estructura = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== ESTRUCTURA ===\n";
print_r($estructura);


