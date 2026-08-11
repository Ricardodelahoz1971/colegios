<?php
require_once 'c:/xampp/htdocs/sistema_escolar/php/db.php';
$_POST['usuario_id'] = 99; // Pedro Perez
$_POST['permisos'] = json_encode([1, 2, 3]);

// Ignoramos CSRF manual simulando el código interior
$usuario_id = 99;
$permisos = [1, 2, 3];

$db->beginTransaction();
$stmt_del = $db->prepare("DELETE FROM usuario_permisos WHERE usuario_id = :usr");
$stmt_del->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
$stmt_del->execute();

if (!empty($permisos)) {
    foreach ($permisos as $p_id) {
        $stmt_ins = $db->prepare("INSERT INTO usuario_permisos (usuario_id, permiso_id) VALUES (:usr, :p)");
        $stmt_ins->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
        $stmt_ins->bindValue(':p', (int)$p_id, PDO::PARAM_INT);
        $stmt_ins->execute();
    }
}
$stmt_custom = $db->prepare("UPDATE usuarios SET permisos_custom = 1 WHERE id = :usr");
$stmt_custom->bindValue(':usr', $usuario_id, PDO::PARAM_INT);
$stmt_custom->execute();
$db->commit();

$s=$db->query('SELECT * FROM usuario_permisos WHERE usuario_id = 99');
print_r($s->fetchAll(PDO::FETCH_ASSOC));
?>
