<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/styles/ui_kit.css';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer ui_kit.css\n";
    exit(1);
}

// Reemplazar la regla de acordeón para que use .accordion-button:not(.collapsed)
$target = "    .custom-acc-btn:not(.collapsed) {
        background-color: var(--el-primary) !important;
        color: var(--el-white) !important;
        box-shadow: 0 0.5rem 1rem rgba(var(--el-primary-rgb), 0.15) !important;
    }

    .custom-acc-btn:not(.collapsed) i,
    .custom-acc-btn:not(.collapsed) svg {
        color: var(--el-white) !important;
    }";

$replacement = "    .accordion-button:not(.collapsed) {
        background-color: var(--el-primary) !important;
        color: var(--el-white) !important;
        box-shadow: 0 0.5rem 1rem rgba(var(--el-primary-rgb), 0.15) !important;
    }

    .accordion-button:not(.collapsed) i,
    .accordion-button:not(.collapsed) svg {
        color: var(--el-white) !important;
    }";

$content = str_replace($target, $replacement, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Estilo de acordeón corregido globalmente en ui_kit.css!\n";
} else {
    echo "Error: No se pudo guardar ui_kit.css\n";
}
