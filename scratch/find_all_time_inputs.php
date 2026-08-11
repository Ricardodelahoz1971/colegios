<?php
function searchDir($dir) {
    $it = new RecursiveDirectoryIterator($dir);
    $it = new RecursiveIteratorIterator($it);
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            if (stripos($content, 'type="time"') !== false || stripos($content, "type='time'") !== false) {
                echo "Encontrado en: " . $file->getPathname() . "\n";
            }
        }
    }
}

searchDir('C:\\xampp\\htdocs\\sistema_escolar');
