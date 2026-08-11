<?php
declare(strict_types=1);

/**
 * DEEPSEEK CODEGEN FOR MM PURE CONVERSION AND AUTO-PAGINATION
 */

$configFile = __DIR__ . '/../antigravity.config.json';
if (!file_exists($configFile)) {
    echo "ERROR: No existe el archivo de configuración antigravity.config.json\n";
    exit(1);
}

$config = json_decode(file_get_contents($configFile), true);
$apiKey = $config['agent']['apiKey'] ?? '';
$baseUrl = 'https://api.deepseek.com/v1';
$endpoint = $baseUrl . '/chat/completions';

$systemPrompt = "Actúa como un programador de software senior experto en PHP, CSS y maquetación de impresión.
Debes devolver las modificaciones completas de imprimir_matricula.php e imprimir_matricula.css para realizar la auto-paginación en mm, sin usar px ni conversiones flotantes en js.";

$userPrompt = "Analiza e implementa la pureza de milímetros (mm) en imprimir_matricula.php y la auto-paginación dinámica:

1. Lee C:/xampp/htdocs/sistema_escolar/imprimir_matricula.php. Reemplaza el bloque script final de renderizado de posiciones por un cálculo nativo en mm:
   - Lee data-left y data-top directamente en mm (convirtiendo los píxeles del lienzo del builder a mm en PHP con la relación exacta 1px = 0.264583mm antes de inyectar los divs en el HTML).
   - Genera directamente style=\"left: [X]mm; top: [Y]mm; width: [W]mm;\" en los divs bloque-avanzado y bloque-texto desde el backend PHP en lugar de usar el script JS final.
   
2. Implementa el algoritmo de auto-paginación en PHP:
   - Altura útil de página Carta: 279.4mm.
   - Restar margen superior e inferior en mm.
   - Agrupar los bloques de cabecera ('logo', 'titulo_colegio', 'lema_colegio', 'metadatos').
   - Iterar sobre todos los bloques ordenados por su coordenada Y (top) en mm.
   - Si la coordenada Y de un bloque supera la altura útil de la página actual, abrir una nueva página física (.print-container), insertar automáticamente los bloques de cabecera al inicio de la nueva página a la altura base (ej: top: 10mm), y ajustar el top del bloque actual para que continúe en el flujo útil de la página 2.
   
3. Eliminar !important del CSS imprimir_matricula.css y asegurar que las páginas .print-container tengan:
   ```css
   .print-container {
       width: 215.9mm;
       height: 279.4mm;
       margin: 10mm auto;
       padding: 0;
       position: relative;
       page-break-after: always;
       break-after: page;
   }
   @media print {
       .print-container {
           margin: 0;
           border: none;
       }
   }
   ```
   
Devuelve el código resultante para aplicar.";

$data = [
    'model' => 'deepseek-chat',
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "ERROR HTTP {$httpCode}: " . $response . "\n";
    exit(1);
}

$resData = json_decode((string)$response, true);
$content = $resData['choices'][0]['message']['content'] ?? '';

file_put_contents(__DIR__ . '/deepseek_mm_result.txt', $content);
echo "OK: Código generado en scratch/deepseek_mm_result.txt\n";
