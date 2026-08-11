<?php
include_once __DIR__ . '/../php/db.php';

$keywords = [
    'fisica' => ['fuerza', 'movimiento', 'reposo', 'velocidad', 'energía', 'choque', 'péndulo', 'caída', 'resorte', 'sonido', 'luz', 'ondas', 'carga', 'eléctrica', 'magnética', 'voltaje', 'circuito', 'corriente', 'atómico', 'nuclear'],
    'quimica' => ['reacción', 'químico', 'oxido-reducción', 'compuesto', 'inorgánico', 'orgánico', 'homólisis', 'heterólisis', 'molécula', 'enlace', 'estequiometría', 'solución', 'pH', 'pericíclicas'],
    'biologia' => ['genética', 'biotecnología', 'célula', 'ecosistema', 'organismo', 'evolución', 'ambiental', 'tala', 'minería', 'bosques', 'clonación', 'terapias génicas']
];

try {
    $db->beginTransaction();
    
    // Obtener todos los de Naturales
    $stmt = $db->query("SELECT id, enunciado FROM ares_catalogo_aprendizajes WHERE area_id = 1");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($items as $item) {
        $tags = [];
        $texto = mb_strtolower($item['enunciado'], 'UTF-8');
        
        foreach($keywords as $tag => $words) {
            foreach($words as $word) {
                if (strpos($texto, $word) !== false) {
                    $tags[] = $tag;
                    break;
                }
            }
        }
        
        if (!empty($tags)) {
            $tag_str = implode(',', array_unique($tags));
            // Actualizamos aprovechando el campo 'metadata_json' o similar si existiera, 
            // pero como no lo tenemos en el catálogo, vamos a inyectar el tag al inicio del enunciado de forma técnica o usar un campo nuevo.
            // MEJOR: Vamos a añadir una columna 'disciplina' al catálogo.
        }
    }
    
    echo "✅ Análisis de disciplinas completado. Procediendo a crear columna de segmentación...\n";
    $db->exec("ALTER TABLE ares_catalogo_aprendizajes ADD COLUMN disciplina TEXT DEFAULT NULL");
    
    foreach($items as $item) {
        $disciplina = 'general';
        $texto = mb_strtolower($item['enunciado'], 'UTF-8');
        
        foreach($keywords as $tag => $words) {
            foreach($words as $word) {
                if (strpos($texto, $word) !== false) {
                    $disciplina = $tag;
                    break;
                }
            }
        }
        $db->prepare("UPDATE ares_catalogo_aprendizajes SET disciplina = ? WHERE id = ?")->execute([$disciplina, $item['id']]);
    }
    
    $db->commit();
    echo "🚀 Catálogo de Naturales segmentado con éxito (Física/Química/Biología).\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
