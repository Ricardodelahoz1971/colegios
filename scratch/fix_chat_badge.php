<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/js/modules/chat_engine.js';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer chat_engine.js\n";
    exit(1);
}

// Reemplazar la sección de estilo activo en abrirChat para remover el badge e iniciar verificación de mensajes
$target = "    // Estilo activo
    document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('is-active-elite'));
    const sel = type === 'group' ? `[data-group-id=\"\${id}\"]` : `[data-user-id=\"\${id}\"]`;
    const targetEl = document.querySelector(sel);
    if (targetEl) targetEl.classList.add('is-active-elite');";

$replacement = "    // Estilo activo y remoción de badge de no leídos
    document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('is-active-elite'));
    const sel = type === 'group' ? `[data-group-id=\"\${id}\"]` : `[data-user-id=\"\${id}\"]`;
    const targetEl = document.querySelector(sel);
    if (targetEl) {
        targetEl.classList.add('is-active-elite');
        const badge = targetEl.querySelector('.badge-elite--danger');
        if (badge) badge.remove();
    }
    // Refrescar contadores globales inmediatamente
    if (typeof verificarMensajes === 'function') verificarMensajes();";

$content = str_replace($target, $replacement, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Remoción de badges al abrir chat corregida en chat_engine.js!\n";
} else {
    echo "Error: No se pudo guardar chat_engine.js\n";
}
