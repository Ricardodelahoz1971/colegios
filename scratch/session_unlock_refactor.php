<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php/logica';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Skip if session_write_close is already present
    if (strpos($content, 'session_write_close') !== false) {
        continue;
    }
    
    // Check if it's a controller that guards the session
    if (strpos($content, 'guardia_sesion()') === false) {
        continue;
    }
    
    $modified = false;
    
    // Pattern 1: has a tiene_permiso block that exits
    // Example:
    // if (!tiene_permiso('...')) {
    //     ...
    //     exit;
    // }
    // We want to insert session_write_close(); right after this block.
    $pattern = '/(if\s*\(\s*!tiene_permiso\([^\)]+\)\s*\)\s*\{[^}]+exit[^}]*\})/';
    if (preg_match($pattern, $content, $matches)) {
        $matchedBlock = $matches[1];
        $content = str_replace($matchedBlock, $matchedBlock . "\n    session_write_close();", $content);
        $modified = true;
    } else {
        // Pattern 2: just insert it after guardia_sesion();
        $content = str_replace("guardia_sesion();", "guardia_sesion();\n    session_write_close();", $content);
        $modified = true;
    }
    
    if ($modified) {
        file_put_contents($file, $content);
        echo "Refactored: " . basename($file) . "\n";
    }
}
echo "Done!\n";
