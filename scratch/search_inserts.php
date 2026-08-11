<?php
declare(strict_types=1);

function search_inserts(string $dir) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = "{$dir}/{$file}";
        if (is_dir($path)) {
            search_inserts($path);
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
                $content = file_get_contents($path);
                if (stripos($content, 'INSERT INTO usuarios') !== false) {
                    echo "Found insertion in: {$path}\n";
                    // Print a snippet
                    $pos = stripos($content, 'INSERT INTO usuarios');
                    echo "   Snippet: " . substr($content, $pos, 400) . "\n\n";
                }
            }
        }
    }
}

search_inserts('c:/xampp/htdocs/sistema_escolar/php');
