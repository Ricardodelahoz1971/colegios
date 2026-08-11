<?php
require_once 'C:\\xampp\\htdocs\\sistema_escolar\\php\\db.php';

// Crear el nuevo hash para '1234'
$new_hash = password_hash('1234', PASSWORD_DEFAULT);

// Actualizar la contraseña del usuario ID = 1 (admin)
$update = $db->prepare("UPDATE usuarios SET password = ? WHERE id = 1");
$update->execute([$new_hash]);

echo "Contraseña del Admin restablecida a '1234' (Usuario: admin) con éxito.\n";
