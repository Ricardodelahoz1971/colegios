<?php
include_once __DIR__ . '/../php/db.php';

$file = __DIR__ . '/../lineamientos/DBA_Ciencias_Naturales_Limpio.md';
$content = file_get_contents($file);

// Dividir por Grados
$grados = preg_split('/## 🎓 (Grado .*)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

try {
    $db->beginTransaction();
    $db->exec("DELETE FROM ares_catalogo_evidencias"); // Limpieza inicial para sincronización fresca

    for ($i = 1; $i < count($grados); $i += 2) {
        $nombre_grado = trim($grados[$i]);
        $cuerpo_grado = $grados[$i+1] ?? '';
        
        // Dividir por DBA dentro del grado
        $dbas = preg_split('/### 📌 DBA #(\d+)/', $cuerpo_grado, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        for ($j = 0; $j < count($dbas); $j += 2) {
            // El primer elemento (j=0) es texto basura antes del primer DBA del grado
            if ($j == 0) continue; 
            
            $num_dba = (int)$dbas[$j-1]; // El número capturado está en el índice anterior
            $cuerpo_dba = $dbas[$j];
            
            // Buscar el ID del aprendizaje en la DB
            $stmt = $db->prepare("SELECT id FROM ares_catalogo_aprendizajes WHERE area_id = 1 AND grado = ? AND num_dba = ?");
            $stmt->execute([$nombre_grado, $num_dba]);
            $aprendizaje_id = $stmt->fetchColumn();
            
            if ($aprendizaje_id) {
                // Extraer Evidencias
                if (preg_match('/\*\*Evidencias de Aprendizaje:\*\*(.*?)(?=---|$)/s', $cuerpo_dba, $matches)) {
                    $bloque_evidencias = $matches[1];
                    $lineas = explode("\n", $bloque_evidencias);
                    foreach($lineas as $linea) {
                        $linea = trim($linea);
                        if (strpos($linea, '-') === 0) {
                            $evidencia = trim(substr($linea, 1));
                            if (!empty($evidencia)) {
                                $db->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)")
                                   ->execute([$aprendizaje_id, $evidencia]);
                            }
                        }
                    }
                }
            }
        }
    }
    
    $db->commit();
    echo "🚀 Atomización de Evidencias (Naturales) completada.\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
