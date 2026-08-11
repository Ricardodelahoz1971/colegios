<?php
declare(strict_types=1);
require_once 'c:/xampp/htdocs/sistema_escolar/php/db.php'; // DB is global here

/**
 * Motor de Sincronización Automática de Grupos Élite
 * Este script asegura que cada curso y cada rol tenga su canal de comunicación.
 */

function sincronizarGrupos(PDO $db): bool {
    // --- PREPARAR SENTENCIAS (ESTÁNDAAR ÉLITE) ---
    $stmt_check_grupo = $db->prepare("SELECT id FROM grupos WHERE tipo = ? AND referencia_id = ?");
    $stmt_ins_grupo = $db->prepare("INSERT INTO grupos (nombre, tipo, referencia_id, creado_por) VALUES (?, ?, ?, 1)");
    $stmt_usuarios_rol = $db->prepare("SELECT id FROM usuarios WHERE rol_id = ?");
    $stmt_check_miembro = $db->prepare("SELECT id FROM miembros_grupo WHERE grupo_id = ? AND usuario_id = ?");
    $stmt_ins_miembro = $db->prepare("INSERT INTO miembros_grupo (grupo_id, usuario_id, es_admin) VALUES (?, ?, ?)");

    // --- 1. GRUPOS POR ROL ---
    $stmt_roles_stmt = $db->prepare("SELECT * FROM roles"); $stmt_roles_stmt->execute(); $roles_stmt = $stmt_roles_stmt;
    while ($r = $roles_stmt->fetch(PDO::FETCH_ASSOC)) {
        $nombre_grupo = "[ROL] " . $r['nombre_rol'];
        
        $stmt_check_grupo->execute(['ROL', $r['id']]);
        $grupo_id = $stmt_check_grupo->fetchColumn();
        
        if (!$grupo_id) {
            $stmt_ins_grupo->execute([$nombre_grupo, 'ROL', $r['id']]);
            $grupo_id = $db->lastInsertId();
        }

        $stmt_usuarios_rol->execute([$r['id']]);
        while ($u = $stmt_usuarios_rol->fetch(PDO::FETCH_ASSOC)) {
            $u_id = $u['id'];
            $stmt_check_miembro->execute([$grupo_id, $u_id]);
            if (!$stmt_check_miembro->fetchColumn()) {
                $stmt_ins_miembro->execute([$grupo_id, $u_id, 0]);
            }
        }
    }

    // --- 2. GRUPOS POR CURSO ---
    $stmt_cursos_stmt = $db->prepare("SELECT * FROM cursos"); $stmt_cursos_stmt->execute(); $cursos_stmt = $stmt_cursos_stmt;
    $sql_est_curso = "SELECT u.id FROM usuarios u JOIN estudiantes e ON u.usuario = e.identificacion WHERE e.curso_id = ?";
    $stmt_est_curso = $db->prepare($sql_est_curso);
    $stmt_doc_curso = $db->prepare("SELECT DISTINCT docente_id FROM carga_academica WHERE curso_id = ?");

    while ($c = $cursos_stmt->fetch(PDO::FETCH_ASSOC)) {
        $nombre_grupo = "Salón " . $c['nombre_curso'];
        
        $stmt_check_grupo->execute(['CURSO', $c['id']]);
        $grupo_id = $stmt_check_grupo->fetchColumn();
        
        if (!$grupo_id) {
            $stmt_ins_grupo->execute([$nombre_grupo, 'CURSO', $c['id']]);
            $grupo_id = $db->lastInsertId();
        }

        $ids_a_añadir = [];
        if ($c['tutor_id']) $ids_a_añadir[] = $c['tutor_id'];

        $stmt_est_curso->execute([$c['id']]);
        while($e = $stmt_est_curso->fetch(PDO::FETCH_ASSOC)) $ids_a_añadir[] = $e['id'];

        $stmt_doc_curso->execute([$c['id']]);
        while($d = $stmt_doc_curso->fetch(PDO::FETCH_ASSOC)) $ids_a_añadir[] = $d['docente_id'];

        $ids_a_añadir = array_unique($ids_a_añadir);
        foreach ($ids_a_añadir as $u_id) {
            $stmt_check_miembro->execute([$grupo_id, $u_id]);
            if (!$stmt_check_miembro->fetchColumn()) {
                $stmt_ins_miembro->execute([$grupo_id, $u_id, 0]);
            }
        }
    }

    return true;
}

// Ejecutar sincronización
sincronizarGrupos($db);
echo "Sincronización de grupos completada.";
?>
