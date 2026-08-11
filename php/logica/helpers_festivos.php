<?php
declare(strict_types=1);
// PHP/LOGICA/HELPERS_FESTIVOS.PHP - MOTOR LEGAL COLOMBIANO DE FESTIVOS (LEY EMILIANI v1.0)

/**
 * Retorna todos los festivos nacionales de un año determinado en Colombia.
 * Aplica los traslados al lunes siguiente según la Ley Emiliani (Ley 51 de 1983).
 * 
 * Cada festivo retornado incluye:
 * - fecha: string (YYYY-MM-DD)
 * - titulo: string
 * - descripcion: string (detallando el origen y traslado si aplica)
 * - tipo: 'NACIONAL'
 * - color: '#dc3545'
 */
function calcular_festivos_colombia(int $year): array {
    $festivos = [];

    // 1. Festivos Fijos (No se trasladan)
    $fijos = [
        ['1-1', 'Año Nuevo', 'Festivo Nacional Oficial de Colombia.'],
        ['5-1', 'Día del Trabajo', 'Festivo Nacional Oficial de Colombia.'], // Nota: 1 de mayo
        ['7-20', 'Día de la Independencia', 'Festivo Nacional Oficial de Colombia.'],
        ['8-7', 'Batalla de Boyacá', 'Festivo Nacional Oficial de Colombia.'],
        ['12-8', 'Inmaculada Concepción', 'Festivo Nacional Oficial de Colombia.'],
        ['12-25', 'Navidad', 'Festivo Nacional Oficial de Colombia.']
    ];

    // Mapeo especial para 1 de mayo (Día del Trabajo) escrito como 5-1
    foreach ($fijos as $f) {
        list($m, $d) = explode('-', $f[0]);
        // Corrección del mapeo del mes
        $m_val = (int)$m;
        $d_val = (int)$d;
        $fecha_orig = sprintf('%04d-%02d-%02d', $year, $m_val, $d_val);
        $festivos[] = [
            'fecha' => $fecha_orig,
            'titulo' => $f[1],
            'descripcion' => $f[2],
            'tipo' => 'NACIONAL',
            'color' => 'var(--el-danger)'
        ];
    }

    // 2. Festivos Sujetos a Ley Emiliani (Se trasladan al lunes siguiente si no caen en lunes)
    // Formato: [Mes, Dia, Nombre]
    $emiliani = [
        [1, 6, 'Reyes Magos'],
        [3, 19, 'San José'],
        [6, 29, 'San Pedro y San Pablo'],
        [8, 15, 'Asunción de la Virgen'],
        [10, 12, 'Día de la Raza'],
        [11, 1, 'Todos los Santos'],
        [11, 11, 'Independencia de Cartagena']
    ];

    foreach ($emiliani as $e) {
        $festivos[] = calcular_traslado_emiliani($year, $e[0], $e[1], $e[2]);
    }

    // 3. Festivos Basados en la Pascua (Semana Santa y fiestas móviles)
    $pascua = calcular_domingo_pascua($year);
    
    // Fijos respecto a Pascua
    // Jueves Santo: -3 días
    $jueves_santo = date('Y-m-d', strtotime('-3 days', $pascua));
    $festivos[] = [
        'fecha' => $jueves_santo,
        'titulo' => 'Jueves Santo',
        'descripcion' => 'Festivo Nacional Oficial de Colombia (Semana Santa).',
        'tipo' => 'NACIONAL',
        'color' => 'var(--el-danger)'
    ];

    // Viernes Santo: -2 días
    $viernes_santo = date('Y-m-d', strtotime('-2 days', $pascua));
    $festivos[] = [
        'fecha' => $viernes_santo,
        'titulo' => 'Viernes Santo',
        'descripcion' => 'Festivo Nacional Oficial de Colombia (Semana Santa).',
        'tipo' => 'NACIONAL',
        'color' => 'var(--el-danger)'
    ];

    // Móviles respecto a Pascua (Ley Emiliani: se trasladan al lunes siguiente)
    // Ascensión del Señor: +39 días desde Pascua (cae originalmente jueves, se traslada al lunes siguiente: +43 días)
    $festivos[] = calcular_traslado_emiliani_desde_pascua($pascua, 39, 'Ascensión del Señor');

    // Corpus Christi: +60 días desde Pascua (cae originalmente jueves, se traslada al lunes siguiente: +64 días)
    $festivos[] = calcular_traslado_emiliani_desde_pascua($pascua, 60, 'Corpus Christi');

    // Sagrado Corazón: +68 días desde Pascua (cae originalmente viernes, se traslada al lunes siguiente: +71 días)
    $festivos[] = calcular_traslado_emiliani_desde_pascua($pascua, 68, 'Sagrado Corazón');

    return $festivos;
}

/**
 * Calcula el Domingo de Resurrección (Pascua) para un año dado (Algoritmo de Gauss).
 * Retorna timestamp.
 */
function calcular_domingo_pascua(int $year): int {
    $a = $year % 19;
    $b = $year % 4;
    $c = $year % 7;
    $d = (19 * $a + 24) % 30;
    $e = (2 * $b + 4 * $c + 6 * $d + 5) % 7;
    $dia = 22 + $d + $e;
    
    $mes = 3;
    if ($dia > 31) {
        $dia = $dia - 31;
        $mes = 4;
    }
    
    // Casos especiales del algoritmo
    if ($mes === 4 && $dia === 26) {
        $dia = 19;
    }
    if ($mes === 4 && $dia === 25 && $a > 11 && $d === 28) {
        $dia = 18;
    }
    
    return mktime(0, 0, 0, $mes, $dia, $year);
}

/**
 * Calcula el traslado de la Ley Emiliani para un día festivo del calendario fijo.
 */
function calcular_traslado_emiliani(int $year, int $mes, int $dia, string $titulo): array {
    $time_orig = mktime(0, 0, 0, $mes, $dia, $year);
    $w = (int)date('w', $time_orig); // 0: Domingo, 1: Lunes, etc.
    
    $nombres_dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $nombres_meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    $fecha_origen_legible = "{$nombres_dias[$w]} {$dia} de {$nombres_meses[$mes]}";

    if ($w === 1) {
        // Cae exactamente en Lunes, se queda igual
        return [
            'fecha' => date('Y-m-d', $time_orig),
            'titulo' => $titulo,
            'descripcion' => "Festivo Nacional Oficial de Colombia (se celebra en su fecha natural).",
            'tipo' => 'NACIONAL',
            'color' => 'var(--el-danger)'
        ];
    } else {
        // Se corre al lunes siguiente
        $dias_para_lunes = ($w === 0) ? 1 : (8 - $w);
        $time_traslado = strtotime("+{$dias_para_lunes} days", $time_orig);
        
        return [
            'fecha' => date('Y-m-d', $time_traslado),
            'titulo' => $titulo,
            'descripcion' => "Festivo Nacional Oficial de Colombia. Trasladado del {$fecha_origen_legible} por la Ley Emiliani (Ley 51 de 1983).",
            'tipo' => 'NACIONAL',
            'color' => 'var(--el-danger)'
        ];
    }
}

/**
 * Calcula el traslado de la Ley Emiliani para fiestas basadas en la Pascua.
 */
function calcular_traslado_emiliani_desde_pascua(int $pascua_timestamp, int $dias_desde_pascua, string $titulo): array {
    $time_orig = strtotime("+{$dias_desde_pascua} days", $pascua_timestamp);
    $w = (int)date('w', $time_orig);
    
    $nombres_dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $nombres_meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    
    $dia_orig = (int)date('d', $time_orig);
    $mes_orig = (int)date('m', $time_orig);
    $fecha_origen_legible = "{$nombres_dias[$w]} {$dia_orig} de {$nombres_meses[$mes_orig]}";

    if ($w === 1) {
        return [
            'fecha' => date('Y-m-d', $time_orig),
            'titulo' => $titulo,
            'descripcion' => "Festivo Nacional Oficial de Colombia.",
            'tipo' => 'NACIONAL',
            'color' => 'var(--el-danger)'
        ];
    } else {
        $dias_para_lunes = ($w === 0) ? 1 : (8 - $w);
        $time_traslado = strtotime("+{$dias_para_lunes} days", $time_orig);
        
        return [
            'fecha' => date('Y-m-d', $time_traslado),
            'titulo' => $titulo,
            'descripcion' => "Festivo Nacional Oficial de Colombia. Trasladado del {$fecha_origen_legible} por la Ley Emiliani (Ley 51 de 1983).",
            'tipo' => 'NACIONAL',
            'color' => 'var(--el-danger)'
        ];
    }
}
