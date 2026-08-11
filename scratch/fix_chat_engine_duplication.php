<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/js/modules/chat_engine.js';
$content = file_get_contents($filePath);

if ($content === false) {
    echo "Error: No se pudo leer chat_engine.js\n";
    exit(1);
}

// Envolver la lógica del submit listener en un centinela para evitar duplicados en SPA
$target = "// LÓGICA DE ENVÍO DE MENSAJES (Delegación Elite)
document.addEventListener('submit', async function(e) {";

$replacement = "// LÓGICA DE ENVÍO DE MENSAJES (Delegación Elite - Protegido contra Duplicación SPA)
if (!window.chatEngineSubmitRegistered) {
    window.chatEngineSubmitRegistered = true;
    document.addEventListener('submit', async function(e) {";

$content = str_replace($target, $replacement, $content);

// Cerrar la llave del if al final de la definición de submit (que termina en la línea 66, antes de abrirChat)
$targetClose = "        } catch (error) {
        }
    }
});

async function abrirChat(id, type) {";

$replacementClose = "        } catch (error) {
        }
    }
});
}

async function abrirChat(id, type) {";

$content = str_replace($targetClose, $replacementClose, $content);

if (file_put_contents($filePath, $content) !== false) {
    echo "¡Prevención de duplicación de submit aplicada con éxito en chat_engine.js!\n";
} else {
    echo "Error: No se pudo guardar chat_engine.js\n";
}
