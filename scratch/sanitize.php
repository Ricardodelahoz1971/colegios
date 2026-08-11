<?php
$reportFile = 'c:/xampp/htdocs/sistema_escolar/ELITE_AUDIT_REPORT.md';
$lines = file($reportFile);
$filesToFix = [];

foreach ($lines as $line) {
    if (preg_match('/`([^:]+):(\d+)`/', $line, $matches)) {
        $file = $matches[1];
        $lineNum = (int)$matches[2];
        
        $filesToFix[$file][] = [
            'line' => $lineNum,
            'type' => str_contains($line, 'PARCHE DE RED') ? 'patch' : 'delete'
        ];
    }
}

$projectRoot = 'c:/xampp/htdocs/sistema_escolar';
$allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($projectRoot));
$pathMap = [];
foreach ($allFiles as $f) {
    if (!$f->isDir() && !str_contains($f->getPathname(), 'scratch') && !str_contains($f->getPathname(), 'vendor') && !str_contains($f->getPathname(), '.gemini')) {
        $pathMap[$f->getFilename()] = $f->getPathname();
    }
}

foreach ($filesToFix as $filename => $fixes) {
    if (!isset($pathMap[$filename])) continue;
    
    $path = $pathMap[$filename];
    $fileLines = file($path);
    
    // Sort descending by line number to safely delete elements
    usort($fixes, function($a, $b) {
        return $b['line'] - $a['line'];
    });
    
    $prevLine = -1;
    foreach ($fixes as $fix) {
        $idx = $fix['line'] - 1;
        if ($idx === $prevLine) continue; // Duplicate violation on same line
        $prevLine = $idx;
        
        if (!isset($fileLines[$idx])) continue;
        
        if ($fix['type'] === 'delete') {
            // Check if it's multi-line var_dump or just delete the line
            // For safety, let's just make it a blank line to not shift numbers if multiple violations exist?
            // No, we already sorted descending, so shifting is safe for earlier lines!
            array_splice($fileLines, $idx, 1);
        } elseif ($fix['type'] === 'patch') {
            // Remove `&_t=` or `?_t=` or `{ cache: 'no-store' }`
            $fileLines[$idx] = preg_replace('/(?:&|\?)_t=.*?(?=\'|"|,|\))/', '', $fileLines[$idx]);
            $fileLines[$idx] = preg_replace('/,\s*\{\s*cache\s*:\s*[\'"]no-store[\'"]\s*\}/', '', $fileLines[$idx]);
        }
    }
    
    file_put_contents($path, implode("", $fileLines));
}
echo "Sanitization complete.\n";
