<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
guardia_sesion();
session_write_close();

// Cargar TCPDF desde assets/libs/tcpdf
$tcpdf_path = realpath(__DIR__ . '/../../assets/libs/tcpdf/');
if (!$tcpdf_path || !is_dir($tcpdf_path)) {
    die("Error: TCPDF no encontrado en " . __DIR__ . '/../../assets/libs/tcpdf/');
}

require_once $tcpdf_path . '/tcpdf.php';

$estudiante_id = (int)($_GET['estudiante_id'] ?? 0);
$formato_id = (int)($_GET['formato_id'] ?? 0);

if ($estudiante_id <= 0 || $formato_id <= 0) {
    die("Parámetros inválidos.");
}

// 1. Obtener formato
$stmt_f = $db->prepare("SELECT * FROM formatos_matricula WHERE id = ?");
$stmt_f->execute([$formato_id]);
$formato = $stmt_f->fetch(PDO::FETCH_ASSOC);

if (!$formato) {
    die("Formato no encontrado.");
}

// 2. Obtener estudiante
$stmt_e = $db->prepare("
    SELECT e.*, c.nombre_curso, da.*
    FROM estudiantes e
    LEFT JOIN cursos c ON e.curso_id = c.id
    LEFT JOIN estudiantes_datos_adicionales da ON e.id = da.estudiante_id
    WHERE e.id = ?
");
$stmt_e->execute([$estudiante_id]);
$estudiante = $stmt_e->fetch(PDO::FETCH_ASSOC);

if (!$estudiante) {
    die("Estudiante no encontrado.");
}

// 3. Obtener datos de configuración (igual que imprimir_matricula.php)
$stmt_estetica = $db->query("SELECT clave, valor FROM ajustes_estetica");
$cfg = $stmt_estetica->fetchAll(PDO::FETCH_KEY_PAIR);
$school_name = $cfg['school_name'] ?? 'SISTEMA ESCOLAR ÉLITE';
$school_motto = $cfg['school_motto'] ?? 'Excelencia en Gestión Educativa';
$school_logo = $cfg['school_logo'] ?? 'perseus.png';

// 4. Generar HTML idéntico al preview (reutilizar lógica de imprimir_matricula.php)
// Por ahora, HTML básico. Se puede mejorar reutilizando código de imprimir_matricula.php
$html = <<<EOF
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; }
        .print-document {
            width: 215.9mm;
            height: 279.4mm;
            border: 1px solid #333;
            position: relative;
            overflow: hidden;
        }
        .canvas-block-wrapper {
            position: absolute;
        }
    </style>
</head>
<body>
    <div class="print-document">
        {$formato['contenido_html']}
    </div>
</body>
</html>
EOF;

// 5. Crear PDF con TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage('P', 'A4');
$pdf->SetFont('Arial', '', 11);

// Renderizar HTML
$pdf->writeHTML($html, true, false, true, false, '');

// 6. Descargar PDF
$filename = 'matricula_' . $estudiante_id . '_' . time() . '.pdf';
$pdf->Output($filename, 'D');
?>
