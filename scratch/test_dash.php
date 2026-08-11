<?php
session_start();
$_SESSION['identificacion'] = 'admin';
$_SESSION['rol_nombre'] = 'Director General';
$_SESSION['rol_id'] = 1;
$_SESSION['usuario_id'] = 1;

require_once 'c:\xampp\htdocs\sistema_escolar\php\db.php';
require_once 'c:\xampp\htdocs\sistema_escolar\php\auth.php';

ob_start();
require 'c:\xampp\htdocs\sistema_escolar\php\dashboard.php';
$html = ob_get_clean();

if (strpos($html, 'Talento Humano') !== false) {
    echo "Found 'Talento Humano' in HTML output!\n";
} else {
    echo "DID NOT FIND 'Talento Humano' in HTML output.\n";
}

if (strpos($html, 'Administración') !== false) {
    echo "Found 'Administración' in HTML output.\n";
}

if (strpos($html, 'navegarModulo(\'personal\')') !== false) {
    echo "Found 'navegarModulo(personal)' in HTML output.\n";
}
?>
