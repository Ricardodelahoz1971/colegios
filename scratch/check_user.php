<?php
require_once 'php/db.php';

echo "--- DATOS DEL USUARIO LFV ---\n";
$stmt = $db->prepare("SELECT id, nombre, usuario, rol_id FROM usuarios WHERE usuario = 'LFV'");
$stmt->execute();
$lfv = $stmt->fetch();
print_r($lfv);

if ($lfv) {
    $lfv_id = (int)$lfv['id'];
    echo "\n--- ES TUTOR DE CURSOS? ---\n";
    $stmt2 = $db->prepare("SELECT id, nombre_curso, tutor_id FROM cursos WHERE tutor_id = ?");
    $stmt2->execute([$lfv_id]);
    print_r($stmt2->fetchAll());

    echo "\n--- TIENE CARGA ACADÉMICA? ---\n";
    $stmt3 = $db->prepare("SELECT ca.id, ca.curso_id, c.nombre_curso, ca.especialidad_id, ca.docente_id 
                           FROM carga_academica ca
                           JOIN cursos c ON ca.curso_id = c.id
                           WHERE ca.docente_id = ?");
    $stmt3->execute([$lfv_id]);
    print_r($stmt3->fetchAll());
}

echo "\n--- PERMISOS DEL ROL DEL USUARIO (ROL ID: " . ($lfv['rol_id'] ?? 'N/A') . ") ---\n";
if ($lfv) {
    $rol_id = (int)$lfv['rol_id'];
    $stmt_p = $db->prepare("SELECT p.nombre_permiso FROM permisos p 
                            JOIN roles_permisos rp ON p.id = rp.permiso_id 
                            WHERE rp.rol_id = ?");
    $stmt_p->execute([$rol_id]);
    print_r($stmt_p->fetchAll(PDO::FETCH_COLUMN));
}
?>
