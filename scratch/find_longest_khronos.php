<?php
$brainDir = 'C:\\Users\\Admin\\.gemini\\antigravity-ide\\brain\\';

$longest = '';
$longestFile = '';
$longestLen = 0;

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($brainDir));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'jsonl') {
        $handle = fopen($file->getPathname(), 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (strpos($line, 'CENTRO DE MANDO DE HORARIOS') !== false) {
                    $data = json_decode($line, true);
                    if ($data && isset($data['content'])) {
                        $len = strlen($data['content']);
                        if ($len > $longestLen) {
                            $longestLen = $len;
                            $longest = $data['content'];
                            $longestFile = $file->getPathname();
                        }
                    }
                    // Also check tool calls args or other fields
                    if ($data && isset($data['tool_calls'])) {
                        foreach ($data['tool_calls'] as $tc) {
                            if (isset($tc['args']['ReplacementContent'])) {
                                $len = strlen($tc['args']['ReplacementContent']);
                                if ($len > $longestLen) {
                                    $longestLen = $len;
                                    $longest = $tc['args']['ReplacementContent'];
                                    $longestFile = $file->getPathname();
                                }
                            }
                        }
                    }
                }
            }
            fclose($handle);
        }
    }
}

echo "Longest found in: $longestFile with length: $longestLen\n";
if ($longest) {
    file_put_contents('C:\\xampp\\htdocs\\sistema_escolar\\scratch\\extracted_longest.txt', $longest);
    echo "Saved to scratch/extracted_longest.txt\n";
}
