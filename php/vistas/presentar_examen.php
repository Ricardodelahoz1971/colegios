<?php
declare(strict_types=1);
// PHP/VISTAS/PRESENTAR_EXAMEN.PHP - EL BÚNKER (ARES) v1.1
session_start();
require_once '../db.php';
require_once '../helpers_elite.php';

// Validar ticket criptográfico
$token = $_GET['token'] ?? '';
if (!isset($_SESSION['ticket_examen']) || $_SESSION['ticket_examen'] !== $token) {
    echo "Acceso Denegado. Ticket Inválido.";
    exit;
}

$mi_id = (int)$_SESSION['usuario_id'];
$asig_id = (int)$_SESSION['ticket_asignacion_id'];

// BLINDAJE: Verificar si el alumno ya entregó este examen
$stmt_check = $db->prepare("SELECT id FROM eval_respuestas WHERE asignacion_id = ? AND estudiante_id = (SELECT id FROM estudiantes WHERE curso_id > 0 AND id = (SELECT estudiante_id FROM usuarios WHERE id = ?))");
$stmt_check->execute([$asig_id, $mi_id]);
if ($stmt_check->fetch()) {
    echo "<div class='card-elite p-5 text-center'>
            <h1 class='text-danger'>ACCESO RESTRINGIDO</h1>
            <p class='subtitle-elite'>Usted ya ha entregado y sellado esta prueba. No es posible reingresar al búnker.</p>
            <div class='mt-4'>
                <a href='../dashboard.php' class='btn-elite px-5'>VOLVER AL PANEL</a>
            </div>
         </div>";
    exit;
}

// Obtener datos maestros de la asignación y la prueba
$stmt = $db->prepare("SELECT a.*, p.titulo, p.instrucciones, p.tiempo_limite, e.nombre_especialidad as materia
                      FROM eval_asignaciones a
                      JOIN eval_pruebas p ON a.prueba_id = p.id
                      JOIN especialidades e ON p.materia_id = e.id
                      WHERE a.id = ?");
$stmt->execute([$asig_id]);
$examen = $stmt->fetch();

$ahora = new DateTime();
$inicio = new DateTime($examen['fecha_inicio']);
$fin = new DateTime($examen['fecha_fin']);

// Cargar preguntas desde la tabla puente (SIN LAS RESPUESTAS CORRECTAS)
$preguntas = [];
$estructura_items = [];
$stmt_preg = $db->prepare("SELECT p.id, p.tipo_id, p.enunciado, p.metadata_json, i.peso 
                           FROM eval_pruebas_items i
                           JOIN eval_preguntas p ON i.pregunta_id = p.id
                           WHERE i.prueba_id = ?
                           ORDER BY i.orden ASC");
$stmt_preg->execute([$examen['prueba_id']]);
$preguntas_db = $stmt_preg->fetchAll(PDO::FETCH_ASSOC);

foreach ($preguntas_db as $p) {
    $contenido = json_decode($p['metadata_json'], true) ?: [];
    
    // FASE PEDAGÓGICA: Generar Banco de Palabras para Completar Huecos (Tipo 4)
    if ($p['tipo_id'] == 4) {
        $palabras = $contenido['respuestas'] ?? [];
        
        // NORMALIZACIÓN: Si es string (ej: "miel, cera"), convertir a array
        if (is_string($palabras)) {
            $palabras = array_map('trim', explode(',', $palabras));
        }

        if (is_array($palabras) && !empty($palabras)) {
            shuffle($palabras);
            $contenido['banco_palabras'] = $palabras;
        }
    }

    // BLINDAJE: Eliminar el campo "correcta" o "correctas"
    if (isset($contenido['correcta'])) unset($contenido['correcta']);
    if (isset($contenido['correctas'])) unset($contenido['correctas']);
    if (isset($contenido['respuestas'])) unset($contenido['respuestas']); 
    if (isset($contenido['opciones'])) {
        foreach ($contenido['opciones'] as &$opc) {
            if (isset($opc['c'])) unset($opc['c']);
            if (isset($opc['es_correcta'])) unset($opc['es_correcta']);
        }
    }

    $p['metadata_json'] = json_encode($contenido);
    $preguntas[$p['id']] = $p;
    $estructura_items[] = ['id' => $p['id'], 'peso' => $p['peso']];
}

$tipo_navegacion = $examen['tipo_navegacion']; // 'libre', 'paginada', 'lineal'
$tiempo_restante_segundos = $fin->getTimestamp() - $ahora->getTimestamp();
// Si el tiempo límite en minutos es menor, ajustamos
$limite_seg = $examen['tiempo_limite'] * 60;
if ($limite_seg > 0 && $limite_seg < $tiempo_restante_segundos) {
    $tiempo_restante_segundos = $limite_seg;
}

// Configuración de Estética Elite
$stmt_estetica = $db->prepare("SELECT clave, valor FROM ajustes_estetica");
$stmt_estetica->execute();
$estetica_db = $stmt_estetica->fetchAll(PDO::FETCH_KEY_PAIR);

$estetica = [
    'color_primario' => $estetica_db['brand_color'] ?? '#0f0664',
    'color_secundario' => $estetica_db['brand_accent'] ?? '#f5bd1f',
    'bg_modo' => $estetica_db['dark_mode'] ?? '0',
    'fuente' => $estetica_db['school_font'] ?? 'Roboto'
]; ?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>BÚNKER ARES - <?= htmlspecialchars($examen['titulo']) ?></title>

    <!-- CSS y Fuentes Base (Carga Local Sello Ares) -->
    <link href="../../assets/libs/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/libs/bootstrap-icons/bootstrap-icons.min.css">
    
    <!-- Google Fonts dinámico según la soberanía institucional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Montserrat:wght@400;700&family=Roboto:wght@300;400;500;700;900&family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../../styles/ui_kit.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../styles/modules/ares_bunker.css?v=<?php echo time(); ?>">
</head>
<body class="bunker--<?= htmlspecialchars($tipo_navegacion, ENT_QUOTES, 'UTF-8') ?>" 
      oncontextmenu="return false;"
      data-total-preguntas="<?= htmlspecialchars((string)count($estructura_items)) ?>"
      data-tipo-navegacion="<?= htmlspecialchars($tipo_navegacion, ENT_QUOTES, 'UTF-8') ?>"
      data-tiempo-restante="<?= htmlspecialchars((string)$tiempo_restante_segundos, ENT_QUOTES, 'UTF-8') ?>"
      data-csrf-token="<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, 'UTF-8') ?>"
      data-asignacion-id="<?= htmlspecialchars((string)$asig_id, ENT_QUOTES, 'UTF-8') ?>"
      data-el-primary="<?= htmlspecialchars($estetica['color_primario'], ENT_QUOTES, 'UTF-8') ?>"
      data-el-primary-rgb="<?php echo el_hex_to_rgb($estetica['color_primario']); ?>"
      data-el-accent="<?= htmlspecialchars($estetica['color_secundario'], ENT_QUOTES, 'UTF-8') ?>"
      data-el-accent-rgb="<?php echo el_hex_to_rgb($estetica['color_secundario']); ?>"
      data-el-font-institutional="'<?= htmlspecialchars($estetica['fuente'], ENT_QUOTES, 'UTF-8') ?>', sans-serif">

    <header class="bunker-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="bg-primary-faded p-2 rounded-3 me-3">
                    <i class="bi bi-shield-lock-fill text-primary fs-4"></i>
                </div>
                <div>
                    <h5 class="mb-0 fw-bold text-accent-elite"><?= htmlspecialchars($examen['titulo']) ?></h5>
                    <small class="bunker-subtitle-elite text-uppercase fs-nano text-accent-elite fw-bold"><?= htmlspecialchars($examen['materia']) ?> | PROGRESO: <span id="progreso-txt" class="text-primary">0</span>/<?= htmlspecialchars((string)count($estructura_items)) ?></small>
                </div>
            </div>
            <div class="text-center px-4 border-is-aero">
                <div class="timer-display" id="reloj-bunker">--:--:--</div>
                <small class="subtitle-elite fs-nano text-uppercase tracking-widest">Misión en Curso</small>
            </div>
            <div>
                <button class="btn-finish-elite" onclick="finalizarExamenManual()">
                    <i class="bi bi-check2-circle me-2"></i> ENTREGAR PRUEBA
                </button>
            </div>
        </div>
    </header>

    <div class="container bunker-content mt-5">
        <div class="row">
            
            <div class="col-lg-8" id="area-preguntas">
                <?php foreach ($estructura_items as $index => $item): 
                    $p = $preguntas[$item['id']];
                    $j = json_decode($p['metadata_json'], true) ?: [];
                    $num = $index + 1; ?>
                <div class="question-card animate__animated animate__fadeInRight <?= $num === 1 ? 'active' : '' // html ok ?>" id="card_<?= htmlspecialchars((string)$num, ENT_QUOTES, 'UTF-8') ?>" data-qid="<?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?>" data-peso="<?= htmlspecialchars((string)$item['peso'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge-item-elite">Ítem #<?= htmlspecialchars((string)$num, ENT_QUOTES, 'UTF-8') ?></span>
                        <div class="text-muted small fw-bold">VALOR: <span class="text-primary"><?= htmlspecialchars((string)$item['peso'], ENT_QUOTES, 'UTF-8') ?> PTS</span></div>
                    </div>
                    
                    <?php if ($p['tipo_id'] != 4): // Si no es 'Completar Huecos', mostrar enunciado normal ?>
                        <div class="fs-5 fw-medium mb-4 text-dark lh-base"><?= $p['enunciado'] // html ok ?></div>
                    <?php endif; ?>
                    
                    <div class="opciones-container">
                        <?php if ($p['tipo_id'] == 1): // Selección Múltiple ?>
                            <?php if (isset($j['opciones']) && is_array($j['opciones'])): ?>
                                <?php foreach ($j['opciones'] as $i => $opc): ?>
                                    <label class="option-container">
                                        <input type="radio" name="resp_<?= htmlspecialchars((string)$p['id']) ?>" id="opt_<?php echo $p['id'].'_'.$i; ?>" class="option-input" value="<?= htmlspecialchars($opc['t'] ?? $opc['texto'] ?? '') ?>" data-idx="idx_<?= htmlspecialchars((string)$i) ?>" onchange="registrarRespuesta(<?= htmlspecialchars((string)$num) ?>)">
                                        <span class="checkmark-elite"></span>
                                        <div class="option-label"><?= htmlspecialchars($opc['t'] ?? $opc['texto'] ?? '') ?></div>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php elseif ($p['tipo_id'] == 2): // Abierta (Ensayo) ?>
                            <textarea name="resp_<?= htmlspecialchars((string)$p['id'], ENT_QUOTES, 'UTF-8') ?>" class="textarea-bunker-elite input-elite" rows="4" placeholder="Desarrolle su respuesta detalladamente..." onchange="registrarRespuesta(<?= htmlspecialchars((string)$num, ENT_QUOTES, 'UTF-8') ?>)"></textarea>
                        <?php elseif ($p['tipo_id'] == 3): // Emparejamiento ?>
                            <?php 
                                $pares = $j['pares'] ?? [];
                                $opciones_b = array_map(function($p) { return $p['b']; }, $pares);
                                shuffle($opciones_b); ?>
                            <div class="card p-4 rounded-4 border-0 bg-primary-faded">
                                <?php foreach ($pares as $idx_p => $par): ?>
                                    <div class="row g-3 mb-2 align-items-center">
                                        <div class="col-md-5">
                                            <div class="p-2 bg-white rounded-3 border-0 shadow-sm small fw-bold text-dark px-3"><?= htmlspecialchars($par['a']) ?></div>
                                        </div>
                                        <div class="col-md-1 text-center"><i class="bi bi-arrow-right-short text-primary fs-4"></i></div>
                                        <div class="col-md-6">
                                            <select class="select-elite select-match" data-idx="<?= htmlspecialchars((string)$idx_p, ENT_QUOTES, 'UTF-8') ?>" onchange="registrarRespuesta(<?= htmlspecialchars((string)$num, ENT_QUOTES, 'UTF-8') ?>)">
                                                <option value="">-- Vincular --</option>
                                                <?php foreach ($opciones_b as $opt_b): ?>
                                                    <option value="<?= htmlspecialchars($opt_b) ?>"><?= htmlspecialchars($opt_b) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif ($p['tipo_id'] == 4): // Completar Huecos ?>
                            <?php 
                                $banco = $j['banco_palabras'] ?? [];
                                if (!empty($banco)):
                            ?>
                                <div class="word-bank-container text-center animate__animated animate__fadeIn">
                                    <div class="fs-nano fw-bold text-muted mb-3 text-uppercase tracking-widest"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Banco de Conceptos Disponibles</div>
                                    <div class="d-flex flex-wrap justify-content-center">
                                        <?php foreach ($banco as $palabra): ?>
                                            <span class="word-pill"><?= htmlspecialchars($palabra) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php 
                                $texto = $p['enunciado'];
                                $contador_input = 0;
                                $texto_con_inputs = preg_replace_callback('/\[\?\]/', function($matches) use (&$contador_input, $num, $estetica) {
                                    $html = '<input type="text" class="input-bunker-elite input-fill-blank d-inline-block mx-1 text-center w-fixed-140" onchange="registrarRespuesta('.$num.')">';
                                    $contador_input++;
                                    return $html;
                                }, $texto); ?>
                            <div class="fill-blanks-area p-4 rounded-4 fs-5 text-dark lh-lg shadow-sm bg-primary-faded border-dashed border-primary border-opacity-25">
                                <?= $texto_con_inputs // html ok ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="pagination-controls d-flex justify-content-between mt-5 pt-4 border-block-start">
                    <button class="btn-elite btn-elite--secondary d-none" id="btn-prev" onclick="cambiarPregunta(-1)">
                        <i class="bi bi-chevron-left me-2"></i> ANTERIOR
                    </button>
                </div>
            </div>

            <div class="col-lg-4 nav-panel ps-elite-1-5">
                <div class="card-elite bg-aero-glass">
                    <div class="card-header p-4 border-0 bg-transparent">
                        <h6 class="mb-0 fw-bold text-uppercase tracking-widest small text-accent-elite">Mapa del Instrumento</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <div class="nav-grid mb-4">
                            <?php for ($i=1; $i <= count($estructura_items); $i++): ?>
                                <button class="nav-btn <?= $i===1 ? 'is-active-bunker':'' // html ok ?>" id="navbtn_<?= htmlspecialchars((string)$i, ENT_QUOTES, 'UTF-8') ?>" onclick="saltarA(<?= htmlspecialchars((string)$i, ENT_QUOTES, 'UTF-8') ?>)"><?= htmlspecialchars((string)$i, ENT_QUOTES, 'UTF-8') ?></button>
                            <?php endfor; ?>
                        </div>

                        <!-- NAVEGACIÓN SECUENCIAL ÉLITE -->
                        <div class="nav-arrows-wrapper">
                            <button class="btn-nav-elite" onclick="cambiarPregunta(-1)" title="Anterior">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <button class="btn-nav-elite" onclick="cambiarPregunta(1)" title="Siguiente">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        
                        <!-- ESTADO SINCRO SUTIL -->
                        <div class="bunker-status-line">
                            <div class="status-pulse"></div>
                            <span class="fs-nano fw-bold text-primary text-uppercase tracking-widest opacity-75">Bóveda Sincronizada</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Motor de Diálogos Élite (Carga Local) -->
    <script src="../../assets/libs/sweetalert2/sweetalert2.all.min.js"></script>
    <script src="../../js/modules/ares_select_engine_v5.js?v=<?php echo time(); ?>"></script>
    <script src="../js/presentar_examen.js" defer></script>
</body>
</html>