<?php
$source_dir = 'C:\\xampp\\htdocs\\sistema_escolar';
$dest_dir = 'C:\\xampp\\htdocs\\copia\\sistema_escolar';

function syncFiles($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..') && ($file != '.git') && ($file != 'scratch') && ($file != '.gemini')) {
            if (is_dir($src . '/' . $file)) {
                syncFiles($src . '/' . $file, $dst . '/' . $file);
            } else {
                $src_mtime = filemtime($src . '/' . $file);
                // Si el archivo fue modificado hoy (29 de julio de 2026)
                if ($src_mtime > strtotime('2026-07-29 00:00:00')) {
                    copy($src . '/' . $file, $dst . '/' . $file);
                    echo "Sincronizado: " . str_replace('C:\\xampp\\htdocs\\sistema_escolar/', '', $src . '/' . $file) . "\n";
                }
            }
        }
    }
    closedir($dir);
}

syncFiles($source_dir, $dest_dir);
echo "Sincronización a copia completada.\n";
