<?php
declare(strict_types=1);
// PHP/VISTAS/IMPRIMIR_EXAMEN.PHP - MOTOR DE GENERACIÓN FÍSICA v1.0
require_once 'php/db.php';
require_once 'php/auth.php';
require_once 'php/security.php';

if (!tiene_permiso('evaluacion')) {
}

$id = (int)($_GET['id'] ?? 0);
$curso_id = (int)($_GET['curso_id'] ?? 0);

// 1. Obtener Identidad de la Prueba
$stmt = $db->prepare("SELECT p.*, e.nombre_especialidad as materia 
                      FROM eval_pruebas p 
                      JOIN especialidades e ON p.materia_id = e.id 
                      WHERE p.id = ?");
$stmt->execute([$id]);
$p = $stmt->fetch();


// 2. Obtener Estudiantes si hay curso_id
$estudiantes = [];
if ($curso_id) {
    $stmt_est = $db->prepare("SELECT e.*, c.nombre_curso 
                             FROM estudiantes e 
                             JOIN cursos c ON e.curso_id = c.id 
                             WHERE e.curso_id = ? 
                             ORDER BY e.apellido, e.nombre");
    $stmt_est->execute([$curso_id]);
    $estudiantes = $stmt_est->fetchAll();
} else {
    // Modo plantilla (un solo examen vacío)
    $estudiantes = [['id' => 0, 'nombre' => '', 'apellido' => '', 'nombre_curso' => '']];
}

// 3. Obtener Reactivos (Ítems)
$stmt_i = $db->prepare("SELECT p.id, p.enunciado, p.tipo_id, p.metadata_json, i.peso, i.orden
                       FROM eval_preguntas p
                       JOIN eval_pruebas_items i ON p.id = i.pregunta_id
                       WHERE i.prueba_id = ?
                       ORDER BY i.orden ASC");
$stmt_i->execute([$id]);
$items = $stmt_i->fetchAll();

// 4. Obtener Configuración Estética
$stmt_cfg = $db->prepare("SELECT valor FROM ajustes_estetica WHERE clave = 'school_name'");
$stmt_cfg->execute();
$school_name = $stmt_cfg->fetchColumn() ?: 'INSTITUCIÓN EDUCATIVA';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Impresión Masiva - <?php echo htmlspecialchars($p['titulo']); ?></title>
    <link rel="stylesheet" href="assets/libs/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/libs/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.9/dist/katex.min.js"></script>
    <link rel="stylesheet" href="styles/ui_kit.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/modules/imprimir_examen.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="no-print p-3 bg-dark text-white text-center">
    <button onclick="window.print()" class="btn-elite px-5 shadow-sm"><i class="bi bi-printer me-2"></i> LANZAR IMPRESIÓN MASIVA</button>
</div>

<?php foreach ($estudiantes as $est): 
    $id_est = $est['id'] ?: 0;
    $nombre_completo = trim($est['nombre'] . ' ' . $est['apellido']);
    $qr_payload = "PRU_{$id}_EST_{$id_est}";
?>
<div class="hoja-examen">
    <div class="header-examen">
        <div class="text-start">
            <h2 class="school-title"><?php echo htmlspecialchars($school_name); ?></h2>
            <h4 class="exam-title"><?php echo htmlspecialchars($p['titulo']); ?></h4>
            <div class="mt-2 d-flex gap-2">
                <span class="badge bg-secondary">MATERIA: <?php echo htmlspecialchars($p['materia']); ?></span>
                <span class="badge bg-light text-dark border">TIEMPO: <?php echo $p['tiempo_limite']; ?> MIN</span>
            </div>
        </div>
        <div id="qrcode_<?php echo $id_est; ?>" class="qr-placeholder" data-payload="<?php echo $qr_payload; ?>"></div>
    </div>

    <div class="student-data">
        <div class="data-row">
            <span class="data-label">ESTUDIANTE:</span>
            <div class="student-field"><?php echo htmlspecialchars($nombre_completo); ?></div>
        </div>
        <div class="data-row mt-3">
            <span class="data-label">GRADO:</span>
            <div class="student-field field-fixed"><?php echo htmlspecialchars($est['nombre_curso'] ?: ''); ?></div>
            
            <span class="data-label ms-3">FECHA:</span>
            <div class="student-field field-fixed"><?php echo date('d/m/Y'); ?></div>
            
            <span class="data-label ms-3">PUNTAJE:</span>
            <div class="student-field field-fixed"> / ______</div>
        </div>
    </div>

    <div class="instrucciones-box">
        <i class="bi bi-info-circle-fill me-1"></i> <strong>INSTRUCCIONES:</strong> <?php echo strip_tags($p['instrucciones'] ?: 'Lea atentamente cada pregunta antes de responder.', '<b><i><u><strong><em>'); ?>
    </div>

    <div class="cuerpo-examen">
        <?php foreach ($items as $idx => $item): 
            $meta = json_decode($item['metadata_json'], true) ?: [];
        ?>
            <div class="item-container">
                <div class="item-enunciado">
                    <span class="badge-tipo"><?php echo $idx + 1; ?></span>
                    <?php echo strip_tags($item['enunciado'], '<b><i><u><strong><em><img><table><tr><td><th><thead><tbody><span>'); ?>
                    <span class="text-muted small ms-2">(<?php echo $item['peso']; ?> pts)</span>
                </div>

                <div class="item-opciones ps-4">
                    <?php if ($item['tipo_id'] == 1): // Opción Múltiple ?>
                        <?php foreach ($meta['opciones'] ?? [] as $opc): ?>
                            <div class="mb-2">
                                <div class="option-box"></div>
                                <span class="small"><?php echo strip_tags($opc['t'] ?? $opc['texto'] ?? '', '<b><i><u><strong><em><span>'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($item['tipo_id'] == 2): // Abierta ?>
                        <div class="linea-ensayo"></div>
                        <div class="linea-ensayo"></div>
                        <div class="linea-ensayo"></div>
                        <div class="linea-ensayo"></div>
                    <?php elseif ($item['tipo_id'] == 3): // Emparejamiento ?>
                        <p class="small italic text-muted">Vincule cada concepto de la izquierda con su respuesta correcta.</p>
                        <?php foreach ($meta['pares'] ?? [] as $par): ?>
                            <div class="row g-2 mb-2">
                                <div class="col-5 border-bottom small"><?php echo htmlspecialchars($par['a']); ?></div>
                                <div class="col-1 text-center">→</div>
                                <div class="col-6 border-bottom">________________________</div>
                            </div>
                        <?php endforeach; ?>
                    <?php elseif ($item['tipo_id'] == 4): // Completar ?>
                        <p class="small text-muted italic">Complete los espacios en blanco del enunciado superior.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer-examen">
        SISTEMA ARES - CONTROL DE EVALUACIÓN INSTITUCIONAL | FIRMA-PRUEBA: <?php echo strtoupper(substr(md5((string)$id), 0, 8)); ?> | GENERADO: <?php echo date('d/m/Y H:i'); ?>
    </div>
</div>
<?php endforeach; ?>

<script src="assets/libs/qrcode/qrcode.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        setTimeout(() => {
            if (typeof QRCode === 'undefined') {
                return;
            }

            // Seleccionar todos los placeholders de QR
            const placeholders = document.querySelectorAll('.qr-placeholder');
            placeholders.forEach(el => {
                const payload = el.getAttribute('data-payload') || el.id;
                new QRCode(el, {
                    text: payload,
                    width: 120,
                    height: 120,
                    typeNumber: 2,
                    colorDark : "#000000", // Negro necesario para el contraste de escaneo
                    colorLight : "#ffffff", // Blanco necesario para el contraste de escaneo
                    correctLevel : QRCode.CorrectLevel.M
                });
            });
            // Renderizar fórmulas de KaTeX
            document.querySelectorAll('.ql-formula').forEach(el => {
                const formula = el.getAttribute('data-value');
                if (formula) {
                    try {
                        katex.render(formula, el, {
                            throwOnError: false
                        });
                    } catch (err) {
                    }
                }
            });
        }, 200);
    });
</script>

</body>
</html>
