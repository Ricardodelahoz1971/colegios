<?php
require_once 'php/db.php';
try {
    $check = $db->prepare("SELECT COUNT(*) FROM ajustes_estetica WHERE clave = 'brand_accent'");
    $check->execute();
    if ($check->fetchColumn() == 0) {
        $db->query("INSERT INTO ajustes_estetica (clave, valor) VALUES ('brand_accent', '#f5bd1f')");
        echo "INFRAESTRUCTURA DE ACENTO CREADA CON ÉXITO";
    } else {
        echo "INFRAESTRUCTURA YA EXISTENTE";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
unlink(__FILE__); // Autodestrucción por seguridad
?>
