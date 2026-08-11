<?php
// PHP Patch template


$contenidoOriginal = file_get_contents('php/vistas/formatos_matricula.php');

$reemplazo1 = str_replace(
    '<div class="ares-var-card" onclick="seleccionarVariableCatalogo(\'firmas\', \'Sección Firmas\', true)">
        <i class="bi bi-pen text-dark"></i>
        <div><strong>Sección Firmas</strong><span>Firmas rector y acudiente</span></div>
    </div>',
    '<div class="ares-var-card" onclick="seleccionarVariableCatalogo(\'firmas\', \'Sección Firmas\', true)">
        <i class="bi bi-pen text-dark"></i>
        <div><strong>Sección Firmas</strong><span>Firmas rector y acudiente</span></div>
    </div>
    <div class="ares-var-card" onclick="seleccionarVariableCatalogo(\'linea\', \'Línea Divisoria\', true)">
        <i class="bi bi-dash-lg text-primary"></i>
        <div><strong>Línea Divisoria</strong><span>Línea gráfica decorativa</span></div>
    </div>',
    $contenidoOriginal
);

$reemplazo2 = str_replace(
    '<!-- Configuración para Firmas -->
    <div id="config-section-firmas" class="d-none">
        <div class="mb-3">
            <label for="config-firmas-columnas" class="form-label small fw-bold text-uppercase">Número de Firmantes</label>
            <select id="config-firmas-columnas" class="input-elite">
                <option value="2">2 Firmas (Paralelas)</option>
                <option value="3">3 Firmas (Distribuidas)</option>
            </select>
        </div>
    </div>',
    '<!-- Configuración para Firmas -->
    <div id="config-section-firmas" class="d-none">
        <div class="mb-3">
            <label for="config-firmas-columnas" class="form-label small fw-bold text-uppercase">Número de Firmantes</label>
            <select id="config-firmas-columnas" class="input-elite">
                <option value="2">2 Firmas (Paralelas)</option>
                <option value="3">3 Firmas (Distribuidas)</option>
            </select>
        </div>
    </div>

    <!-- Configuración para Línea -->
    <div id="config-section-linea" class="d-none">
        <div class="mb-3">
            <label for="config-linea-grosor" class="form-label small fw-bold text-uppercase d-flex justify-content-between">
                <span>Grosor de la Línea (px)</span>
                <span id="config-linea-grosor-value">2px</span>
            </label>
            <input type="range" id="config-linea-grosor" class="form-range" min="1" max="15" step="1" value="2" oninput="document.getElementById(\'config-linea-grosor-value\').textContent = this.value + \'px\'; if (activeConfigNode) { activeConfigNode.dataset.height = this.value; activeConfigNode.style.height = this.value + \'px\'; }">
        </div>
    </div>',
    $reemplazo1
);

$reemplazo3 = str_replace(
    '
    // Ocultar todas las secciones del modal primero
    document.getElementById(\'config-section-logo\').classList.add(\'d-none\');
    document.getElementById(\'config-section-titulo\').classList.add(\'d-none\');
    document.getElementById(\'config-section-metadatos\').classList.add(\'d-none\');
    document.getElementById(\'config-section-tablas\').classList.add(\'d-none\');
    document.getElementById(\'config-section-firmas\').classList.add(\'d-none\');
    
    if (tipo === \'logo\' || tipo === \'titulo_colegio\' || tipo === \'metadatos\') {
        // Engranaje inoperativo a solicitud del usuario
        return;
    } else if (tipo === \'calificaciones\') {
',
    '
    // Ocultar todas las secciones del modal primero
    document.getElementById(\'config-section-logo\').classList.add(\'d-none\');
    document.getElementById(\'config-section-titulo\').classList.add(\'d-none\');
    document.getElementById(\'config-section-metadatos\').classList.add(\'d-none\');
    document.getElementById(\'config-section-tablas\').classList.add(\'d-none\');
    document.getElementById(\'config-section-firmas\').classList.add(\'d-none\');
    document.getElementById(\'config-section-linea\').classList.add(\'d-none\');
    
    if (tipo === \'logo\' || tipo === \'titulo_colegio\' || tipo === \'metadatos\') {
        // Engranaje inoperativo a solicitud del usuario
        return;
    } else if (tipo === \'linea\') {
        document.getElementById(\'config-section-linea\').classList.remove(\'d-none\');
        const grosor = activeConfigNode.dataset.height || \'2\';
        document.getElementById(\'config-linea-grosor\').value = grosor;
        document.getElementById(\'config-linea-grosor-value\').textContent = grosor + \'px\';
    } else if (tipo === \'calificaciones\') {
',
    $reemplazo2
);

$reemplazo4 = str_replace(
    '
    } else if (tipo === \'firmas\') {
        const cols = document.getElementById(\'config-firmas-columnas\').value;
        activeConfigNode.dataset.columnas = cols;
        
        // Disparar regeneración visual en el canvas si existe la función correspondiente
        if (typeof actualizarBloqueFirmasCanvas === \'function\') {
            actualizarBloqueFirmasCanvas(activeConfigNode, parseInt(cols));
        }
    }

',
    '
    } else if (tipo === \'firmas\') {
        const cols = document.getElementById(\'config-firmas-columnas\').value;
        activeConfigNode.dataset.columnas = cols;
        
        // Disparar regeneración visual en el canvas si existe la función correspondiente
        if (typeof actualizarBloqueFirmasCanvas === \'function\') {
            actualizarBloqueFirmasCanvas(activeConfigNode, parseInt(cols));
        }
    } else if (tipo === \'linea\') {
        const grosor = document.getElementById(\'config-linea-grosor\').value;
        activeConfigNode.dataset.height = grosor;
        activeConfigNode.style.height = grosor + \'px\';
    }

',
    $reemplazo3
);

file_put_contents('php/vistas/formatos_matricula.php', $reemplazo4);
?>