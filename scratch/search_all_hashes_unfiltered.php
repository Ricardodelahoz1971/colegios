<?php
declare(strict_types=1);

$transcript_path = 'C:/Users/Admin/.gemini/antigravity/brain/019ddebe-8a71-45b1-914d-28bb8ea5e296/.system_generated/logs/transcript.jsonl';

$handle = fopen($transcript_path, 'r');
if ($handle) {
    $line_num = 0;
    $seen = [];
    while (($line = fgets($handle)) !== false) {
        $line_num++;
        if (preg_match_all('/\$2y\$10\$[A-Za-z0-9.\/]{53}/', $line, $matches)) {
            foreach ($matches[0] as $hash) {
                if (in_array($hash, $seen)) continue;
                $seen[] = $hash;
                echo "Line {$line_num}: Found Hash {$hash}\n";
                // Print a snippet of 200 chars around the hash
                $pos = strpos($line, $hash);
                $start = max(0, $pos - 100);
                echo "   Context: " . substr($line, $start, 250) . "\n\n";
            }
        }
    }
    fclose($handle);
}
