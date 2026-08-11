<?php
declare(strict_types=1);

$dir = 'c:/xampp/htdocs/sistema_escolar/scratch';
$files = scandir($dir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $filePath = "{$dir}/{$file}";
    if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'php') {
        $content = file_get_contents($filePath);
        if (preg_match_all('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $content, $matches)) {
            foreach (array_unique($matches[0]) as $hash) {
                echo "Scratch File: {$file}\n";
                echo "   Found Hash: " . $hash . "\n\n";
            }
        }
    }
}
