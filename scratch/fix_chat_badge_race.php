<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/js/modules/chat_engine.js';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer chat_engine.js\n";
    exit(1);
}

// 1. Quitar la llamada a verificarMensajes() del inicio de abrirChat
$target1 = "    if (targetEl) {
        targetEl.classList.add('is-active-elite');
        const badge = targetEl.querySelector('.badge-elite--danger');
        if (badge) badge.remove();
    }
    // Refrescar contadores globales inmediatamente
    if (typeof verificarMensajes === 'function') verificarMensajes();";

$replacement1 = "    if (targetEl) {
        targetEl.classList.add('is-active-elite');
        const badge = targetEl.querySelector('.badge-elite--danger');
        if (badge) badge.remove();
    }";

$content = str_replace($target1, $replacement1, $content);

// 2. Colocar la llamada a verificarMensajes() dentro del bloque de éxito del fetch de abrirChat
$target2 = "        if (newChatMain && chatMain) {
            chatMain.innerHTML = newChatMain.innerHTML;
            const history = document.getElementById('chat-history');
            if (history) history.scrollTop = history.scrollHeight;
        }";

$replacement2 = "        if (newChatMain && chatMain) {
            chatMain.innerHTML = newChatMain.innerHTML;
            const history = document.getElementById('chat-history');
            if (history) history.scrollTop = history.scrollHeight;
            
            // Refrescar contadores globales una vez leídos los mensajes en BD
            if (typeof verificarMensajes === 'function') verificarMensajes();
        }";

$content = str_replace($target2, $replacement2, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Carrera de badges corregida con éxito en chat_engine.js!\n";
} else {
    echo "Error: No se pudo guardar chat_engine.js\n";
}
