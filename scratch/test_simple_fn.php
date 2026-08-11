<?php

declare(strict_types=1);

/**
 * Función de prueba simple para el Ingeniero Ricardo.
 * Calcula la suma de dos números enteros.
 *
 * @param int $firstNumber Primer sumando
 * @param int $secondNumber Segundo sumando
 * @return array{status: string, data: array{result: int}, message: string}
 */
function testSum(int $firstNumber, int $secondNumber): array
{
    $sum = $firstNumber + $secondNumber;

    return [
        'status' => 'success',
        'data' => [
            'result' => $sum
        ],
        'message' => 'Suma calculada exitosamente.'
    ];
}

// Para ejecutar y verificar la función localmente:
$response = testSum(15, 29);
header('Content-Type: application/json; charset=utf-8');
echo json_encode($response, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
