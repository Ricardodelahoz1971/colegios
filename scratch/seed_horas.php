<?php
require_once __DIR__ . '/../php/db.php';
$stmt = $db->prepare("SELECT COUNT(*) FROM configuracion_global WHERE clave = 'limite_horas_docente'");
$stmt->execute();
if ($stmt->fetchColumn() == 0) {
    $db->prepare("
        INSERT INTO configuracion_global (clave, valor, tipo_dato, categoria, nombre_legible, descripcion, editable_por)
        VALUES ('limite_horas_docente', '24', 'integer', 'academico', 'Límite de Horas Semanales Docente', 'Establece el tope de horas semanales permitidas para un docente antes de emitir alerta de sobrecarga laboral.', 'coordinador')
    ")->execute();
    echo "Seeded successfully!";
} else {
    echo "Already seeded.";
}
