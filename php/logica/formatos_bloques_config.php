<?php
declare(strict_types=1);

/**
 * Ficha única de configuración por tipo de bloque del diseñador de formatos.
 * Fuente de verdad compartida entre el canvas (JS, vía json_encode) y el
 * renderizador de impresión (PHP). Ningún otro archivo debe definir estilos
 * o zonas permitidas por su cuenta.
 *
 * Se completa un bloque a la vez, solo cuando se necesita. No inventar
 * estilos para bloques que aún no se han diseñado.
 *
 * @param array $cfg Ajustes estéticos institucionales (tabla ajustes_estetica).
 * @return array Configuración de bloques.
 */
function obtenerConfiguracionBloques(array $cfg): array
{
    return [
        'titulo_colegio' => [
            'zonas_permitidas' => ['header'],
            'estilo' => [
                'fontSize' => 20,
                'fontFamily' => $cfg['school_font'] ?? 'Montserrat',
                'fontWeight' => 'bold',
                'textTransform' => 'uppercase',
                'textAlign' => 'center',
                'color' => $cfg['brand_color'] ?? '#204192',
                'padding_mm' => 1,
                'lineHeight' => 1.2,
                'locked' => ['fontWeight', 'textTransform', 'textAlign', 'fontFamily', 'padding_mm'],
                'editable' => ['fontSize', 'color'],
                'constraints' => [
                    'fontSize' => ['min' => 16, 'max' => 28],
                    'ancho_mm' => ['min' => 40, 'max' => 170, 'auto' => true],
                ],
            ],
        ],
    ];
}
