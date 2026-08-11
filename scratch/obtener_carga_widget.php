<?php
declare(strict_types=1);

$dbPath = __DIR__ . '/../php/database/usuarios.db';
try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Simulamos un docente para la prueba (ID 11) o el actual si es posible
    $user_id = 11; 

    echo "--- Carga Académica del Docente ---\n";
    $stmt = $pdo->prepare("
        SELECT ca.id as carga_id, ca.curso_id, ca.especialidad_id, 
               c.nombre_curso, e.nombre_especialidad, e.area_id
        FROM carga_academica ca
        JOIN cursos c ON ca.curso_id = c.id
        JOIN especialidades e ON ca.especialidad_id = e.id
        WHERE ca.docente_id = :uid
    ");
    $stmt->execute([':uid' => $user_id]);
    $carga = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($carga);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
