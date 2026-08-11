<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    $db->exec("ALTER TABLE formatos_matricula ADD COLUMN IF NOT EXISTS tipo_documento VARCHAR(50) NOT NULL DEFAULT 'matricula'");
    $db->exec("ALTER TABLE formatos_matricula ADD COLUMN IF NOT EXISTS tamano_lienzo VARCHAR(50) NOT NULL DEFAULT 'carta'");
    echo "MIGRACION_OK\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
