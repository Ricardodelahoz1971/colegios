<?php
declare(strict_types=1);

$prompt = "Tengo un error al migrar de SQLite a MySQL/MariaDB: 'SQLSTATE[HY000]: General error: 1273 Unknown collation: NOCASE'.
El error ocurre en las siguientes consultas preparadas de PHP:
1. \$check = \$db->prepare('SELECT COUNT(*) FROM areas WHERE nombre_area = :nom COLLATE NOCASE');
2. \$check = \$db->prepare('SELECT COUNT(*) FROM especialidades WHERE nombre_especialidad = :nom AND id != :id COLLATE NOCASE');

¿Cómo debo modificar estas consultas para que funcionen correctamente en MySQL/MariaDB manteniendo la insensibilidad a mayúsculas y minúsculas? Dame solo las líneas modificadas de código PHP y una breve explicación.";

$data = [
    'model' => 'qwen2.5-coder:3b',
    'prompt' => $prompt,
    'stream' => false
];

$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

$resData = json_decode($response, true);
echo $resData['response'] ?? 'No response';
