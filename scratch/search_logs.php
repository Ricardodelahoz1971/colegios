<?php
declare(strict_types=1);

$logsPath = __DIR__ . '/.system_generated/logs/transcript.jsonl';
if (!file_exists($logsPath)) {
    // Try absolute path of the brain directory
    $logsPath = 'C:\\Users\\Admin\\.gemini\\antigravity\\brain\\019ddebe-8a71-45b1-914d-28bb8ea5e296\\.system_generated\\logs\\transcript.jsonl';
}

if (!file_exists($logsPath)) {
    echo "❌ Logs transcript file not found at: $logsPath\n";
    exit(1);
}

echo "========================================================\n";
echo "🔍 BUSCANDO EN TRANSCRIPCIONES HISTÓRICAS DE LOGS\n";
echo "========================================================\n";

$file = fopen($logsPath, 'r');
$found = 0;
while (($line = fgets($file)) !== false) {
    if (str_contains($line, 'LFV') || str_contains($line, 'Francisco Villa')) {
        // Let's decode the JSON line or print the snippet
        $data = json_decode($line, true);
        $content = $data['content'] ?? '';
        
        // Check if this content contains SQLite user dumps
        if (str_contains($content, 'LFV') && (str_contains($content, '$2y$10$') || str_contains($content, 'password'))) {
            echo "🎯 HASH ENCONTRADO EN PASO " . ($data['step_index'] ?? 'unknown') . "!\n";
            // Print a snippet of content around the hash
            $start = strpos($content, 'LFV');
            echo substr($content, max(0, $start - 100), 300) . "\n\n";
            $found++;
        }
        
        // Also inspect tool calls/outputs
        $tool_calls = $data['tool_calls'] ?? [];
        foreach ($tool_calls as $tc) {
            $output = $tc['output'] ?? '';
            if (str_contains($output, 'LFV') && str_contains($output, '$2y$10$')) {
                echo "🎯 HASH ENCONTRADO EN LA SALIDA DEL TOOL DE PASO " . ($data['step_index'] ?? 'unknown') . "!\n";
                $start = strpos($output, 'LFV');
                echo substr($output, max(0, $start - 100), 300) . "\n\n";
                $found++;
            }
        }
    }
}
fclose($file);

if ($found === 0) {
    echo "⚠️ No se encontró rastro del hash original de LFV en los logs comprimidos.\n";
}
echo "========================================================\n";
