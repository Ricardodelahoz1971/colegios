<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

$stmt = $db->query("SELECT valor FROM ajustes_estetica WHERE clave = 'school_font'");
$font = $stmt->fetchColumn();
echo "FUENTE EN BD: " . ($font ?: "No definida") . "\n";
