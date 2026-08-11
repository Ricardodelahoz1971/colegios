<?php
// 🛡️ DATA INTEGRITY AUDITOR v2.0 (SQLite Edition)
try {
    $db_path = __DIR__ . '/usuarios.db';
    if (!file_exists($db_path)) {
        die("Error: No se encontró el archivo de base de datos en $db_path\n");
    }

    $db = new PDO("sqlite:$db_path");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- ESTRUCTURA DE LA TABLA ESTUDIANTES ---\n";
    $res = $db->query("PRAGMA table_info(estudiantes)");
    while($r = $res->fetch(PDO::FETCH_ASSOC)) {
        echo "{$r['name']} | {$r['type']} | Null: " . ($r['notnull'] ? 'NO' : 'YES') . " | Default: {$r['dflt_value']}\n";
    }

    echo "\n--- AUDITORÍA DE INCONSISTENCIAS (Muestreo) ---\n";
    
    // 1. Verificar escala de promedios
    $stmt_res = $db->prepare("SELECT COUNT(*) as total FROM estudiantes WHERE promedio > 5"); $stmt_res->execute(); $res = $stmt_res;
    $p_altos = $res->fetch(PDO::FETCH_ASSOC)['total'];
    echo "[!] Estudiantes con promedio > 5 (Posible escala 1-100): $p_altos\n";

    // 2. Verificar desincronización de curso vs curso_id
    // Nota: JOIN funciona igual en SQLite
    $stmt_res = $db->prepare("SELECT COUNT(*) as total 
        FROM estudiantes e 
        JOIN cursos c ON e.curso_id = c.id 
        WHERE e.curso != c.nombre_curso
    "); $stmt_res->execute(); $res = $stmt_res;
    $desinc = $res->fetch(PDO::FETCH_ASSOC)['total'];
    echo "[!] Registros con nombre de curso desincronizado (Redundancia fallida): $desinc\n";

    // 3. Verificar correos duplicados o vacíos
    $res = $db->query("SELECT COUNT(*) as total FROM estudiantes WHERE email IS NULL OR email = ''");
    $sin_mail = $res->fetch(PDO::FETCH_ASSOC)['total'];
    echo "[!] Estudiantes sin correo electrónico: $sin_mail\n";

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
