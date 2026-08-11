<?php
declare(strict_types=1);

$dir = __DIR__ . '/../php';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($it as $file) {
    if ($file->isDir()) continue;
    $content = file_get_contents($file->getPathname());
    if (stripos($content, 'ciencias') !== false || stripos($content, 'educación') !== false || stripos($content, 'educacion') !== false) {
        echo "Found match in file: " . $file->getPathname() . "\n";
        $lines = file($file->getPathname());
        foreach ($lines as $i => $line) {
            if (stripos($line, 'ciencias') !== false || stripos($line, 'educación') !== false || stripos($line, 'educacion') !== false) {
                echo "  " . ($i + 1) . ": " . trim($line) . "\n";
            }
        }
    }
}
