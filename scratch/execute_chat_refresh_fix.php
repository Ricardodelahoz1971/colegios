<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/js/modules/chat_engine.js';
$currentCode = file_get_contents($filePath);

$prompt = "Tengo el siguiente archivo JavaScript `js/modules/chat_engine.js`:
```javascript
{$currentCode}
```

Necesito modificarlo para cumplir con el siguiente requerimiento técnico:
1. En el listener del evento `submit` del formulario:
   - Tras recibir un estado exitoso (`data.status === 'success'`), se debe limpiar el input de texto (`document.getElementById('mensaje-texto').value = '';`).
   - Se debe llamar a `verificarMensajes()` si existe.
   - En lugar de invocar `abrirChat(dest_id, chat_type)` (lo cual recarga toda la interfaz principal y causa que el formulario y selectores parpadeen/recarguen), se debe llamar a una nueva función asíncrona dedicada `actualizarHistorialChat(dest_id, chat_type)` para refrescar de inmediato únicamente la burbuja de mensajes.
2. Definir la función asíncrona `actualizarHistorialChat(chatId, chatType)` en el archivo:
   - Debe buscar el contenedor `#chat-history`. Si no existe, no hace nada.
   - Debe hacer una petición fetch a `logica/obtener_mensajes_ajax.php?chat_id=\${chatId}&chat_type=\${chatType}` usando `credentials: 'same-origin'`.
   - Si la respuesta es exitosa (`data.status === 'success'`), actualizar `chatHistoryContainer.innerHTML = data.html` y luego hacer scroll hacia el final (`chatHistoryContainer.scrollTop = chatHistoryContainer.scrollHeight`).
   - Debe capturar y manejar cualquier excepción silenciosamente o con un log mínimo para mantener la robustez SPA.

Por favor, devuélveme el código JavaScript COMPLETO modificado dentro de un bloque de código markdown ```javascript ... ``` y nada más.";

$data = [
    'model' => 'qwen2.5-coder:3b',
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'num_ctx' => 12000
    ]
];

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

echo "Enviando solicitud a Qwen...\n";
$response = curl_exec($ch);
if ($response === false) {
    echo "cURL Error: " . curl_error($ch) . "\n";
}
curl_close($ch);

$resData = json_decode((string)$response, true);
$resultText = $resData['response'] ?? '';

if (preg_match('/```javascript\s*(.*?)\s*```/s', $resultText, $matches)) {
    $newCode = $matches[1];
    file_put_contents($filePath, $newCode);
    echo "¡Modificaciones aplicadas correctamente en chat_engine.js!\n";
} else {
    echo "No se encontró el bloque javascript en la respuesta de Qwen. Respuesta completa:\n";
    echo $resultText . "\n";
}
