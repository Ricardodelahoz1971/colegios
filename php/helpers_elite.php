<?php
declare(strict_types=1);

/**
 * 🏛️ ELITE HELPERS v1.0
 * Funciones de soporte para el ADN Vitrina 06
 */

/**
 * Convierte un color HEX en formato RGB para su uso en variables CSS (rgba).
 */
function el_hex_to_rgb(string $hex): string {
    $hex = str_replace("#", "", $hex);
    if(strlen($hex) === 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "$r, $g, $b";
}

/**
 * Sanitiza metadatos de reactivos antes de enviarlos al cliente (Blindaje Ares).
 */
function el_sanitize_item_metadata(array $metadata): array {
    $clean = $metadata;
    $keys_to_purge = ['correcta', 'correctas', 'respuestas', 'justificacion'];
    foreach ($keys_to_purge as $key) {
        if (isset($clean[$key])) unset($clean[$key]);
    }
    
    if (isset($clean['opciones'])) {
        foreach ($clean['opciones'] as &$opc) {
            unset($opc['c'], $opc['es_correcta']);
        }
    }
    return $clean;
}

/**
 * Normaliza de forma robusta y semántica el nombre de un curso a la nomenclatura oficial del MEN.
 */
function el_normalize_grade(string $courseName): string {
    $clean = trim(mb_strtolower($courseName, 'UTF-8'));
    
    // Si contiene números arábigos (ej: "1A", "5B", "10C", "11º")
    if (preg_match('/([0-9]+)/', $clean, $matches)) {
        return "Grado " . $matches[1] . "º";
    }
    
    // Mapeo textual para grados de primaria, secundaria y preescolar
    $mapping = [
        'primero' => 'Grado 1º',
        'segundo' => 'Grado 2º',
        'tercero' => 'Grado 3º',
        'cuarto' => 'Grado 4º',
        'quinto' => 'Grado 5º',
        'sexto' => 'Grado 6º',
        'septimo' => 'Grado 7º',
        'séptimo' => 'Grado 7º',
        'octavo' => 'Grado 8º',
        'noveno' => 'Grado 9º',
        'decimo' => 'Grado 10º',
        'décimo' => 'Grado 10º',
        'once' => 'Grado 11º',
        'transicion' => 'Transición',
        'transición' => 'Transición',
        'preescolar' => 'Preescolar',
        'jardin' => 'Jardín',
        'jardín' => 'Jardín',
        'prejardin' => 'Prejardín',
        'prejardín' => 'Prejardín'
    ];
    
    foreach ($mapping as $key => $val) {
        if (str_contains($clean, $key)) {
            return $val;
        }
    }
    
    // Fallback por defecto a limpiar números
    $numericOnly = preg_replace('/[^0-9]/', '', $courseName);
    return !empty($numericOnly) ? "Grado " . $numericOnly . "º" : "Grado General";
}

/**
 * Resuelve y mapea de forma semántica el grado institucional al grado registrado en el catálogo de aprendizajes del MEN.
 */
function el_resolve_catalogue_grade(PDO $db, int $area_id, string $courseName): string {
    $normalized = el_normalize_grade($courseName);
    
    try {
        $stmt = $db->prepare("SELECT DISTINCT grado FROM ares_catalogo_aprendizajes WHERE area_id = ?");
        $stmt->execute([$area_id]);
        $db_grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($db_grades)) {
            return $normalized;
        }
        
        // 1. Coincidencia exacta
        if (in_array($normalized, $db_grades, true)) {
            return $normalized;
        }
        
        // Fallback robusto para grados de preescolar/transición comerciales
        if (in_array($normalized, ['Jardín', 'Prejardín', 'Preescolar', 'Transición'], true)) {
            if (in_array('Transición', $db_grades, true)) {
                return 'Transición';
            }
        }
        
        // Extracción del número del grado institucional
        preg_match('/([0-9]+)/', $normalized, $matches);
        $num = $matches ? (int)$matches[1] : null;
        
        if ($num !== null) {
            foreach ($db_grades as $db_g) {
                // 2. Coincidencia por rangos (ej: "Grado 1º-3º", "Grado 4º-5º")
                if (preg_match('/([0-9]+)[^\d]*-[^\d]*([0-9]+)/', $db_g, $range_matches)) {
                    $min = (int)$range_matches[1];
                    $max = (int)$range_matches[2];
                    if ($num >= $min && $num <= $max) {
                        return $db_g;
                    }
                }
                
                // 3. Coincidencia por categorías oficiales (Primaria, Básica, Media)
                if (stripos($db_g, 'primaria') !== false && $num >= 1 && $num <= 5) {
                    return $db_g;
                }
                if (stripos($db_g, 'básica') !== false && $num >= 6 && $num <= 9) {
                    return $db_g;
                }
                if (stripos($db_g, 'basica') !== false && $num >= 6 && $num <= 9) {
                    return $db_g;
                }
                if (stripos($db_g, 'media') !== false && $num >= 10 && $num <= 11) {
                    return $db_g;
                }
            }
        }
        
        return $db_grades[0] ?? $normalized;
    } catch (Exception $e) {
        return $normalized;
    }
}


