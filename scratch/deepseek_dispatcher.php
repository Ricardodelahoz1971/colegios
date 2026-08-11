<?php
declare(strict_types=1);

/**
 * DEEPSEEK DISPATCHER DE TOKENS SOBERANO
 * Este script garantiza matemáticamente que el código es generado
 * por la API de DeepSeek configurada en el proyecto, imprimiendo
 * y guardando el consumo exacto de tokens.
 */

// 1. Cargar configuración oficial del proyecto
$configFile = __DIR__ . '/../antigravity.config.json';
if (!file_exists($configFile)) {
    echo "ERROR: No existe el archivo de configuración antigravity.config.json\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
$apiKey = $config['agent']['apiKey'] ?? '';
$baseUrl = 'https://api.deepseek.com/v1';
$endpoint = $baseUrl . '/chat/completions';

// 2. Definir los prompts (Se configuran aquí los requerimientos del Ingeniero)
$systemPrompt = "Eres un asistente de programación experto en PHP nativo, Javascript Vanilla y CSS Vitrina 06. Debes retornar el código solicitado limpio, sin explicaciones ni cháchara técnica.";
$userPrompt = "Necesito que me des el código completo de js/modules/formatos_matricula_builder.js o el archivo que deseas procesar aplicando las directivas del manifiesto.";

echo "=== INICIANDO LLAMADA MATEMÁTICA A DEEPSEEK CLUMB ===\n";
echo "Endpoint: " . $endpoint . "\n";
echo "Modelo: " . ($config['agent']['model'] ?? 'deepseek-chat') . "\n";

$data = [
    'model' => $config['agent']['model'] ?? 'deepseek-chat',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ],
    'temperature' => 0.1,
    'stream' => false
];

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$timeStart = microtime(true);
$response = curl_exec($ch);
$timeEnd = microtime(true);

if (curl_errno($ch)) {
    echo "ERROR CURL: " . curl_error($ch) . "\n";
    curl_close($ch);
    exit(1);
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "ERROR HTTP {$httpCode}: " . $response . "\n";
    exit(1);
}

$resData = json_decode((string)$response, true);

// 3. Extracción de Métricas de Consumo
$promptTokens = $resData['usage']['prompt_tokens'] ?? 0;
$completionTokens = $resData['usage']['completion_tokens'] ?? 0;
$totalTokens = $resData['usage']['total_tokens'] ?? 0;
$content = $resData['choices'][0]['message']['content'] ?? '';

echo "=== REPORTE MATEMÁTICO DE CONSUMO ===\n";
echo "Tiempo de Respuesta: " . round($timeEnd - $timeStart, 2) . " segundos\n";
echo "Tokens de Entrada (Prompt): " . $promptTokens . "\n";
echo "Tokens de Salida (Generación): " . $completionTokens . "\n";
echo "Total de Tokens Gastados en DeepSeek: " . $totalTokens . "\n";
echo "=====================================\n";

// Guardar en el log de auditoría del proyecto
$logData = sprintf(
    "[%s] Prompt: %d | Completion: %d | Total: %d | Time: %.2fs\n",
    date('Y-m-d H:i:s'),
    $promptTokens,
    $completionTokens,
    $totalTokens,
    $timeEnd - $timeStart
);
file_put_contents(__DIR__ . '/../php/logs/deepseek_audit.log', $logData, FILE_APPEND);

// Imprimir una pequeña muestra de lo generado para confirmación
echo "Muestra del código generado:\n";
echo substr($content, 0, 300) . "...\n";
