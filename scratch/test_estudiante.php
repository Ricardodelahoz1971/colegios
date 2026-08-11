<?php
require_once __DIR__ . '/../php/db.php';

session_start();
// Simulate dashboard.php session healing logic
$_SESSION['usuario_id'] = 9;
$_SESSION['rol_id'] = 5;
$_SESSION['rol_nombre'] = 'Estudiante';

// Healing logic from dashboard.php
if (!isset($_SESSION['rol_id']) || empty($_SESSION['rol_id']) || (!isset($_SESSION['estudiante_id']) && in_array($_SESSION['rol_id'] ?? 0, [5, 12]))) {
    $stmt_seg = $db->prepare("SELECT rol_id, estudiante_id FROM usuarios WHERE id = ?");
    $stmt_seg->execute([$_SESSION['usuario_id']]);
    $user_db = $stmt_seg->fetch(PDO::FETCH_ASSOC);
    
    if ($user_db) {
        $_SESSION['rol_id'] = $user_db['rol_id'];
        if ($user_db['rol_id'] == 12 || $user_db['rol_id'] == 5) {
            $_SESSION['estudiante_id'] = $user_db['estudiante_id'];
        }
    }
}

echo "Session After Healing:\n";
print_r($_SESSION);

// Now simulate obtener_agenda.php logic
$estudiante_id = $_SESSION['estudiante_id'] ?? 0;
$stmt_est = $db->prepare("SELECT curso_id FROM estudiantes WHERE id = :eid");
$stmt_est->bindValue(':eid', $estudiante_id, PDO::PARAM_INT);
$stmt_est->execute();
$est_data = $stmt_est->fetch(PDO::FETCH_ASSOC);

if (!$est_data) {
    echo "ERROR: Perfil de estudiante no encontrado.\n";
} else {
    echo "SUCCESS: Curso ID = " . $est_data['curso_id'] . "\n";
}
