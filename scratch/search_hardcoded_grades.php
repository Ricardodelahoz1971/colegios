<?php
declare(strict_types=1);

$dirs = [__DIR__ . '/../php/vistas', __DIR__ . '/../php/logica'];
$keywords = ['5.0', '1.0', '3.0', 'max="5"', 'min="1"'];
$matches = [];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isDir()) continue;
        $content = file_get_contents($file->getPathname());
        foreach ($keywords as $kw) {
            if (stripos($content, $kw) !== false) {
                // Find matching lines
                $lines = file($file->getPathname());
                foreach ($lines as $i => $line) {
                    if (stripos($line, $kw) !== false) {
                        $matches[] = [
                            'file' => basename($file->getPathname()),
                            'line' => $i + 1,
                            'content' => trim($line),
                            'kw' => $kw
                        ];
                    }
                }
            }
        }
    }
}

echo "=== HARDCODED GRADE CONSTRAINTS FOUND ===\n";
foreach ($matches as $m) {
    // Only print inputs or float boundary checks
    if (stripos($m['content'], 'input') !== false || 
        stripos($m['content'], 'val') !== false || 
        stripos($m['content'], 'calific') !== false || 
        stripos($m['content'], 'nota') !== false ||
        stripos($m['content'], '<=') !== false ||
        stripos($m['content'], '>=') !== false) {
        echo sprintf("- %s [L%d] (%s): %s\n", $m['file'], $m['line'], $m['kw'], $m['content']);
    }
}
