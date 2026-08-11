<?php
// scratch/extractor_dba_naturales.php - Versión 4.0 (Final Polish)
$source = "c:\\xampp\\htdocs\\sistema_escolar\\lineamientos\\DBA_C.Naturales-min.md";
$output = "c:\\xampp\\htdocs\\sistema_escolar\\scratch\\auditoria_dba_naturales.md";

if (!file_exists($source)) die("Error: No se encuentra el archivo fuente.");

$content = file_get_contents($source);
$pages = explode("\x0C", $content); 

$extracted = [];

foreach ($pages as $page) {
    $lines = explode("\n", $page);
    $page_grade = "";
    foreach ($lines as $l) {
        if (preg_match('/Grado\s+(\d+º)/i', $l, $m)) {
            $page_grade = "Grado " . $m[1];
            break;
        }
    }
    if (!$page_grade) continue;
    if (!isset($extracted[$page_grade])) $extracted[$page_grade] = [];

    $gutter_stats = [];
    foreach ($lines as $line) {
        $len = strlen($line);
        for ($i = 40; $i < min($len - 2, 90); $i++) {
            if ($line[$i] === ' ' && $line[$i+1] === ' ' && $line[$i+2] === ' ') {
                $gutter_stats[$i] = ($gutter_stats[$i] ?? 0) + 1;
            }
        }
    }
    arsort($gutter_stats);
    $best_gutter = !empty($gutter_stats) ? key($gutter_stats) + 1 : 65;

    $left_buffer = "";
    $right_buffer = "";
    foreach ($lines as $line) {
        $left_part = mb_strcut($line, 0, $best_gutter);
        $right_part = mb_strcut($line, $best_gutter);
        $left_buffer .= " " . trim($left_part);
        $right_buffer .= " " . trim($right_part);
    }

    parseBuffer($left_buffer, $page_grade, $extracted);
    parseBuffer($right_buffer, $page_grade, $extracted);
}

function parseBuffer($buffer, $grade, &$storage) {
    $buffer = preg_replace('/\s+/', ' ', $buffer);
    $buffer = str_replace(['Derechos Básicos de Aprendizaje • V.1', 'Ciencias Naturales •'], '', $buffer);
    
    $blocks = preg_split('/(\d+\.)/', $buffer, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $current_id = "";
    foreach ($blocks as $block) {
        if (preg_match('/^\d+\.$/', $block)) {
            $current_id = rtrim($block, '.');
            continue;
        }
        
        if ($current_id) {
            $block = str_replace('Evidencias de aprendizaje', '@@@', $block);
            $parts = explode('@@@', $block);
            $dba_text = cleanText(array_shift($parts));

            if (!isset($storage[$grade][$current_id])) {
                $storage[$grade][$current_id] = ['dba' => $dba_text, 'evidencias' => []];
            } else {
                if (strlen($dba_text) > strlen($storage[$grade][$current_id]['dba'])) {
                    $storage[$grade][$current_id]['dba'] = $dba_text;
                }
            }
            
            foreach ($parts as $ev_block) {
                $ev_list = explode(' q ', $ev_block);
                foreach ($ev_list as $ev) {
                    $clean_ev = cleanText($ev);
                    if ($clean_ev && strlen($clean_ev) > 5 && !in_array($clean_ev, $storage[$grade][$current_id]['evidencias'])) {
                        $storage[$grade][$current_id]['evidencias'][] = $clean_ev;
                    }
                }
            }
        }
    }
}

function cleanText($text) {
    // 1. Proteger "por ejemplo"
    $text = str_ireplace('por ejemplo', 'POR_EJEMPLO_TMP', $text);
    
    // 2. Eliminar marcas de "Ejemplo" al final de bloques (Ejemplo 14, Ejemp, etc)
    $text = preg_replace('/\b(Ejemplo|Ejemp|Ej|Grado)\b.*$/i', '', $text);
    
    // 3. Restaurar "por ejemplo"
    $text = str_replace('POR_EJEMPLO_TMP', 'por ejemplo', $text);
    
    // 4. Limpieza de caracteres huérfanos al final (residuos de columna)
    $text = trim($text);
    $text = preg_replace('/\s+[a-z]$/i', '', $text);
    
    return trim($text, " \t\n\r\0\x0B.");
}

// Reporte Final
$report = "# 🧬 Reporte de Auditoría FINAL: DBA Ciencias Naturales\n";
$report .= "Extracción de Grado, Aprendizajes (DBA) y Evidencias.\n\n";

foreach ($extracted as $grade => $dbas) {
    $report .= "## 🎓 $grade\n";
    ksort($dbas, SORT_NUMERIC);
    foreach ($dbas as $id => $data) {
        if (strlen($data['dba']) < 15) continue;
        $report .= "### 📌 DBA #$id\n";
        $report .= "**Enunciado:** " . htmlspecialchars($data['dba']) . "\n\n";
        if (!empty($data['evidencias'])) {
            $report .= "**Evidencias de Aprendizaje:**\n";
            foreach ($data['evidencias'] as $ev) {
                $report .= "- " . htmlspecialchars($ev) . "\n";
            }
        }
        $report .= "\n---\n";
    }
}

file_put_contents($output, $report);
echo "OK: Auditoría FINAL generada.";
