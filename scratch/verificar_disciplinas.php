<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/../php/database/usuarios.db';
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- Disciplinas en Catálogo ARES ---\n";
    $disciplinas = $pdo->query("SELECT DISTINCT disciplina FROM ares_catalogo_aprendizajes")->fetchAll(PDO::FETCH_COLUMN);
    print_r($disciplinas);

    echo "\n--- Materias en Configuración ---\n";
    // Busquemos una tabla de materias. En el primer escaneo vi 'areas' y 'especialidades'. 
    // Veamos si hay algo como 'materias' o 'asignaturas'.
    $materias = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE '%materia%'")->fetchAll(PDO::FETCH_COLUMN);
    print_r($materias);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
