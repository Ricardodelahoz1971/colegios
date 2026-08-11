<?php
declare(strict_types=1);
// PHP/VISTAS/PRESENTAR_EXAMEN.PHP - EL BÚNKER (ARES) v1.1
session_start();
require_once '../db.php';
require_once '../helpers_elite.php';

// Validar ticket criptográfico
$token = $_GET['token'] ?? '';
if (!isset($_SESSION['ticket_examen']) || $_SESSION['ticket_examen'] !== $token) {
    die("Acceso Denegado. Ticket Inválido.");
}

$mi_id = (int)$_SESSION['usuario_id'];
$asig_id = (int)$_SESSION['ticket_asignacion_id'];

// BLINDAJE: Verificar si el alumno ya entregó este examen
$stmt_check = $db->prepare("SELECT id FROM eval_respuestas WHERE asignacion_id = ? AND estudiante_id = (SELECT id FROM estudiantes WHERE curso_id > 0 AND id = (SELECT estudiante_id FROM usuarios WHERE id = ?))");
$stmt_check->execute([$asig_id, $mi_id]);
if ($stmt_check->fetch()) {
    die("<div class='card-elite p-5 text-center'>
            <h1 class='text-danger'>ACCESO RESTRINGIDO</h1>
            <p class='subtitle-elite'>Usted ya ha entregado y sellado esta prueba. No es posible reingresar al búnker.</p>
            <div class='mt-4'>
                <a href='../dashboard.php' class='btn-elite px-5'>VOLVER AL PANEL</a>
            </div>
         </div>");
}

// Obtener datos maestros de la asignación y la prueba
$stmt = $db->prepare("SELECT a.*, p.titulo, p.instrucciones, p.tiempo_limite, e.nombre_especialidad as materia
                      FROM eval_asignaciones a
                      JOIN eval_pruebas p ON a.prueba_id = p.id
                      JOIN especialidades e ON p.materia_id = e.id
                      WHERE a.id = ?");
$stmt->execute([$asig_id]);
$examen = $stmt->fetch();

$ahora = new DateTime();
$inicio = new DateTime($examen['fecha_inicio']);
$fin = new DateTime($examen['fecha_fin']);

