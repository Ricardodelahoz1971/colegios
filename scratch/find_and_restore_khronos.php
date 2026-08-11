<?php
// PHP Script to search for khronos.php contents in gemini brain transcripts and restore it.
$brainDir = 'C:\\Users\\Admin\\.gemini\\antigravity-ide\\brain\\';

function searchTranscripts($dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $file) {
        if ($file->isFile() && $file->getExtension() === 'jsonl') {
            $handle = fopen($file->getPathname(), 'r');
            if ($handle) {
                while (($line = fgets($handle)) !== false) {
                    if (strpos($line, 'CENTRO DE MANDO DE HORARIOS') !== false && strpos($line, 'declare(strict_types=1);') !== false) {
                        $data = json_decode($line, true);
                        // We want to find a step containing view_file tool output or matching content
                        if ($data && isset($data['content']) && strpos($data['content'], 'declare(strict_types=1);') !== false) {
                            fclose($handle);
                            return $data['content'];
                        }
                    }
                }
                fclose($handle);
            }
        }
    }
    return null;
}

$raw_content = searchTranscripts($brainDir);
if ($raw_content) {
    // view_file content usually comes formatted with line numbers, e.g. "1: <?php\n2: \n..."
    // Let's parse it and strip the line numbers
    $lines = explode("\n", $raw_content);
    $clean_lines = [];
    foreach ($lines as $line) {
        if (preg_match('/^\s*\d+:\s*(.*)$/', $line, $matches)) {
            $clean_lines[] = $matches[1];
        } else {
            // Keep lines that don't match (e.g. if they are part of a multiline block without numbers, though view_file usually numbers all lines)
            $clean_lines[] = $line;
        }
    }
    
    $restored_content = implode("\n", $clean_lines);
    // Let's check if the restored content starts with <?php
    if (strpos($restored_content, '<?php') !== false) {
        // We restore it
        file_put_contents('C:\\xampp\\htdocs\\sistema_escolar\\php\\vistas\\khronos.php', $restored_content);
        echo "Exito: khronos.php restaurado correctamente.\n";
        exit(0);
    }
}

echo "Fallo: No se pudo restaurar el archivo desde las transcripciones.\n";
