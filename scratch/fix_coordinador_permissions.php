<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

try {
    // 1. Normalizar usuario LFV para heredar del rol coordinador
    $stmt = $db->prepare("UPDATE usuarios SET permisos_custom = 0 WHERE usuario = 'LFV'");
    $stmt->execute();
    echo "✅ Usuario LFV normalizado en la base de datos (permisos_custom = 0).\n";
    
    // 2. Garantizar que el rol ID 20 (coordinador) tenga el permiso evaluacion (ID 15)
    $stmt_p = $db->prepare("SELECT COUNT(*) FROM rol_permisos WHERE rol_id = 20 AND permiso_id = 15");
    $stmt_p->execute();
    if ($stmt_p->fetchColumn() == 0) {
        $db->prepare("INSERT INTO rol_permisos (rol_id, permiso_id) VALUES (20, 15)")->execute();
        echo "✅ Permiso 'evaluacion' vinculado al rol coordinador (ID 20).\n";
    } else {
        echo "ℹ️ El permiso 'evaluacion' ya estaba vinculado al rol coordinador.\n";
    }
    
    // 3. Forzar ejecución de db_integridad para sincronizar el resto
    require_once __DIR__ . '/../php/logica/db_integridad.php';
    echo "✅ Ejecutado el motor evolutivo db_integridad.php para sincronización.\n";
    
} catch (PDOException $e) {
    echo "❌ Error actualizando la base de datos: " . $e->getMessage() . "\n";
}
