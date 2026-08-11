<?php
declare(strict_types=1);

$filePath = 'c:/xampp/htdocs/sistema_escolar/php/vistas/mensajeria.php';
$content = file_get_contents($filePath);

$prompt = "Tengo el siguiente código PHP en `php/vistas/mensajeria.php`:
```php
{$content}
```

Necesito modificarlo para solucionar dos errores PDOException SQLSTATE[HY093] (Invalid parameter number) causados por reutilizar el mismo parámetro nominal en consultas preparadas cuando la emulación de preparaciones de PDO está desactivada.

Por favor, realiza las siguientes correcciones:
1. En la línea 35-39:
   - Modifica la consulta preparatoria \$stmt_es_mi_profe para cambiar los parámetros reutilizados `:cid` a `:cid1` y `:cid2`.
   - Modifica las vinculaciones de parámetros correspondientes (aproximadamente en la línea 74-76) para vincular `:cid1` y `:cid2` individualmente con \$mi_curso_id.

2. En la línea 57-60:
   - Modifica la consulta preparatoria \$stmt_is_mine para cambiar los parámetros reutilizados `:tid` a `:tid1` y `:tid2`.
   - Modifica las vinculaciones correspondientes (aproximadamente en la línea 61-63) para vincular `:tid1` y `:tid2` individualmente con \$user_id.

Por favor, devuélveme el código PHP completo modificado dentro de un bloque markdown ```php ... ``` y nada más.";

$data = [
    'model' => 'qwen2.5-coder:3b',
    'prompt' => $prompt,
    'stream' => false,
    'options' => [
        'num_ctx' => 16000
    ]
];

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

echo "Enviando solicitud a Qwen...\n";
$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode((string)$response, true);
$resultText = $resData['response'] ?? '';

if (preg_match('/```php\s*(.*?)\s*```/s', $resultText, $matches)) {
    $newCode = $matches[1];
    file_put_contents($filePath, $newCode);
    echo "¡Modificaciones aplicadas correctamente en php/vistas/mensajeria.php!\n";
} else {
    echo "No se encontró el bloque php en la respuesta de Qwen. Respuesta completa:\n";
    echo $resultText . "\n";
}
