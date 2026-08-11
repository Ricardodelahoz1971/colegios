<?php
require 'php/db.php';
try {
    $count = $db->query("SELECT COUNT(*) FROM eval_preguntas")->fetchColumn();
    $rows = $db->query("SELECT p.id, p.titulo, p.docente_id, a.num_dba 
                        FROM eval_preguntas p 
                        LEFT JOIN ares_catalogo_aprendizajes a ON p.aprendizaje_id = a.id")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "TOTAL_REACTIVOS: $count\n";
    foreach($rows as $r) {
        echo "ID: {$r['id']} | Titulo: {$r['titulo']} | Docente: {$r['docente_id']} | DBA: " . ($r['num_dba'] ?? 'NULL') . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
