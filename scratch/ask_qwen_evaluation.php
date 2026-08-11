<?php
declare(strict_types=1);

// Cargar las primeras 100 líneas del archivo de estilos para evaluar cumplimiento
$cssSample = file_get_contents('c:/xampp/htdocs/sistema_escolar/styles/ui_kit.css');
$cssSample = implode("\n", array_slice(explode("\n", $cssSample), 0, 150));

$manifesto = file_get_contents('c:/xampp/htdocs/sistema_escolar/STYLE_MANIFESTO.md');

$prompt = "Eres un evaluador de calidad de código especializado en arquitectura frontend élite. 
A continuación te presento las reglas del manifiesto de estilo (STYLE_MANIFESTO.md) y una muestra de código CSS (ui_kit.css).
Por favor, evalúa si la muestra de código cumple o viola las reglas del manifiesto, identifica fortalezas y señala cualquier desviación o área de mejora técnica.

---
REGLAS DEL MANIFIESTO:
{$manifesto}

---
MUESTRA DE CSS A EVALUAR:
{$cssSample}

---
Por favor, estructura tu evaluación de forma ejecutiva con:
1. Resumen de Cumplimiento (Puntuación de 1 a 10).
2. Fortalezas detectadas (alineación con variables, BEM, propiedades lógicas).
3. Violaciones/Oportunidades de mejora detectadas en la muestra de CSS.
4. Conclusión.";

$data = [
    'model' => 'qwen2.5-coder:3b',
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'num_ctx' => 8192 // Configuración de 8k de contexto recomendada
    ]
];

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode($response, true);
echo $resData['response'] ?? 'No response from local Ollama';
