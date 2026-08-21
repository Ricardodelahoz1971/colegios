<?php
declare(strict_types=1);

/**
 * 🛡️ MOTOR DE PURGA RADICAL DEEPSEEK
 * Automatiza la extirpación de deuda técnica en $_POST usando DeepSeek API.
 */



// Si existe un .env, cargar clave
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2) + [NULL, NULL];
        if ($name === 'DEEPSEEK_API_KEY') {
            putenv("DEEPSEEK_API_KEY=" . trim($value));
        }
    }
}

$apiKey = getenv('DEEPSEEK_API_KEY');
if (!$apiKey) {
    echo "❌ ERROR: DEEPSEEK_API_KEY no configurado en .env.\nAgregue DEEPSEEK_API_KEY=su_clave al archivo .env\n";
    exit(1);
}

$directorios_escaneo = [__DIR__ . '/php/logica', __DIR__ . '/php/vistas', __DIR__ . '/php'];
$archivos_infectados = [];

// Escanear recursivamente archivos PHP con $_POST vulnerable
foreach ($directorios_escaneo as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $lines = file($file->getPathname());
            foreach ($lines as $line) {
                if (strpos($line, '$_POST') !== false && strpos($line, 'prepare') === false && strpos($line, 'filter_input') === false) {
                    $archivos_infectados[] = $file->getPathname();
                    break;
                }
            }
        }
    }
}
$archivos_infectados = array_unique($archivos_infectados);

echo "🚨 Encontrados " . count($archivos_infectados) . " archivos infectados.\n";

foreach ($archivos_infectados as $archivo) {
    echo "🔄 Purgando: " . basename($archivo) . "\n";
    $codigo = file_get_contents($archivo);
    
    $prompt = "Eres el Auditor Maestro DeepSeek. Refactoriza el siguiente código PHP para eliminar cualquier uso directo de \$_POST. Usa sentencias preparadas de PDO estrictamente y filter_input para sanitizar si es necesario. Devuelve ÚNICAMENTE el código PHP limpio sin markdown y sin explicaciones.\n\n" . $codigo;
    
    $payload = [
        "model" => "deepseek-chat",
        "messages" => [
            ["role" => "system", "content" => "Eres un programador experto y estricto. Solo devuelves código PHP limpio."],
            ["role" => "user", "content" => $prompt]
        ],
        "temperature" => 0.0
    ];
    
    $ch = curl_init('https://api.deepseek.com/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $codigo_limpio = $data['choices'][0]['message']['content'] ?? '';
        if (!empty($codigo_limpio)) {
            $codigo_limpio = str_replace(['```php', '```'], '', $codigo_limpio);
            file_put_contents($archivo, trim($codigo_limpio));
            echo "✅ " . basename($archivo) . " purgado con éxito.\n";
        }
    } else {
        echo "❌ Fallo en la API DeepSeek para " . basename($archivo) . "\n";
    }
}

echo "🏆 PURGA RADICAL FINALIZADA.\n";
