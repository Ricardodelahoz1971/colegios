<?php
$db = new PDO('sqlite:c:\xampp\htdocs\sistema_escolar\php\database\usuarios.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

function tienen_rol_mock($roles) {
    return false; // teacher is not admin/coordinador
}

$mi_id = 3;

echo "=== TESTING SCOPE 'mine' ===\n";
try {
    $query = "SELECT p.*, 
                     t.nombre as tipo_nombre, u.nombre as autor_nombre, 
                     a.num_dba as dba_num, ar.nombre_area as dba_area_nombre
              FROM eval_preguntas p 
              JOIN eval_tipos t ON p.tipo_id = t.id 
              JOIN usuarios u ON p.docente_id = u.id 
              LEFT JOIN ares_catalogo_aprendizajes a ON p.aprendizaje_id = a.id
              LEFT JOIN areas ar ON a.area_id = ar.id
              WHERE 1=1";
    $params = [];

    if (!tienen_rol_mock(['Administrador', 'Coordinador'])) {
        $query .= " AND (p.docente_id = ? OR p.materia_id IN (SELECT especialidad_id FROM carga_academica WHERE docente_id = ?))";
        $params[] = $mi_id;
        $params[] = $mi_id;
    }

    $query .= " AND p.docente_id = ?";
    $params[] = $mi_id;

    $query .= " ORDER BY p.fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $res = $stmt->fetchAll();
    echo "SUCCESS! Count: " . count($res) . "\n";
    print_r($res);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== TESTING SCOPE 'universal' ===\n";
try {
    $query = "SELECT p.*, 
                     t.nombre as tipo_nombre, u.nombre as autor_nombre, 
                     a.num_dba as dba_num, ar.nombre_area as dba_area_nombre
              FROM eval_preguntas p 
              JOIN eval_tipos t ON p.tipo_id = t.id 
              JOIN usuarios u ON p.docente_id = u.id 
              LEFT JOIN ares_catalogo_aprendizajes a ON p.aprendizaje_id = a.id
              LEFT JOIN areas ar ON a.area_id = ar.id
              WHERE 1=1";
    $params = [];

    if (!tienen_rol_mock(['Administrador', 'Coordinador'])) {
        $query .= " AND (p.docente_id = ? OR p.materia_id IN (SELECT especialidad_id FROM carga_academica WHERE docente_id = ?))";
        $params[] = $mi_id;
        $params[] = $mi_id;
    }

    $query .= " AND p.docente_id != ?";
    $params[] = $mi_id;

    $query .= " ORDER BY p.fecha_creacion DESC";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $res = $stmt->fetchAll();
    echo "SUCCESS! Count: " . count($res) . "\n";
    print_r($res);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
