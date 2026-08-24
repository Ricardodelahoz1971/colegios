<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
// PHP/LOGICA/OBTENER_GEOGRAFIA.PHP - SERVICIO AJAX DE GEOGRAFÍA ÉLITE
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../db.php';

$accion = limpiar_texto_utf8($_GET['accion'] ?? '') ?? '';

try {
    switch ($accion) {
        case 'paises':
            $stmt = $db->prepare("
                SELECT id, codigo_iso, nombre, gentilicio, indicativo 
                FROM cat_paises 
                ORDER BY CASE WHEN id = 1 THEN 0 ELSE 1 END, nombre ASC
            ");
            $stmt->execute();
            $paises = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $paises]);
            break;

        case 'departamentos':
            $pais_id = filter_input(INPUT_GET, 'pais_id', FILTER_VALIDATE_INT) ?: 1;
            $stmt = $db->prepare("
                SELECT id, pais_id, codigo_dane, nombre 
                FROM cat_departamentos 
                WHERE pais_id = ? 
                ORDER BY nombre ASC
            ");
            $stmt->execute([$pais_id]);
            $deptos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $deptos]);
            break;

        case 'municipios':
            $departamento_id = filter_input(INPUT_GET, 'departamento_id', FILTER_VALIDATE_INT) ?: 0;
            $stmt = $db->prepare("
                SELECT id, departamento_id, codigo_dane, nombre 
                FROM cat_municipios 
                WHERE departamento_id = ? 
                ORDER BY nombre ASC
            ");
            $stmt->execute([$departamento_id]);
            $muns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['status' => 'success', 'data' => $muns]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Acción no válida']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error al consultar catálogo geográfico']);
}
