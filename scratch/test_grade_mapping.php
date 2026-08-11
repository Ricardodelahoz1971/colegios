<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';
require_once __DIR__ . '/../php/helpers_elite.php';

function resolve_db_grade(PDO $db, int $area_id, string $courseName): string {
    $normalized = el_normalize_grade($courseName);
    
    // Query distinct grades in DB for this area
    $stmt = $db->prepare("SELECT DISTINCT grado FROM ares_catalogo_aprendizajes WHERE area_id = ?");
    $stmt->execute([$area_id]);
    $db_grades = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($db_grades)) {
        return $normalized;
    }
    
    // 1. Exact match
    if (in_array($normalized, $db_grades, true)) {
        return $normalized;
    }
    
    // Extract number from normalized (e.g., "Grado 1º" -> 1)
    preg_match('/([0-9]+)/', $normalized, $matches);
    $num = $matches ? (int)$matches[1] : null;
    
    if ($num !== null) {
        foreach ($db_grades as $db_g) {
            // 2. Range match (e.g., "Grado 1º-3º" or "Grado 1-3")
            if (preg_match('/([0-9]+)[^\d]*-[^\d]*([0-9]+)/', $db_g, $range_matches)) {
                $min = (int)$range_matches[1];
                $max = (int)$range_matches[2];
                if ($num >= $min && $num <= $max) {
                    return $db_g;
                }
            }
            
            // 3. Category match (e.g., "Primaria", "Básica", "Media")
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
    
    // Fallback to first available or normalized
    return $db_grades[0] ?? $normalized;
}

echo "Testing Educación Física (Area 9) mapping:\n";
foreach (['1A', '2B', '3A', '4B', '5A', '6B', '7A', '8B', '9A', '10B', '11A'] as $c) {
    echo "  - Course $c -> " . resolve_db_grade($db, 9, $c) . "\n";
}

echo "\nTesting Matemáticas (Area 3) mapping:\n";
foreach (['1A', '5B', '11A'] as $c) {
    echo "  - Course $c -> " . resolve_db_grade($db, 3, $c) . "\n";
}
