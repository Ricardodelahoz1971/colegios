<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/../php/database/usuarios.db';
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Formatos de Grado en Catálogo ---\n";
    $gradosCat = $pdo->query("SELECT DISTINCT grado FROM ares_catalogo_aprendizajes LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    print_r($gradosCat);

    echo "\n--- Formatos de Curso en Institución ---\n";
    $cursosInst = $pdo->query("SELECT DISTINCT nombre_curso FROM cursos LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
    print_r($cursosInst);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
