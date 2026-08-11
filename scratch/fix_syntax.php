<?php
$file = __DIR__ . '/../php/vistas/aula_virtual_estudiante.php';
$content = file_get_contents($file);

// Fix the syntax errors
$content = preg_replace("/if \(r\.tipo_recurso === 'VIDEO'\) \{ icon = 'bi-play-btn-fill'; color = '            if \(r\.tipo_recurso === 'DOC'\) \{ icon = 'bi-file-earmark-word-fill'; color = 'info'; \}/",
"if (r.tipo_recurso === 'VIDEO') { icon = 'bi-play-btn-fill'; color = 'primary'; }
            if (r.tipo_recurso === 'DOC') { icon = 'bi-file-earmark-word-fill'; color = 'info'; }", $content);

$content = str_replace("`;           `;           `;", "`;", $content);
$content = str_replace("`;           `;", "`;", $content);

file_put_contents($file, $content);
echo "Fixed syntax errors in aula_virtual_estudiante.php\n";
