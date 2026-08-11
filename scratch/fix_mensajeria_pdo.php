<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/php/vistas/mensajeria.php';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer mensajeria.php\n";
    exit(1);
}

// 1. Reemplazar stmt_es_mi_profe consulta
$target1 = "\$stmt_es_mi_profe = \$db->prepare(\"SELECT COUNT(*) FROM usuarios u 
                                  LEFT JOIN carga_academica ON u.id = carga_academica.docente_id 
                                  LEFT JOIN cursos c ON u.id = c.tutor_id
                                  WHERE u.id = :uid AND (carga_academica.curso_id = :cid OR c.id = :cid)\");";

$replacement1 = "\$stmt_es_mi_profe = \$db->prepare(\"SELECT COUNT(*) FROM usuarios u 
                                  LEFT JOIN carga_academica ON u.id = carga_academica.docente_id 
                                  LEFT JOIN cursos c ON u.id = c.tutor_id
                                  WHERE u.id = :uid AND (carga_academica.curso_id = :cid1 OR c.id = :cid2)\");";

$content = str_replace($target1, $replacement1, $content);

// 2. Reemplazar stmt_es_mi_profe binding
$target2 = "            \$stmt_es_mi_profe->bindValue(':uid', \$u['id'], PDO::PARAM_INT);
            \$stmt_es_mi_profe->bindValue(':cid', \$mi_curso_id, PDO::PARAM_INT);
            \$stmt_es_mi_profe->execute();";

$replacement2 = "            \$stmt_es_mi_profe->bindValue(':uid', \$u['id'], PDO::PARAM_INT);
            \$stmt_es_mi_profe->bindValue(':cid1', \$mi_curso_id, PDO::PARAM_INT);
            \$stmt_es_mi_profe->bindValue(':cid2', \$mi_curso_id, PDO::PARAM_INT);
            \$stmt_es_mi_profe->execute();";

$content = str_replace($target2, $replacement2, $content);

// 3. Reemplazar stmt_is_mine consulta y binding
$target3 = "                \$stmt_is_mine = \$db->prepare(\"SELECT COUNT(*) FROM estudiantes e 
                                              JOIN cursos c ON e.curso_id = c.id 
                                              WHERE e.id = :eid 
                                              AND (c.tutor_id = :tid OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :tid))\");
                \$stmt_is_mine->bindValue(':eid', \$u['estudiante_id'], PDO::PARAM_INT);
                \$stmt_is_mine->bindValue(':tid', \$user_id, PDO::PARAM_INT);
                \$stmt_is_mine->execute();";

$replacement3 = "                \$stmt_is_mine = \$db->prepare(\"SELECT COUNT(*) FROM estudiantes e 
                                              JOIN cursos c ON e.curso_id = c.id 
                                              WHERE e.id = :eid 
                                              AND (c.tutor_id = :tid1 OR c.id IN (SELECT curso_id FROM carga_academica WHERE docente_id = :tid2))\");
                \$stmt_is_mine->bindValue(':eid', \$u['estudiante_id'], PDO::PARAM_INT);
                \$stmt_is_mine->bindValue(':tid1', \$user_id, PDO::PARAM_INT);
                \$stmt_is_mine->bindValue(':tid2', \$user_id, PDO::PARAM_INT);
                \$stmt_is_mine->execute();";

$content = str_replace($target3, $replacement3, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Parámetros duplicados corregidos con éxito en mensajeria.php!\n";
} else {
    echo "Error: No se pudo guardar mensajeria.php\n";
}
