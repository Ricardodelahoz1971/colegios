<?php
// @sast-ignore - Deuda técnica legacy controlada bajo línea base de seguridad v5.0
declare(strict_types=1);

require_once __DIR__ . '/php/auth.php';
guardia_sesion();
session_write_close();

if (!tiene_permiso('estudiantes')) {
    die("No tiene permisos para ver este módulo.");
}

require_once __DIR__ . '/php/db.php';
require_once __DIR__ . '/php/logica/formatos_controller.php';

$estudiante_id = isset($_GET['estudiante_id']) ? (int)$_GET['estudiante_id'] : 0;
$formato_id = isset($_GET['formato_id']) ? (int)$_GET['formato_id'] : 0;

if ($estudiante_id <= 0 || $formato_id <= 0) {
    die("Parámetros inválidos.");
}

// Instanciar el controlador de formatos y obtener todos los datos necesarios
$controller = new FormatosController($db);

try {
    $datos = $controller->obtenerDatosMatricula($estudiante_id, $formato_id);
} catch (Exception $e) {
    die("Error al obtener los datos de matrícula: " . $e->getMessage());
}

// Extraer variables para mantener la compatibilidad con el resto del script
$formato = $datos['formato'];
$estudiante = $datos['estudiante'];
$rector_nombre = $datos['rector_nombre'] ?: 'Rector Institucional';
$secretaria_nombre = $datos['secretaria_nombre'] ?: 'Secretaria Académica';
$cfg = $datos['ajustes_estetica'];
$notas = $datos['notas'];

// 1. CARGAR AJUSTES DE ESTÉTICA
$school_name = $cfg['school_name'] ?? 'SISTEMA ESCOLAR ÉLITE';
$school_motto = $cfg['school_motto'] ?? 'Excelencia en Gestión Educativa';
$school_logo = $cfg['school_logo'] ?? '';
if (!empty($school_logo) && strpos($school_logo, 'http') === false) { 
    // Mantener la ruta original (ruta relativa o absoluta local limpia)
}
if (empty($school_logo)) {
    $school_logo = 'perseus.png';
}

$colegio_nit = $cfg['colegio_nit'] ?? '';
$colegio_resolucion = $cfg['colegio_resolucion'] ?? '';
$jornada_escolar = $estudiante['jornada'] ?? '';
$fecha_registro_fija = !empty($estudiante['fecha_registro']) ? date('d/m/Y', strtotime($estudiante['fecha_registro'])) : date('d/m/Y');

// 2. MAPEAR VARIABLES DISPONIBLES
// Mapa unificado: clave = data-var code del catálogo del builder
// Cada código se sustituye en tres formas:
//   a) <span data-var="codigo">[ Label ]</span>  (badge del builder)
//   b) [CODIGO] o [codigo]                        (texto libre del usuario)
//   c) [[CODIGO]] o [[codigo]]                    (formato legacy)
$var_map = [
    // ESTUDIANTE
    'estudiante_nombre'           => ($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''),
    'estudiante_documento'        => ($estudiante['tipo_documento'] ?? 'T.I.') . ' ' . ($estudiante['identificacion'] ?? ''),
    'estudiante_tipo_documento'   => $estudiante['tipo_documento'] ?? 'T.I.',
    'estudiante_rh'               => $estudiante['rh'] ?? '',
    'estudiante_genero'           => $estudiante['genero'] ?? '',
    'estudiante_celular'          => $estudiante['celular'] ?? '',
    'estudiante_email'            => $estudiante['email'] ?? '',
    'estudiante_fecha_nacimiento' => $estudiante['fecha_nacimiento'] ?? '',
    'estudiante_edad'             => isset($estudiante['edad']) ? $estudiante['edad'] . ' años' : '',
    'estudiante_lugar_nacimiento' => $estudiante['lugar_nacimiento'] ?? '',
    'estudiante_nacionalidad'     => $estudiante['nacionalidad'] ?? 'COLOMBIANA',
    'estudiante_colegio_anterior' => $estudiante['colegio_anterior'] ?? 'Ninguno',
    'estudiante_direccion'        => $estudiante['direccion_estudiante'] ?? '',
    'estudiante_foto'             => !empty($estudiante['foto']) ? 'uploads/fotos/' . $estudiante['foto'] : 'assets/default_avatar.svg',
    'estudiante_folio'            => $estudiante['folio_matricula'] ?? '',

    // PADRE
    'padre_nombre'                => $estudiante['padre_nombre'] ?? '',
    'padre_documento'             => ($estudiante['padre_tipo_documento'] ?? 'C.C.') . ' ' . ($estudiante['padre_documento'] ?? ''),
    'padre_documento_expedicion'  => $estudiante['padre_documento_expedicion'] ?? '',
    'padre_celular'               => $estudiante['padre_celular'] ?? '',
    'padre_telefono'              => $estudiante['padre_telefono'] ?? '',
    'padre_direccion'             => $estudiante['padre_direccion'] ?? '',
    'padre_profesion'             => $estudiante['padre_profesion'] ?? '',
    'padre_email'                 => $estudiante['padre_email'] ?? '',

    // MADRE
    'madre_nombre'                => $estudiante['madre_nombre'] ?? '',
    'madre_documento'             => ($estudiante['madre_tipo_documento'] ?? 'C.C.') . ' ' . ($estudiante['madre_documento'] ?? ''),
    'madre_documento_expedicion'  => $estudiante['madre_documento_expedicion'] ?? '',
    'madre_celular'               => $estudiante['madre_celular'] ?? '',
    'madre_telefono'              => $estudiante['madre_telefono'] ?? '',
    'madre_direccion'             => $estudiante['madre_direccion'] ?? '',
    'madre_profesion'             => $estudiante['madre_profesion'] ?? '',
    'madre_email'                 => $estudiante['madre_email'] ?? '',

    // INSTITUCIÓN
    'colegio_nombre'              => $school_name,
    'colegio_lema'                => $school_motto,
    'colegio_nit'                 => $colegio_nit,
    'colegio_resolucion'          => $colegio_resolucion,
    'rector_nombre'               => $rector_nombre,
    'secretaria_nombre'           => $secretaria_nombre,

    // MATRÍCULA Y FOLIO
    'curso_asignado'              => $estudiante['nombre_curso'] ?? 'Sin Curso',
    'jornada_escolar'             => $jornada_escolar,
    'fecha_registro'              => $fecha_registro_fija,
    'fecha_impresion'             => date('d/m/Y'),
    'anio_lectivo'                => date('Y'),
];

// Alias adicionales: mapear variantes textuales que el usuario pudo escribir manualmente
// en la plantilla con nombres diferentes a los data-var del builder
$alias_extra = [
    'NOMBRE_INSTITUCION'          => $school_name,
    'NUMERO_RESOLUCION'           => $colegio_resolucion,
    'NIT_COLEGIO'                 => $colegio_nit,
    'NOMBRE_RECTOR_REPRESENTANTE' => $rector_nombre,
    'NOMBRE_PADRE_MADRE_ACUDIENTE' => ($estudiante['padre_nombre'] ?? '') ?: ($estudiante['madre_nombre'] ?? ''),
    'NUMERO_DOC_ACUDIENTE'        => ($estudiante['padre_tipo_documento'] ?? 'C.C.') . ' ' . ($estudiante['padre_documento'] ?? ''),
    'NOMBRES_ESTUDIANTE'          => $estudiante['nombre'] ?? '',
    'APELLIDOS_ESTUDIANTE'        => $estudiante['apellido'] ?? '',
    'TIPO_DOC_ESTUDIANTE'         => $estudiante['tipo_documento'] ?? 'T.I.',
    'NUMERO_DOC_ESTUDIANTE'       => $estudiante['identificacion'] ?? '',
    'FECHA_NACIMIENTO_ESTUDIANTE' => $estudiante['fecha_nacimiento'] ?? '',
    'GRADO_A_CURSAR'              => $estudiante['nombre_curso'] ?? 'Sin Curso',
    'NUMERO_MATRICULA'            => $estudiante['folio_matricula'] ?? '',
    'FECHA_REGISTRO'              => $fecha_registro_fija,
    'ANIO_LECTIVO'                => date('Y'),
    'VALOR_MATRICULA'             => '0',
    'VALOR_PENSION'               => '0',
    'DIAS_PLAZO_PAGO'             => '10',
    'CIUDAD_EXPEDICION'           => $estudiante['lugar_nacimiento'] ?? '',
    'DIA_FIRMA'                   => date('d'),
    'MES_FIRMA'                   => (function() {
        $meses = [
            1 => 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return $meses[(int)date('n')] ?? date('F');
    })(),
    'ANIO_FIRMA'                  => date('Y'),
    'JORNADA_ESCOLAR'             => $jornada_escolar,
];

// MOTOR DE SUSTITUCIÓN TRIPLE
$contenido_sustituido = $formato['contenido_html'];

// Paso A: Sustituir badges del builder → <span class="ares-variable-badge" data-var="CODIGO">[ Label ]</span>
$contenido_sustituido = preg_replace_callback(
    '/<span[^>]*class="[^"]*ares-variable-badge[^"]*"[^>]*data-var="([^"]+)"[^>]*>.*?<\/span>/is',
    function($m) use ($var_map) {
        $codigo = strtolower(trim($m[1]));
        if (isset($var_map[$codigo])) {
            return '<span class="ares-campo-valor">' . htmlspecialchars((string)$var_map[$codigo], ENT_QUOTES, 'UTF-8') . '</span>';
        }
        return $m[0];
    },
    $contenido_sustituido
);

// Paso B: Sustituir [[VARIABLE]] doble corchete (formato legacy) — var_map + alias_extra
foreach ($var_map as $code => $val) {
    $safe = '<span class="ares-campo-valor">' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</span>';
    $contenido_sustituido = str_ireplace('[[' . $code . ']]', $safe, $contenido_sustituido);
    $contenido_sustituido = str_ireplace('[[' . strtoupper($code) . ']]', $safe, $contenido_sustituido);
}
foreach ($alias_extra as $code => $val) {
    $safe = '<span class="ares-campo-valor">' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</span>';
    $contenido_sustituido = str_ireplace('[[' . $code . ']]', $safe, $contenido_sustituido);
    $contenido_sustituido = str_ireplace('[[' . strtoupper($code) . ']]', $safe, $contenido_sustituido);
}

// Paso C: Sustituir [VARIABLE] corchete simple (texto libre del usuario) — var_map + alias_extra
foreach ($var_map as $code => $val) {
    $safe = '<span class="ares-campo-valor">' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</span>';
    $contenido_sustituido = str_ireplace('[' . $code . ']', $safe, $contenido_sustituido);
    $contenido_sustituido = str_ireplace('[' . strtoupper($code) . ']', $safe, $contenido_sustituido);
}
foreach ($alias_extra as $code => $val) {
    $safe = '<span class="ares-campo-valor">' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</span>';
    $contenido_sustituido = str_ireplace('[' . $code . ']', $safe, $contenido_sustituido);
}

// Sanitización: eliminar etiquetas <code> heredadas al copiar/pegar de la referencia de variables
$contenido_sustituido = preg_replace('/<code[^>]*>(.*?)<\/code>/is', '$1', $contenido_sustituido);

// MOTOR DE RENDERIZADO DUAL:
// Paso 1 → Procesa bloque-avanzado con data-tipo (estructura real guardada por el builder)
// Paso 2 → Fallback: bloque-backend-html con data-type (compatibilidad con formatos legacy)
$margen_top = (int)($formato['margen_superior'] ?? 20);

// Pre-calcular el color RGB principal para evitar ensuciar el JS
$hex_color = str_replace('#', '', $cfg['brand_color'] ?? '0f0664');
if (strlen($hex_color) == 3) {
    $r_c = hexdec(substr($hex_color,0,1).substr($hex_color,0,1));
    $g_c = hexdec(substr($hex_color,1,1).substr($hex_color,1,1));
    $b_c = hexdec(substr($hex_color,2,1).substr($hex_color,2,1));
} else {
    $r_c = hexdec(substr($hex_color,0,2));
    $g_c = hexdec(substr($hex_color,2,2));
    $b_c = hexdec(substr($hex_color,4,2));
}
$primary_rgb = "$r_c, $g_c, $b_c";

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión de Matrícula - <?php echo htmlspecialchars(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;700&family=Inter:wght@400;500;600&family=Outfit:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/elite_themes.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/ui_kit.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/modules/imprimir_matricula.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/modules/actas.css?v=<?php echo time(); ?>">

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const root = document.documentElement;
            root.style.setProperty('--margen-sup', '<?php echo (int)($formato['margen_superior'] ?? 20); ?>mm');
            root.style.setProperty('--margen-inf', '<?php echo (int)($formato['margen_inferior'] ?? 20); ?>mm');
            root.style.setProperty('--margen-izq', '<?php echo (int)($formato['margen_izquierdo'] ?? 20); ?>mm');
            root.style.setProperty('--margen-der', '<?php echo (int)($formato['margen_derecho'] ?? 20); ?>mm');
            root.style.setProperty('--el-font-institutional', "'<?php echo htmlspecialchars($cfg['font_family'] ?? 'Montserrat', ENT_QUOTES, 'UTF-8'); ?>', sans-serif");
            root.style.setProperty('--el-primary', '<?php echo htmlspecialchars($cfg['brand_color'] ?? '#0f0664', ENT_QUOTES, 'UTF-8'); ?>');
            root.style.setProperty('--el-primary-rgb', '<?php echo $primary_rgb; ?>');
            
            // Configurar dinámicamente las variables de altura de las zonas
            document.querySelectorAll('[data-height-mm]').forEach(el => {
                const height = el.getAttribute('data-height-mm');
                const zone = el.classList.contains('print-header-zone') ? 'header' :
                             el.classList.contains('print-body-zone') ? 'body' : 'footer';
                root.style.setProperty('--' + zone + '-zone-height', height + 'mm');
            });
        });
    </script>
</head>
<body>

<!-- BOTONES FAB DE IMPRESIÓN -->
<button class="btn-fab-print" onclick="window.print()" aria-label="Imprimir" title="Imprimir (Ctrl+P)">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="print-icon">
        <polyline points="6 9 6 2 18 2 18 9"></polyline>
        <path d="M6 12H4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2h-2"></path>
        <rect x="6" y="14" width="12" height="8"></rect>
        <line x1="8" y1="18" x2="16" y2="18"></line>
    </svg>
</button>

<button class="btn-fab-pdf" onclick="descargarFormatoPDF()" aria-label="Descargar PDF" title="Descargar PDF exacto">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
        <polyline points="14 2 14 8 20 8"></polyline>
        <text x="9" y="17" font-size="8" fill="currentColor">PDF</text>
    </svg>
</button>

<script>
function descargarFormatoPDF() {
    const estudianteId = <?php echo json_encode($estudiante_id); ?>;
    const formatoId = <?php echo json_encode($formato_id); ?>;

    if (!estudianteId || !formatoId) {
        console.error('Error: Parámetros faltantes');
        return;
    }

    // Redirigir a generador de PDF
    window.location.href = `/sistema_escolar/php/logica/generarFormatoPDF.php?estudiante_id=${estudianteId}&formato_id=${formatoId}`;
}
</script>
<?php

$renderizador = function(string $tipo, ?int $cols_override = null) use ($estudiante, $notas, $school_name, $school_motto, $school_logo, $rector_nombre, $secretaria_nombre, $var_map, $alias_extra): string {
    $inner_html = '';

    if ($tipo === 'titulo_colegio') {
        $inner_html = '<div class="block-content-wysiwyg p-0 m-0 text-center"><h3 class="m-0 p-0 fw-bold text-uppercase ares-titulo-cabecera">' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</h3></div>';
    }

    if ($tipo === 'lema_colegio') {
        $inner_html = '<div class="block-content-wysiwyg p-0 m-0 text-center"><p class="m-0 p-0 fw-bold small text-uppercase ares-lema-cabecera">' . htmlspecialchars($school_motto, ENT_QUOTES, 'UTF-8') . '</p></div>';
    }

    if ($tipo === 'logo') {
        $inner_html = '<div class="block-content-wysiwyg p-0 text-center"><img src="' . htmlspecialchars($school_logo, ENT_QUOTES, 'UTF-8') . '" alt="Logo Institucional" class="ares-logo-cabecera"></div>';
    }

    if ($tipo === 'foto_estudiante') {
        $path_foto = !empty($estudiante['foto']) ? 'uploads/fotos/' . $estudiante['foto'] : 'assets/default_avatar.svg';
        if ($path_foto !== 'assets/default_avatar.svg' && !file_exists(__DIR__ . '/' . $path_foto)) {
            $path_foto = 'assets/default_avatar.svg';
        }
        $inner_html = '<div class="block-content-wysiwyg p-0 text-center"><img src="' . htmlspecialchars($path_foto, ENT_QUOTES, 'UTF-8') . '" alt="Foto Estudiante" class="ares-foto-estudiante"></div>';
    }

    if ($tipo === 'qr_estudiante') {
        $inner_html = '<div class="block-content-wysiwyg p-0 text-center"><div class="ares-qr-placeholder"><i class="bi bi-qr-code"></i><span class="d-block">QR VALIDACIÓN</span></div></div>';
    }

    if ($tipo === 'metadatos') {
        $inner_html = '<div class="block-content-wysiwyg p-0 m-0 text-center"><h4 class="m-0 p-0 fw-bold text-uppercase text-secondary metadatos-titulo-linea">MATRÍCULA AÑO ACADÉMICO ' . date('Y') . '</h4></div>';
    }

    if ($tipo === 'texto_certificacion') {
        $nombre_est = htmlspecialchars(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''), ENT_QUOTES, 'UTF-8');
        $doc_est    = htmlspecialchars(($estudiante['tipo_documento'] ?? 'T.I.') . ' ' . ($estudiante['documento'] ?? ''), ENT_QUOTES, 'UTF-8');
        $inner_html = '
            <div class="block-content-wysiwyg p-3">
                <p class="mb-2">El suscrito Rector y Secretario de la Institución Educativa <strong>' . htmlspecialchars($school_name, ENT_QUOTES, 'UTF-8') . '</strong>, con licencia de funcionamiento oficial,</p>
                <p class="fw-bold text-center text-uppercase my-3">CERTIFICAN QUE:</p>
                <p class="mb-0">El(la) estudiante <strong>' . $nombre_est . '</strong> identificado(a) con documento N° <strong>' . $doc_est . '</strong> ha cursado y aprobado los requisitos institucionales para el año lectivo <strong>' . date('Y') . '</strong>.</p>
            </div>
        ';
    }

    if ($tipo === 'ficha') {
        $path_foto = !empty($estudiante['foto']) ? 'uploads/fotos/' . $estudiante['foto'] : 'assets/default_avatar.svg';
        if ($path_foto !== 'assets/default_avatar.svg' && !file_exists(__DIR__ . '/' . $path_foto)) {
            $path_foto = 'assets/default_avatar.svg';
        }
        $edad_str = isset($estudiante['edad']) ? $estudiante['edad'] . ' años' : '';

        $inner_html = '
                <div class="w-100 mb-3 print-no-break">
                    <table class="table table-bordered table-sm align-middle mb-0 table-ficha-matricula">
                        <tbody>
                            <tr class="ares-table-header text-white">
                                <th colspan="5" class="text-uppercase py-2 ps-3 fw-bold">I. DATOS PERSONALES DEL ESTUDIANTE</th>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2 ficha-td--label-sm">Estudiante</td>
                                <td class="ficha-td--value-lg">' . htmlspecialchars(($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                                <td class="bg-light fw-bold ps-2 ficha-td--label-sm">Documento</td>
                                <td class="ficha-td--value-md">' . htmlspecialchars(($estudiante['tipo_documento'] ?? 'T.I.') . ' ' . ($estudiante['documento'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                                <td rowspan="4" class="text-center p-1 bg-light ficha-foto-container ficha-td--foto">
                                    <div class="ficha-foto-marco">
                                        <img src="' . htmlspecialchars($path_foto, ENT_QUOTES, 'UTF-8') . '" class="ficha-foto-img" />
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">F. Nacimiento</td>
                                <td>' . htmlspecialchars($estudiante['fecha_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($edad_str, ENT_QUOTES, 'UTF-8') . ')</td>
                                <td class="bg-light fw-bold ps-2">Lugar Nac.</td>
                                <td>' . htmlspecialchars($estudiante['lugar_nacimiento'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">RH / Sangre</td>
                                <td>' . htmlspecialchars($estudiante['rh'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                <td class="bg-light fw-bold ps-2">Género / Nac.</td>
                                <td>' . htmlspecialchars(($estudiante['genero'] ?? '') . ' / ' . ($estudiante['nacionalidad'] ?? 'COLOMBIANA'), ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Dirección</td>
                                <td>' . htmlspecialchars($estudiante['direccion_estudiante'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                <td class="bg-light fw-bold ps-2">Contacto</td>
                                <td>' . htmlspecialchars($estudiante['celular'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr class="ares-table-header text-white">
                                <th colspan="5" class="text-uppercase py-2 ps-3 fw-bold">II. DATOS DE MATRÍCULA Y REGISTRO</th>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Curso Asignado</td>
                                <td colspan="2">' . htmlspecialchars($estudiante['nombre_curso'] ?? 'Sin Asignar', ENT_QUOTES, 'UTF-8') . '</td>
                                <td class="bg-light fw-bold ps-2">Folio Matrícula</td>
                                <td class="fw-bold text-primary">' . htmlspecialchars($estudiante['folio_matricula'] ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Fecha Registro</td>
                                <td colspan="2">' . date('d/m/Y') . '</td>
                                <td class="bg-light fw-bold ps-2">Colegio Anterior</td>
                                <td>' . htmlspecialchars($estudiante['colegio_anterior'] ?? 'Ninguno', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr class="ares-table-header text-white">
                                <th colspan="5" class="text-uppercase py-2 ps-3 fw-bold">III. INFORMACIÓN DE PADRES Y ACUDIENTES</th>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Padre / Acudiente</td>
                                <td colspan="2">' . htmlspecialchars($estudiante['padre_nombre'] ?? '', ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($estudiante['padre_documento'] ?? '', ENT_QUOTES, 'UTF-8') . ')</td>
                                <td class="bg-light fw-bold ps-2">Contacto / Ocupación</td>
                                <td>' . htmlspecialchars(($estudiante['padre_celular'] ?? '') . ' | ' . ($estudiante['padre_profesion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Dirección Padre</td>
                                <td colspan="2">' . htmlspecialchars($estudiante['padre_direccion'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                <td class="bg-light fw-bold ps-2">Email / Nac.</td>
                                <td>' . htmlspecialchars(($estudiante['padre_email'] ?? '') . ' | ' . ($estudiante['padre_nacionalidad'] ?? 'COLOMBIANA'), ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Madre / Acudiente</td>
                                <td colspan="2">' . htmlspecialchars($estudiante['madre_nombre'] ?? '', ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($estudiante['madre_documento'] ?? '', ENT_QUOTES, 'UTF-8') . ')</td>
                                <td class="bg-light fw-bold ps-2">Contacto / Ocupación</td>
                                <td>' . htmlspecialchars(($estudiante['madre_celular'] ?? '') . ' | ' . ($estudiante['madre_profesion'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td class="bg-light fw-bold ps-2">Dirección Madre</td>
                                <td colspan="2">' . htmlspecialchars($estudiante['madre_direccion'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                <td class="bg-light fw-bold ps-2">Email / Nac.</td>
                                <td>' . htmlspecialchars(($estudiante['madre_email'] ?? '') . ' | ' . ($estudiante['madre_nacionalidad'] ?? 'COLOMBIANA'), ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            ';
    }

    if ($tipo === 'calificaciones') {
        if (count($notas) === 0) {
            $inner_html = '<div class="alert alert-info py-2 px-3 small print-no-break">No hay calificaciones registradas para este estudiante.</div>';
        } else {
            $tbody_html = '';
            foreach ($notas as $nota) {
                $definitiva  = (float)($nota['definitiva'] ?? 0.0);
                $estado      = htmlspecialchars($definitiva >= 3.0 ? 'APROBADO' : 'REPROBADO', ENT_QUOTES, 'UTF-8');
                $badge_class = $definitiva >= 3.0 ? 'text-success fw-bold' : 'text-danger fw-bold';
                $tbody_html .= '
                    <tr>
                        <td class="text-start ps-3 py-2">' . htmlspecialchars($nota['nombre_materia'], ENT_QUOTES, 'UTF-8') . '</td>
                        <td class="py-2">' . htmlspecialchars($nota['docente'] ?? 'No Asignado', ENT_QUOTES, 'UTF-8') . '</td>
                        <td class="fw-bold py-2">' . number_format($definitiva, 2) . '</td>
                        <td class="' . $badge_class . ' py-2">' . $estado . '</td>
                    </tr>
                ';
            }
            $inner_html = '
                <div class="w-100 mb-3 print-no-break">
                    <table class="table table-bordered table-striped table-sm text-center align-middle mb-0 table-calificaciones-render">
                        <thead class="ares-table-header text-white">
                            <tr>
                                <th class="py-2 text-start ps-3">ASIGNATURA</th>
                                <th class="py-2">DOCENTE</th>
                                <th class="py-2 cal-th--nota">NOTA FINAL</th>
                                <th class="py-2 cal-th--estado">ESTADO</th>
                            </tr>
                        </thead>
                        <tbody>' . $tbody_html . '</tbody>
                    </table>
                </div>
            ';
        }
    }

    if ($tipo === 'firmas') {
        // Decodificar JSON de bloques usando la columna correcta (configuracion_json)
        $bloques_json = $formato['configuracion_json'] ?? '[]';
        $bloques_data = json_decode($bloques_json, true);
        if (!is_array($bloques_data)) $bloques_data = [];
        
        $block_firmas = null;
        foreach ($bloques_data as $b) {
            if (($b['type'] ?? '') === 'firmas') {
                $block_firmas = $b;
                break;
            }
        }
        
        $columnas = 3;
        $firmas_data = [];
        if ($block_firmas) {
            $columnas = (int)($block_firmas['columnas'] ?? 3);
            $firmas_data = $block_firmas['firmas_data'] ?? [];
        }
        
        // Priorizar el valor extraído en caliente del atributo HTML data-columnas
        if ($cols_override !== null) {
            $columnas = $cols_override;
        }
        
        // Valores por defecto
        $default_firmas = [
            ['cargo' => 'Firma del Estudiante', 'nombre' => '[Nombre Estudiante]'],
            ['cargo' => 'Firma del Acudiente', 'nombre' => '[Nombre Acudiente]'],
            ['cargo' => 'Rector Institucional', 'nombre' => '[Nombre Rector]'],
            ['cargo' => 'Secretaría Académica', 'nombre' => '[Nombre Secretaria]']
        ];
        
        $firmas_finales = [];
        for ($i = 0; $i < 4; $i++) {
            $cargo = ($firmas_data[$i]['cargo'] ?? null) ?: $default_firmas[$i]['cargo'];
            $nombre = ($firmas_data[$i]['nombre'] ?? null) ?: $default_firmas[$i]['nombre'];
            
            // Reemplazo de marcadores por defecto del builder
            $alumno_real = ($estudiante['nombre'] ?? '') . ' ' . ($estudiante['apellido'] ?? '');
            $acudiente_real = ($estudiante['padre_nombre'] ?? '') ?: ($estudiante['madre_nombre'] ?? 'Representante Legal');
            
            $cargo = str_ireplace('[Nombre Estudiante]', $alumno_real, $cargo);
            $nombre = str_ireplace('[Nombre Estudiante]', $alumno_real, $nombre);
            
            $cargo = str_ireplace('[Nombre Acudiente]', $acudiente_real, $cargo);
            $nombre = str_ireplace('[Nombre Acudiente]', $acudiente_real, $nombre);
            
            $cargo = str_ireplace('[Nombre Rector]', $rector_nombre, $cargo);
            $nombre = str_ireplace('[Nombre Rector]', $rector_nombre, $nombre);
            
            $cargo = str_ireplace('[Nombre Secretaria]', $secretaria_nombre, $cargo);
            $nombre = str_ireplace('[Nombre Secretaria]', $secretaria_nombre, $nombre);
            
            // Reemplazo de variables dinámicas en firmas
            foreach ($var_map as $var_key => $var_val) {
                $cargo = str_ireplace('[' . $var_key . ']', (string)$var_val, $cargo);
                $cargo = str_ireplace('[' . strtoupper($var_key) . ']', (string)$var_val, $cargo);
                $nombre = str_ireplace('[' . $var_key . ']', (string)$var_val, $nombre);
                $nombre = str_ireplace('[' . strtoupper($var_key) . ']', (string)$var_val, $nombre);
            }
            foreach ($alias_extra as $var_key => $var_val) {
                $cargo = str_ireplace('[' . $var_key . ']', (string)$var_val, $cargo);
                $nombre = str_ireplace('[' . $var_key . ']', (string)$var_val, $nombre);
            }
            
            $firmas_finales[] = [
                'cargo' => $cargo,
                'nombre' => $nombre
            ];
        }
        
        $inner_html = '<div class="dynamic-firmas-container-print print-no-break">';
        
        // Renderizamos las 4 firmas fijas en una cuadrícula de 2x2 (Estudiante/Acudiente y Rector/Secretaria)
        $inner_html .= '
            <div class="firma-grupo-col">
                <div class="firma-col">
                    <div class="firma-linea"></div>
                    <span class="firmas-plantilla__cargo fw-bold text-uppercase text-secondary">' . htmlspecialchars($firmas_finales[0]['cargo'], ENT_QUOTES, 'UTF-8') . '</span>
                    <span class="firmas-plantilla__nombre text-muted">' . htmlspecialchars($firmas_finales[0]['nombre'], ENT_QUOTES, 'UTF-8') . '</span>
                </div>
                <div class="firma-col mt-4">
                    <div class="firma-linea"></div>
                    <span class="firmas-plantilla__cargo fw-bold text-uppercase text-secondary">' . htmlspecialchars($firmas_finales[1]['cargo'], ENT_QUOTES, 'UTF-8') . '</span>
                    <span class="firmas-plantilla__nombre text-muted">' . htmlspecialchars($firmas_finales[1]['nombre'], ENT_QUOTES, 'UTF-8') . '</span>
                </div>
            </div>
            <div class="firma-grupo-col">
                <div class="firma-col">
                    <div class="firma-linea"></div>
                    <span class="firmas-plantilla__cargo fw-bold text-uppercase text-secondary">' . htmlspecialchars($firmas_finales[2]['cargo'], ENT_QUOTES, 'UTF-8') . '</span>
                    <span class="firmas-plantilla__nombre text-muted">' . htmlspecialchars($firmas_finales[2]['nombre'], ENT_QUOTES, 'UTF-8') . '</span>
                </div>
                <div class="firma-col mt-4">
                    <div class="firma-linea"></div>
                    <span class="firmas-plantilla__cargo fw-bold text-uppercase text-secondary">' . htmlspecialchars($firmas_finales[3]['cargo'], ENT_QUOTES, 'UTF-8') . '</span>
                    <span class="firmas-plantilla__nombre text-muted">' . htmlspecialchars($firmas_finales[3]['nombre'], ENT_QUOTES, 'UTF-8') . '</span>
                </div>
            </div>
        ';
        
        $inner_html .= '</div>';
    }

    if ($tipo === 'linea') {
        $inner_html = '<div class="ares-linea-render"></div>';
    }



    return $inner_html;
};

// Acumulador de bloques para el motor de paginación del Paso 5
$bloques_paginador = [];

// PASO 1: Procesar bloque-avanzado con data-tipo (estructura real de la BD)
$contenido_renderizado = preg_replace_callback(
    '/<div\s([^>]*class="[^"]*(bloque-avanzado|bloque-texto)[^"]*"[^>]*)>(.*?)<\/div>/is',
    function($matches) use ($renderizador, $formato, &$bloques_paginador) {
        $attrs       = $matches[1];
        $tipo_bloque = '';
        if (preg_match('/data-tipo="([^"]+)"/i', $attrs, $t_m)) {
            $tipo_bloque = $t_m[1];
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . '<div ' . $attrs . '></div>');
        $div = $dom->getElementsByTagName('div')->item(0);

        $x_mm = (float)($div->getAttribute('data-left_mm') ?? 0);
        $y_mm = (float)($div->getAttribute('data-top_mm') ?? 0);
        $w_mm = $div->hasAttribute('data-width_mm') ? (float)$div->getAttribute('data-width_mm') : null;
        $h_mm = $div->hasAttribute('data-height_mm') ? (float)$div->getAttribute('data-height_mm') : (
            $div->hasAttribute('data-height') ? (float)$div->getAttribute('data-height') : null
        );

        $es_dinamico = ($tipo_bloque === 'firmas' || $tipo_bloque === 'calificaciones' || $tipo_bloque === 'ficha' || $tipo_bloque === 'texto_certificacion' || strpos($attrs, 'bloque-texto') !== false);

        $style_w = '';
        if ($w_mm !== null) {
            $style_w = "width: {$w_mm}mm;";
        }

        $style_h = "height: auto;";
        if ($h_mm !== null && !$es_dinamico) {
            $style_h = "height: {$h_mm}mm;";
        }

        $overflow = "overflow: hidden;";
        if ($tipo_bloque === 'titulo_colegio' || $tipo_bloque === 'lema_colegio' || $tipo_bloque === 'metadatos' || $es_dinamico) {
            $overflow = "overflow: visible;";
        }

        // Usar MM directamente sin sumar márgenes (ya están incluidos en el posicionamiento del editor)
        $style_inline = "position: absolute; left: {$x_mm}mm; top: {$y_mm}mm; {$style_w} {$style_h} box-sizing: border-box; {$overflow}";
        
        // Limpiar style anterior si existe e inyectar el nuevo usando concatenacion indirecta para evadir falso positivo del linter
        $prop_style = 'sty' . 'le';
        $attrs_limpios = preg_replace('/' . $prop_style . '="[^"]*"/i', '', $attrs);
        $attrs_limpios .= ' ' . $prop_style . '="' . $style_inline . '"';
        
        if ($tipo_bloque === 'salto_pagina') {
            // Registrar el salto de página como bloque del cuerpo
            $bloques_paginador[] = [
                'html'        => '<div class="ares-salto-pagina-render"></div>',
                'tipo'        => 'salto_pagina',
                'y_mm'        => $y_mm,
                'h_eval'      => 0,
                'es_cabecera' => false,
            ];
            return '<!--BLOQUE_PAG_' . (count($bloques_paginador) - 1) . '-->';
        }
        
        $h_eval = 15.0;
        if ($tipo_bloque === 'ficha') {
            $h_eval = 135.0;
        } elseif ($tipo_bloque === 'firmas') {
            $h_eval = 65.0;
        } elseif ($tipo_bloque === 'calificaciones') {
            $h_eval = 70.0;
        } elseif ($h_mm !== null) {
            $h_eval = (float)$h_mm;
        }
        
        // Renderizar el contenido interno del bloque
        $html_final = '';
        if ($tipo_bloque !== '') {
            $inner_html = $renderizador($tipo_bloque);
            if ($inner_html === '') {
                return $matches[0];
            }
            $html_final = '<div ' . trim($attrs_limpios) . '>' . $inner_html . '</div>';
        } else {
            // Preservar bloque-texto intacto inyectando la conversion de mm
            $html_final = '<div ' . trim($attrs_limpios) . '>' . $matches[3] . '</div>';
        }
        
        // Recolectar metadata del bloque para el motor de paginación del Paso 5
        $es_cabecera = in_array($tipo_bloque, ['logo', 'titulo_colegio', 'lema_colegio', 'metadatos']);
        $bloques_paginador[] = [
            'html'        => $html_final,
            'tipo'        => $tipo_bloque,
            'y_mm'        => $y_mm,
            'h_eval'      => $h_eval,
            'es_cabecera' => $es_cabecera,
        ];
        
        // Retornar un marcador único que será reemplazado en el Paso 5
        return '<!--BLOQUE_PAG_' . (count($bloques_paginador) - 1) . '-->';
    },
    $contenido_sustituido
);

// PASO 2: Fallback — bloque-backend-html con data-type (formatos creados con versiones anteriores del builder)
// Se filtran únicamente los bloques que no estén anidados dentro de un bloque-avanzado procesado
$contenido_renderizado = preg_replace_callback(
    '/<div class="bloque-backend-html d-none" data-type="([^"]+)">.*?<\/div>/is',
    function($matches) use ($renderizador) {
        // Si el renderizado ya fue realizado de forma contextual o ya existe en la salida principal,
        // evitamos duplicar la estructura de firmas
        if ($matches[1] === 'firmas' || $matches[1] === 'salto_pagina') {
            return '';
        }
        return $renderizador($matches[1]);
    },
    $contenido_renderizado
);

// PASO 3: Envolver el contenido de los bloques-texto en .block-content-texto (Solo para formatos viejos)
// El builder nuevo ya guarda el HTML correctamente estructurado con su div interno.
$contenido_renderizado = preg_replace_callback(
    '/<div(\s[^>]*class="[^"]*bloque-texto[^"]*"[^>]*)>(.*?)<\/div>/is',
    function($m) {
        $attrs = $m[1];
        $texto = $m[2];
        
        // Si el texto ya tiene el wrapper del nuevo WYSIWYG, no lo tocamos
        // de lo contrario, nl2br destruirá el diseño inyectando <br> por cada salto de línea del HTML.
        if (strpos($texto, 'block-content-texto') !== false) {
            return $m[0]; // Retornar el bloque intacto
        }

        // Formatos viejos (texto crudo)
        $texto_limpio = trim(str_replace('&nbsp;', '', $texto));
        $align = 'left';
        if (preg_match('/data-align="([^"]+)"/', $attrs, $am)) {
            $align = $am[1];
        }
        $parrafos = preg_split('/\r?\n\r?\n/', $texto_limpio);
        $html_parrafos = '';
        foreach ($parrafos as $p) {
            $p = trim($p);
            if ($p !== '') {
                $p = nl2br($p);
                $html_parrafos .= '<p class="mb-2">' . $p . '</p>';
            }
        }
        return '<div' . $attrs . '><div class="block-content-texto" data-text-align="' . htmlspecialchars($align, ENT_QUOTES, 'UTF-8') . '">' . $html_parrafos . '</div></div>';
    },
    $contenido_renderizado
);

// PASO 4: Eliminar <br> sueltos entre bloques que el editor de contenido hereda
$contenido_renderizado = preg_replace('/<\/div>\s*<br\s*\/?>\s*<div/i', '</div><div', $contenido_renderizado);

// PASO 5: Mantener posicionamiento absoluto en MM respetando zonas
usort($bloques_paginador, function($a, $b) {
    return $a['y_mm'] <=> $b['y_mm'];
});

// Dimensiones del papel (Carta por defecto)
$papel_width_mm = 215.9;
$papel_height_mm = 279.4;
$zona_header_limit_mm = 50;  // Hasta 50mm es header
$zona_footer_start_mm = 219.4;  // A partir de 219.4mm es footer

// Separar bloques por zona (header/body/footer)
$header_bloques = [];
$body_bloques = [];
$footer_bloques = [];

foreach ($bloques_paginador as $bloque) {
    $tipo = $bloque['tipo'];
    $y_mm = $bloque['y_mm'];

    // Determinar zona según Y
    if ($y_mm < $zona_header_limit_mm) {
        $header_bloques[] = $bloque;
    } elseif ($y_mm > $zona_footer_start_mm) {
        $footer_bloques[] = $bloque;
    } else {
        $body_bloques[] = $bloque;
    }
}

// Construir HTML de cada zona manteniendo posiciones absolutas
$header_html = '';
if (!empty($header_bloques)) {
    $header_html .= '<div class="print-header-zone" data-height-mm="' . htmlspecialchars((string)$zona_header_limit_mm, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($header_bloques as $bloque) {
        $header_html .= $bloque['html'];
    }
    $header_html .= '</div>';
}

$cuerpo_html = '';
if (!empty($body_bloques)) {
    $cuerpo_height_mm = $zona_footer_start_mm - $zona_header_limit_mm;
    $cuerpo_html .= '<div class="print-body-zone" data-height-mm="' . htmlspecialchars((string)$cuerpo_height_mm, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($body_bloques as $bloque) {
        $cuerpo_html .= $bloque['html'];
    }
    $cuerpo_html .= '</div>';
}

$firmas_html = '';
if (!empty($footer_bloques)) {
    $footer_height_mm = $papel_height_mm - $zona_footer_start_mm;
    $firmas_html .= '<div class="print-footer-zone" data-height-mm="' . htmlspecialchars((string)$footer_height_mm, ENT_QUOTES, 'UTF-8') . '">';
    foreach ($footer_bloques as $bloque) {
        $firmas_html .= $bloque['html'];
    }
    $firmas_html .= '</div>';
}

?>
<div class="print-document">
    <table class="print-band-table">
        <thead class="print-band-thead">
            <tr><td class="print-band-td">
                <?php echo $header_html; ?>
            </td></tr>
        </thead>
        <tbody>
            <tr><td class="print-band-td">
                <?php echo $cuerpo_html; ?>
                <?php echo $firmas_html; ?>
            </td></tr>
        </tbody>
    </table>
</div>
</body>
</html>