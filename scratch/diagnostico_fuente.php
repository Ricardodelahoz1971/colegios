<?php
try {
    $db = new PDO('sqlite:php/database/usuarios.db');
    $stmt = $db->prepare("SELECT valor FROM ajustes_estetica WHERE clave = 'school_font'");
    $stmt->execute();
    $fuente = $stmt->fetchColumn();
    echo "--- REPORTE DE SOBERANÍA TIPOGRÁFICA ---\n";
    echo "FUENTE EN BASE DE DATOS: [" . ($fuente ?: "VACÍO") . "]\n";
    echo "----------------------------------------\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
