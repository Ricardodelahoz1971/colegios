<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/php/logica/check_mensajes.php';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer check_mensajes.php\n";
    exit(1);
}

// 1. Corregir execute([$user_id, $user_id]) a execute([$user_id]) en la consulta de notificaciones
$target1 = "    \$stmt_n = \$db->prepare(\$sql_notif);
    \$stmt_n->execute([\$user_id, \$user_id]);";

$replacement1 = "    \$stmt_n = \$db->prepare(\$sql_notif);
    \$stmt_n->execute([\$user_id]);";

$content = str_replace($target1, $replacement1, $content);

// 2. Corregir la consulta de usuarios online para MariaDB/MySQL (usando TIMESTAMPDIFF)
$target2 = "\$online_stmt_prepared = \$db->prepare(\"SELECT id FROM usuarios WHERE ultima_actividad IS NOT NULL AND (strftime('%s','now') - strftime('%s', ultima_actividad)) <= 60\");";
$replacement2 = "\$online_stmt_prepared = \$db->prepare(\"SELECT id FROM usuarios WHERE ultima_actividad IS NOT NULL AND TIMESTAMPDIFF(SECOND, ultima_actividad, NOW()) <= 60\");";

$content = str_replace($target2, $replacement2, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡check_mensajes.php corregido con éxito!\n";
} else {
    echo "Error: No se pudo guardar check_mensajes.php\n";
}
