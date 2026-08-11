<?php
declare(strict_types=1);

$dir = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296';
$files = scandir($dir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $filePath = "{$dir}/{$file}";
    if (is_file($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, 'LFV') !== false && preg_match('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $content, $matches)) {
            echo "File: {$file}\n";
            echo "   Found Hash: " . $matches[0] . "\n";
            // Print surrounding context
            $idx = strpos($content, $matches[0]);
            echo "   Context: " . substr($content, max(0, $idx - 100), 300) . "\n\n";
        }
    }
}
