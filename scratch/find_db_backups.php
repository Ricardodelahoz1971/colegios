<?php
declare(strict_types=1);

function search_dir(string $dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = "{$dir}/{$file}";
        if (is_dir($path)) {
            // Skip large/unnecessary directories
            if ($file === 'assets' || $file === 'uploads' || $file === '.git' || $file === '.gemini') continue;
            search_dir($path);
        } else {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['db', 'sqlite', 'bak', 'sql', 'sqlite3'])) {
                echo "Found DB/Backup File: {$path} (" . filesize($path) . " bytes)\n";
            }
        }
    }
}

search_dir('c:/xampp/htdocs/sistema_escolar');
