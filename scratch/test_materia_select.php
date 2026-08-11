<?php
include_once __DIR__ . '/../php/db.php';
$stmt = $db->query("SELECT id, nombre_especialidad, area_id, disciplina_men FROM especialidades");
$materias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "🧪 VERIFICACIÓN DE OPCIONES RENDERIZADAS (HTML DINÁMICO):\n";
foreach ($materias as $m) {
    echo "Option Rendered:\n";
    echo "  <option value=\"{$m['id']}\" data-area-id=\"{$m['area_id']}\" data-disciplina=\"{$m['disciplina_men']}\">{$m['nombre_especialidad']}</option>\n";
}
