<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/php/vistas/mensajeria.php';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer mensajeria.php\n";
    exit(1);
}

// Reemplazar la consulta y las vinculaciones de stmt_c para el docente
$target = "    \$stmt_c = \$db->prepare(\"SELECT id, nombre, referencia_id FROM grupos 
              WHERE id IN (SELECT g.id FROM grupos g JOIN cursos c ON g.referencia_id = c.id WHERE g.tipo = 'CURSO' AND (c.tutor_id = :uid OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :uid)))\");
    \$stmt_c->bindValue(':uid', \$user_id, PDO::PARAM_INT);";

$replacement = "    \$stmt_c = \$db->prepare(\"SELECT id, nombre, referencia_id FROM grupos 
              WHERE id IN (SELECT g.id FROM grupos g JOIN cursos c ON g.referencia_id = c.id WHERE g.tipo = 'CURSO' AND (c.tutor_id = :uid1 OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :uid2)))\");
    \$stmt_c->bindValue(':uid1', \$user_id, PDO::PARAM_INT);
    \$stmt_c->bindValue(':uid2', \$user_id, PDO::PARAM_INT);";

$content = str_replace($target, $replacement, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Parámetros duplicados corregidos en stmt_c para el docente!\n";
} else {
    echo "Error: No se pudo guardar mensajeria.php\n";
}
