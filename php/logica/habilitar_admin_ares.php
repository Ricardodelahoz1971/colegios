<?php
declare(strict_types=1);
require_once '../db.php';
$sql_permisos = "SELECT p.nombre FROM permisos p JOIN rol_permisos rp ON p.id = rp.permiso_id WHERE rp.rol_id = 1";
$stmt_permisos = $db->prepare($sql_permisos); 
$stmt_permisos->execute(); 
$permisos = $stmt_permisos->fetchAll(PDO::FETCH_COLUMN);
echo "=== PERMISOS ADMIN ===\n";

if (!in_array('evaluacion', $permisos)) {
    echo "\nADMIN NO TIENE PERMISO 'evaluacion'. Habilitando...\n";
    $stmt_p_ares = $db->prepare("SELECT id FROM permisos WHERE nombre = 'evaluacion'");
    $stmt_p_ares->execute();
    $p_id = $stmt_p_ares->fetchColumn();
    if ($p_id) {
        $db->prepare("INSERT OR IGNORE INTO rol_permisos (rol_id, permiso_id) VALUES (1, ?)")->execute([$p_id]);
        echo "Permiso 'evaluacion' otorgado al Admin.";
    } else {
        echo "Error: Permiso 'evaluacion' no existe en la tabla permisos.";
    }
} else {
    echo "\nAdmin ya tiene permiso 'evaluacion'.";
}

