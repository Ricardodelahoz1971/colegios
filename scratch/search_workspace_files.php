<?php
declare(strict_types=1);

$dir = 'c:/xampp/htdocs/sistema_escolar';
$files = scandir($dir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $filePath = "{$dir}/{$file}";
    if (is_file($filePath)) {
        $content = file_get_contents($filePath);
        if (preg_match('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $content, $matches)) {
            echo "Workspace File: {$file}\n";
            echo "   Found Hash: " . $matches[0] . "\n\n";
        }
    }
}
