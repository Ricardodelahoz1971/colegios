<?php
declare(strict_types=1);

$overview_path = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296/.system_generated/logs/overview.txt';

if (file_exists($overview_path)) {
    $content = file_get_contents($overview_path);
    echo "Searching overview.txt...\n";
    // Find all bcrypt hashes
    if (preg_match_all('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $content, $matches)) {
        foreach (array_unique($matches[0]) as $hash) {
            echo "Found Hash: {$hash}\n";
            $pos = strpos($content, $hash);
            echo "   Context: " . substr($content, max(0, $pos - 100), 250) . "\n\n";
        }
    } else {
        echo "No hashes found in overview.txt\n";
    }
} else {
    echo "overview.txt not found.\n";
}
