<?php
declare(strict_types=1);
require_once __DIR__ . '/../security.php';
guardia_sesion();
    session_write_close();
// PHP/LOGICA/ZULU_ADMIN_PLAN.PHP - MOTOR DE PLAN MAESTRO (BLINDADO)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../db.php';
require_once '../auth.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id']) || !tienen_rol(['administrador', 'coordinador', 'rector', 1, 2, 3])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado: Privilegios insuficientes.']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

// GET: Cargar materias de un nivel
if ($method === 'GET') {
    $nivel = $_GET['nivel'] ?? '';
    if (!$nivel) {
        echo json_encode([]);
        exit;
    }
    
    $stmt = $db->prepare("SELECT especialidad_id, intensidad_horaria FROM zulu_plan_maestro WHERE nivel_nombre = :nivel");
    $stmt->bindValue(':nivel', $nivel, PDO::PARAM_STR);
    $stmt->execute();
    
    $materias = [];
    while($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $materias[] = [
            'id' => (int)$r['especialidad_id'],
            'h' => (int)$r['intensidad_horaria']
        ];
    }
    echo json_encode($materias);
    exit;
}

// POST: Guardar materias de un nivel
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nivel = $data['nivel'] ?? ''; // El ID del nivel
    $materias = $data['materias'] ?? []; // Array de {id, h}
    
    if (!$nivel) {
        echo json_encode(['success' => false, 'message' => 'Nivel no especificado']);
        exit;
    }
    
    $db->beginTransaction();
    try {
        // 1. Borrar plan anterior
        $stmt_del = $db->prepare("DELETE FROM zulu_plan_maestro WHERE nivel_nombre = :nivel");
        $stmt_del->bindValue(':nivel', $nivel, PDO::PARAM_STR);
        $stmt_del->execute();
        
        // 2. Insertar nuevo plan con intensidad
        $stmt_ins = $db->prepare("INSERT INTO zulu_plan_maestro (nivel_nombre, especialidad_id, intensidad_horaria) VALUES (:nivel, :m_id, :h)");
        $ids_permitidos = [];
        foreach ($materias as $m) {
            $stmt_ins->bindValue(':nivel', $nivel, PDO::PARAM_STR);
            $stmt_ins->bindValue(':m_id', (int)$m['id'], PDO::PARAM_INT);
            $stmt_ins->bindValue(':h', (int)$m['h'], PDO::PARAM_INT);
            $stmt_ins->execute();
            $ids_permitidos[] = (int)$m['id'];
        }

        // 3. SINCRONIZACIÓN ATLAS: Limpiar carga académica que ya no esté en el plan
        if (!empty($ids_permitidos)) {
            $in_query = implode(',', $ids_permitidos);
            $sql_cleanup = "DELETE FROM carga_academica 
                            WHERE curso_id IN (SELECT id FROM cursos WHERE nivel_id = :nivel_id)
                            AND especialidad_id NOT IN ($in_query)";
        } else {
            $sql_cleanup = "DELETE FROM carga_academica WHERE curso_id IN (SELECT id FROM cursos WHERE nivel_id = :nivel_id)";
        }
        
        $stmt_cleanup = $db->prepare($sql_cleanup);
        $stmt_cleanup->bindValue(':nivel_id', (int)$nivel, PDO::PARAM_INT);
        $stmt_cleanup->execute();

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>

