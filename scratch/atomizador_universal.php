<?php
include_once __DIR__ . '/../php/db.php';

$files = [
    1 => 'DBA_Ciencias_Naturales_Limpio.md',
    2 => 'DBA_Sociales_Limpio.md',
    3 => 'DBA_Matematicas_Limpio.md',
    4 => 'DBA_Lenguaje_Limpio.md'
];

try {
    $db->beginTransaction();
    $db->exec("DELETE FROM ares_catalogo_evidencias"); 

    foreach($files as $area_id => $filename) {
        $file = __DIR__ . "/../lineamientos/$filename";
        if (!file_exists($file)) continue;
        
        $content = file_get_contents($file);
        $grados = preg_split('/## 🎓 (Grado .*)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        for ($i = 1; $i < count($grados); $i += 2) {
            $nombre_grado = trim($grados[$i]);
            $cuerpo_grado = $grados[$i+1] ?? '';
            $dbas = preg_split('/### 📌 DBA #(\d+)/', $cuerpo_grado, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            
            for ($j = 0; $j < count($dbas); $j += 2) {
                if ($j == 0) continue; 
                $num_dba = (int)$dbas[$j-1];
                $cuerpo_dba = $dbas[$j];
                
                $stmt = $db->prepare("SELECT id FROM ares_catalogo_aprendizajes WHERE area_id = ? AND grado = ? AND num_dba = ?");
                $stmt->execute([$area_id, $nombre_grado, $num_dba]);
                $aprendizaje_id = $stmt->fetchColumn();
                
                if ($aprendizaje_id) {
                    if (preg_match('/\*\*Evidencias de Aprendizaje:\*\*(.*?)(?=---|$|##)/s', $cuerpo_dba, $matches)) {
                        $lineas = explode("\n", $matches[1]);
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
        echo "✅ Área $area_id ($filename) atomizada.\n";
    }
    
    $db->commit();
    echo "🚀 PROCESAMIENTO UNIVERSAL COMPLETADO.\n";
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
