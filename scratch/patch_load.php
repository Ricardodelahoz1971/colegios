<?php
$file = 'c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js';
$content = file_get_contents($file);

$find = "    const left_val = parseFloat(jsonBlock.left_mm !== undefined ? jsonBlock.left_mm : jsonBlock.left);
    const left_mm = isNaN(left_val) ? 10.0 : left_val;
    const top_val = parseFloat(jsonBlock.top_mm !== undefined ? jsonBlock.top_mm : jsonBlock.top);
    const top_mm = isNaN(top_val) ? 10.0 : top_val;
    const width_mm = parseFloat(jsonBlock.width_mm) || parseFloat(jsonBlock.width) || null;
    const height_mm = parseFloat(jsonBlock.height_mm) || parseFloat(jsonBlock.height) || null;
    const zone = jsonBlock.zone || 'body';

    const previousActiveZone = activeZone;
    activeZone = jsonBlock.zone || 'body';

    const insertedNode = insertarBloqueEnCanvas(jsonBlock.type, null, undefined, undefined, true);";

$replace = "    // PARIDAD ARQUITECTURA V2 (tipo, x_mm, y_mm) vs V1 (type, left_mm, top_mm)
    const tipoReal = jsonBlock.tipo || jsonBlock.type;
    const xKey = jsonBlock.x_mm !== undefined ? jsonBlock.x_mm : (jsonBlock.left_mm !== undefined ? jsonBlock.left_mm : jsonBlock.left);
    const yKey = jsonBlock.y_mm !== undefined ? jsonBlock.y_mm : (jsonBlock.top_mm !== undefined ? jsonBlock.top_mm : jsonBlock.top);
    const wKey = jsonBlock.w_mm !== undefined ? jsonBlock.w_mm : (jsonBlock.width_mm !== undefined ? jsonBlock.width_mm : jsonBlock.width);
    const hKey = jsonBlock.h_mm !== undefined ? jsonBlock.h_mm : (jsonBlock.height_mm !== undefined ? jsonBlock.height_mm : jsonBlock.height);
    const zoneKey = jsonBlock.zona || jsonBlock.zone || 'body';

    const left_val = parseFloat(xKey);
    const left_mm = isNaN(left_val) ? 10.0 : left_val;
    const top_val = parseFloat(yKey);
    const top_mm = isNaN(top_val) ? 10.0 : top_val;
    const width_mm = parseFloat(wKey) || null;
    const height_mm = parseFloat(hKey) || null;
    const zone = zoneKey;

    const previousActiveZone = activeZone;
    activeZone = zone;

    const insertedNode = insertarBloqueEnCanvas(tipoReal, null, undefined, undefined, true);";

$content = str_replace($find, $replace, $content);

// Y arreglar la comparacion un poco mas abajo donde vuelve a usar jsonBlock.type
$content = str_replace("jsonBlock.type === 'texto'", "tipoReal === 'texto'", $content);
$content = str_replace("includes(jsonBlock.type)", "includes(tipoReal)", $content);
// En las firmas:
$content = str_replace("jsonBlock.type === 'firmas'", "tipoReal === 'firmas'", $content);

file_put_contents($file, $content);
echo "Cargador parchado!";
