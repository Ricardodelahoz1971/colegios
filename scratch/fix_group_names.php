<?php
require_once 'php/db.php';
try {
    $stmt_cursos = $db->query("SELECT id, nombre_curso FROM cursos");
    $cursos = $stmt_cursos->fetchAll(PDO::FETCH_ASSOC);

    $stmt_update = $db->prepare("UPDATE grupos SET nombre = ? WHERE tipo = 'CURSO' AND referencia_id = ?");

    foreach ($cursos as $c) {
        $esperado = "Salón " . $c['nombre_curso'];
        $stmt_update->execute([$esperado, $c['id']]);
        echo "Curso ID {$c['id']} ({$c['nombre_curso']}) -> Grupo actualizado a '{$esperado}'\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
