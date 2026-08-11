<?php
function getRecentFiles(string $dir, array &$results = []) : array {
    $files = scandir($dir);
    foreach ($files as $key => $value) {
        $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
        if (!is_dir($path)) {
            $mtime = filemtime($path);
            if ($mtime > time() - 7 * 24 * 3600) {
                $results[] = [
                    'path' => $path,
                    'mtime' => date('Y-m-d H:i:s', $mtime)
                ];
            }
        } else if ($value != "." && $value != "..") {
            getRecentFiles($path, $results);
        }
    }
    return $results;
}

$recent = getRecentFiles('C:/xampp/htdocs/sistema_escolar');
usort($recent, function($a, $b) {
    return strcmp($b['mtime'], $a['mtime']);
});

foreach (array_slice($recent, 0, 30) as $file) {
    echo "{$file['mtime']} - {$file['path']}\n";
}
