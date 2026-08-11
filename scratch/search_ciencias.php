<?php
declare(strict_types=1);

$dir = __DIR__ . '/../';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $pathname = $file->getPathname();
    // skip scratch, backup files, ai files, etc.
    if (strpos($pathname, 'scratch') !== false || strpos($pathname, '.git') !== false || strpos($pathname, '.gemini') !== false) {
        continue;
    }
    $ext = pathinfo($pathname, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'js', 'html', 'css', 'md'])) {
        continue;
    }
    
    $content = file_get_contents($pathname);
    if (stripos($content, 'ciencias') !== false) {
        echo "MATCH in $pathname:\n";
        $lines = file($pathname);
        foreach ($lines as $i => $line) {
            if (stripos($line, 'ciencias') !== false) {
                echo "  " . ($i + 1) . ": " . trim($line) . "\n";
            }
        }
    }
}
