<?php
declare(strict_types=1);

// 1. Modificar php/vistas/mensajeria.php para quitar las clases text-* de los botones
$mensajeriaPath = 'c:/xampp/htdocs/sistema_escolar/php/vistas/mensajeria.php';
$mensajeriaContent = file_get_contents($mensajeriaPath);
if ($mensajeriaContent !== false) {
    $mensajeriaContent = str_replace(
        'class="accordion-button collapsed custom-acc-btn text-primary py-3"',
        'class="accordion-button collapsed custom-acc-btn py-3"',
        $mensajeriaContent
    );
    $mensajeriaContent = str_replace(
        'class="accordion-button collapsed custom-acc-btn text-secondary py-3"',
        'class="accordion-button collapsed custom-acc-btn py-3"',
        $mensajeriaContent
    );
    $mensajeriaContent = str_replace(
        'class="accordion-button collapsed custom-acc-btn text-success py-3"',
        'class="accordion-button collapsed custom-acc-btn py-3"',
        $mensajeriaContent
    );
    file_put_contents($mensajeriaPath, $mensajeriaContent);
    echo "Clases text-* removidas de mensajeria.php\n";
}

// 2. Modificar styles/modules/mensajeria.css para añadir estilos limpios sin !important
$cssModulePath = 'c:/xampp/htdocs/sistema_escolar/styles/modules/mensajeria.css';
$cssModuleContent = file_get_contents($cssModulePath);
if ($cssModuleContent !== false) {
    // Eliminar posibles bloques previos al final
    $addon = "\n\n/* 🏷️ FIJACIÓN DE CONTRASTE EN PESTAÑAS (ACORDEÓN HERMES) */\n";
    $addon .= ".custom-acc-btn {\n";
    $addon .= "    font-family: var(--el-font-institutional);\n";
    $addon .= "    font-weight: 800;\n";
    $addon .= "    transition: var(--el-transition-organic);\n";
    $addon .= "}\n\n";
    $addon .= "#accordionChat .accordion-item:nth-child(1) .custom-acc-btn.collapsed {\n";
    $addon .= "    color: var(--el-primary);\n";
    $addon .= "}\n";
    $addon .= "#accordionChat .accordion-item:nth-child(2) .custom-acc-btn.collapsed {\n";
    $addon .= "    color: var(--el-text-muted);\n";
    $addon .= "}\n";
    $addon .= "#accordionChat .accordion-item:nth-child(3) .custom-acc-btn.collapsed {\n";
    $addon .= "    color: var(--el-success);\n";
    $addon .= "}\n\n";
    $addon .= "#accordionChat .custom-acc-btn:not(.collapsed) {\n";
    $addon .= "    background-color: var(--el-primary);\n";
    $addon .= "    color: var(--el-white);\n";
    $addon .= "    box-shadow: 0 0.5rem 1rem rgba(var(--el-primary-rgb), 0.15);\n";
    $addon .= "}\n\n";
    $addon .= "#accordionChat .custom-acc-btn:not(.collapsed) i,\n";
    $addon .= "#accordionChat .custom-acc-btn:not(.collapsed) svg {\n";
    $addon .= "    color: var(--el-white);\n";
    $addon .= "}\n";

    // Insertar justo antes del cierre de @layer modules
    $pos = strrpos($cssModuleContent, '} /* Fin de @layer modules */');
    if ($pos !== false) {
        $cssModuleContent = substr($cssModuleContent, 0, $pos) . $addon . "\n" . substr($cssModuleContent, $pos);
        file_put_contents($cssModulePath, $cssModuleContent);
        echo "Estilos limpios añadidos a styles/modules/mensajeria.css\n";
    }
}

// 3. Modificar styles/ui_kit.css para remover los estilos que pusimos temporalmente
$uiKitPath = 'c:/xampp/htdocs/sistema_escolar/styles/ui_kit.css';
$uiKitContent = file_get_contents($uiKitPath);
if ($uiKitContent !== false) {
    $uiKitContent = str_replace(
        "    /* 🏷️ ACCORDION BUTTONS CONTRAST FIX */\n    .custom-acc-btn {\n        font-family: var(--el-font-institutional) !important;\n        font-weight: 800 !important;\n        transition: var(--el-transition-organic) !important;\n    }\n\n    .accordion-button:not(.collapsed) {\n        background-color: var(--el-primary) !important;\n        color: var(--el-white) !important;\n        box-shadow: 0 0.5rem 1rem rgba(var(--el-primary-rgb), 0.15) !important;\n    }\n\n    .accordion-button:not(.collapsed) i,\n    .accordion-button:not(.collapsed) svg {\n        color: var(--el-white) !important;\n    }\n",
        "",
        $uiKitContent
    );
    file_put_contents($uiKitPath, $uiKitContent);
    echo "Estilos temporales removidos de ui_kit.css\n";
}
