<?php
declare(strict_types=1);

$prompt = "Tengo una campana de notificaciones de mensajes que es un SVG en HTML:
```html
<div class=\"dropdown no-print\" id=\"campanaNotificaciones\">
    <a href=\"#\" class=\"text-dark position-relative p-1 d-block\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">
        <svg width=\"24\" height=\"24\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9\"></path><path d=\"M13.73 21a2 2 0 0 1-3.46 0\"></path></svg>
        <span id=\"punto-rojo-mensajes\" class=\"position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle d-none notif-badge-offset pulse-notif-elite\"></span>
    </a>
```

Necesito cumplir con estos requerimientos:
1. Eliminar el punto rojo (`#punto-rojo-mensajes`).
2. Diseñar una animación CSS premium de tipo balanceo de campana (ringing/swinging effect) con transiciones suaves en CSS y guardarla.
3. En el archivo JS (`js/modules/hermes_global.js`), en la función `verificarMensajes`, si `data.unread_count > 0`, activar la animación en el SVG. De lo contrario, detener la animación.
4. Para la animación en CSS, el efecto debe ser sutil y elegante, usando transiciones `cubic-bezier(0.4, 0, 0.2, 1)` (efecto seda del manifiesto).

Dime la animación CSS recomendada (keyframes y clase) y cómo integrarla en `styles/ui_kit.css` y `js/modules/hermes_global.js`. Devuélvelo estructurado de forma concisa.";

$data = [
    'model' => 'qwen2.5-coder:3b',
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'num_ctx' => 4096
    ]
];

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);


$resData = json_decode((string)$response, true);
echo $resData['response'] ?? 'No response';
