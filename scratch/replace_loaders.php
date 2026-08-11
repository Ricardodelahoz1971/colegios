<?php
declare(strict_types=1);

$files = [
    __DIR__ . '/../php/dashboard.php',
    __DIR__ . '/../php/vistas/estudiante_examenes.php',
    __DIR__ . '/../php/vistas/calificar_pruebas.php',
    __DIR__ . '/../php/vistas/aplicacion_pruebas.php',
    __DIR__ . '/../php/vistas/sabana_calificaciones.php',
    __DIR__ . '/../php/vistas/constructor_actividades.php',
    __DIR__ . '/../php/vistas/aula_virtual_gestion.php',
    __DIR__ . '/../js/modules/chat_engine.js',
    __DIR__ . '/../js/modules/ares_editor.js',
    __DIR__ . '/../js/modules/perseus_engine.js'
];

$replacement = '<div class="loader-ball-elite"><div class="balls-1"></div><div class="balls-2"></div><div class="balls-3"></div><div class="balls-4"></div><div class="balls-5"></div><div class="balls-6"></div><div class="balls-7"></div><div class="balls-8"></div><div class="balls-9"></div></div>';

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }

    echo "Processing file: $file...\n";
    $content = file_get_contents($file);

    $prompt = "You are an automated refactoring script.
I have a file located at '$file'.
I need you to replace all HTML spinner/loader elements (such as `spinner-border`, `<div class=\"spinner-border...`, `<div class=\"escudo-loader...`, or bootstrap spinners) with this exact HTML markup:
```html
$replacement
```

Here is the file content:
---
$content
---

Please output ONLY the complete updated file content. Do not include markdown code block syntax (like ```php or ```javascript) at the beginning or end of your output, do not include explanations, and do not alter anything else in the file. Return the raw code directly.";

    $data = [
        'model' => 'qwen2.5-coder:3b',
        'prompt' => $prompt,
        'stream' => false,
        'options' => [
            'num_ctx' => 8192
        ]
    ];

    $ch = curl_init('http://localhost:11434/api/generate');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);


    if ($status === 200) {
        $resData = json_decode((string)$response, true);
        $result = $resData['response'] ?? '';

        // Clean up markdown wrapping if Qwen still returned it
        $result = preg_replace('/^```[a-zA-Z]*\r?\n/', '', $result);
        $result = preg_replace('/\r?\n```$/', '', $result);
        $result = trim($result);

        if (!empty($result) && strlen($result) > 100) {
            file_put_contents($file, $result);
            echo "Successfully updated $file\n";
        } else {
            echo "Warning: Received empty or too short response for $file\n";
        }
    } else {
        echo "Error: Failed to query Ollama for $file (HTTP $status)\n";
    }
}
echo "Replacement process completed.\n";
