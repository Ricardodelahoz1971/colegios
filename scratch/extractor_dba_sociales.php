<?php
// scratch/extractor_dba_sociales.php - Versión 5.3 (Social Sciences Deep Clean)
$source = "c:\\xampp\\htdocs\\sistema_escolar\\lineamientos\\DBA_C.Sociales-V2.md";
$output = "c:\\xampp\\htdocs\\sistema_escolar\\lineamientos\\DBA_Sociales_Limpio.md";

if (!file_exists($source)) die("Error: No se encuentra el archivo fuente.");

$content = file_get_contents($source);
$pages = explode("\x0C", $content); 

$extracted = [];

// Verbos específicos para Sociales
$verbos_dba = ['Identifica', 'Utiliza', 'Reconoce', 'Compara', 'Realiza', 'Describe', 'Explica', 'Propone', 'Clasifica', 'Formula', 'Plantea', 'Interpreta', 'Resuelve', 'Justifica', 'Caracteriza', 'Elige', 'Aplica', 'Establece', 'Construye', 'Analiza', 'Relaciona', 'Comprende', 'Se ubica', 'Diferencia', 'Participa', 'Asume', 'Valora', 'Analiza'];

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

    // Gutter Detection
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

    parseBuffer($left_buffer, $page_grade, $extracted, $verbos_dba);
    parseBuffer($right_buffer, $page_grade, $extracted, $verbos_dba);
}

function parseBuffer($buffer, $grade, &$storage, $verbos) {
    $buffer = preg_replace('/\s+/', ' ', $buffer);
    $clean_buffer = preg_replace('/Ejemplo.*$/', '', $buffer);
    $blocks = preg_split('/(\d+\.)/', $clean_buffer, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    
    $current_id = "";
    foreach ($blocks as $block) {
        if (preg_match('/^(\d+)\.$/', $block, $m)) {
            $current_id = $m[1];
            continue;
        }
        
        if ($current_id) {
            $words = explode(' ', trim($block));
            $first_word = $words[0] ?? "";
            // Soporte para "Se ubica"
            if ($first_word == "Se" && isset($words[1])) $first_word = "Se " . $words[1];
            $first_word = preg_replace('/[^a-zA-Záéíóú\s]/', '', $first_word);
            
            if (!in_array($first_word, $verbos)) continue;

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
                // Sociales usa 'l' como viñeta
                $ev_list = preg_split('/\s[lmq]\s/', $ev_block);
                foreach ($ev_list as $ev) {
                    $clean_ev = cleanText($ev);
                    if ($clean_ev && strlen($clean_ev) > 10 && !in_array($clean_ev, $storage[$grade][$current_id]['evidencias'])) {
                        $storage[$grade][$current_id]['evidencias'][] = $clean_ev;
                    }
                }
            }
        }
    }
}

function cleanText($text) {
    $text = str_ireplace('por ejemplo', 'POR_EJEMPLO_TMP', $text);
    // Limpieza de ruido visual de la brújula y números huérfanos
    $text = preg_replace('/\b(Ejemplo|Ejemp|Ej|Grado|Figura|Tabla|q |m |u |l |[NSEW] |[0-9]{1,3} )\b.*$/i', '', $text);
    $text = str_replace('POR_EJEMPLO_TMP', 'por ejemplo', $text);
    $text = trim($text);
    $text = preg_replace('/\s+[a-z]$/i', '', $text);
    return trim($text, " \t\n\r\0\x0B.");
}

$report = "# 🌍 Reporte de Auditoría V5.3: DBA Sociales\n";
$report .= "Extracción Final de ALTA PRECISIÓN para el área de Ciencias Sociales.\n\n";

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
echo "OK: Auditoría de Sociales generada.";
