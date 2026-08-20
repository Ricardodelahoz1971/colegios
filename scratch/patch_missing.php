<?php
$bakFile = 'c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js.backup';
$currentFile = 'c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js';

$bakContent = file_get_contents($bakFile);
$currentContent = file_get_contents($currentFile);

$startMarker = 'function actualizarFiltroCatalogoContextual(tipoDocumento) {';
$endMarker = 'window.sincronizarValoresRealesBadges = function() {';

$startPos = strpos($bakContent, $startMarker);
$endPos = strpos($bakContent, $endMarker, $startPos);

if ($startPos !== false && $endPos !== false) {
    $missingBlock = substr($bakContent, $startPos, $endPos - $startPos);
    
    // Check if it's already in the current file to prevent double injection
    if (strpos($currentContent, $startMarker) === false) {
        $injectPos = strpos($currentContent, $endMarker);
        if ($injectPos !== false) {
            $newContent = substr_replace($currentContent, $missingBlock, $injectPos, 0);
            file_put_contents($currentFile, $newContent);
            echo "INJECTION SUCCESSFUL!";
        } else {
            echo "COULD NOT FIND INJECTION POINT IN CURRENT FILE";
        }
    } else {
        echo "ALREADY INJECTED";
    }
} else {
    echo "COULD NOT FIND MARKERS IN BAK FILE";
}
