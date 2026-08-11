<?php
$dirs = glob('C:/Users/Admin/.gemini/antigravity-ide/brain/*', GLOB_ONLYDIR);
$results = [];
foreach ($dirs as $dir) {
    $results[] = [
        'path' => $dir,
        'mtime' => date('Y-m-d H:i:s', filemtime($dir))
    ];
}
usort($results, function($a, $b) {
    return strcmp($b['mtime'], $a['mtime']);
});
foreach (array_slice($results, 0, 15) as $r) {
    echo "{$r['mtime']} - {$r['path']}\n";
}
