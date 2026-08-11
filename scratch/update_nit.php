<?php
require 'php/db.php';
$db->prepare("UPDATE ajustes_estetica SET valor = '800.189.040-3' WHERE clave = 'colegio_nit'")->execute();
echo "NIT actualizado exitosamente en ajustes_estetica.\n";
