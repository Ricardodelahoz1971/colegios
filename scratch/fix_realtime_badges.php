<?php
declare(strict_types=1);

// 1. Modificar php/logica/check_mensajes.php
$checkMensajesPath = 'c:/xampp/htdocs/sistema_escolar/php/logica/check_mensajes.php';
$checkContent = file_get_contents($checkMensajesPath);
if ($checkContent !== false) {
    // Agregar el conteo por usuario
    $queryAdd = "\n    $unread_direct = (int)$stmt_ud->fetchColumn();\n\n    // Conteo de mensajes sin leer agrupados por remitente (contacto directo)\n    $unread_per_user = [];\n    $stmt_uu = $db->prepare(\"SELECT remitente_id, COUNT(*) as qty FROM mensajes WHERE destinatario_id = ? AND leido = 0 AND grupo_id = 0 GROUP BY remitente_id\");\n    $stmt_uu->execute([$user_id]);\n    while(\$row = \$stmt_uu->fetch(PDO::FETCH_ASSOC)) {\n        \$unread_per_user[(int)\$row['remitente_id']] = (int)\$row['qty'];\n    }\n";
    
    $checkContent = str_replace(
        "\$unread_direct = (int)\$stmt_ud->fetchColumn();",
        $queryAdd,
        $checkContent
    );

    // Agregar unread_per_user al JSON de salida
    $jsonTarget = "'unread_per_group' => \$unread_per_group,";
    $jsonReplacement = "'unread_per_group' => \$unread_per_group,\n        'unread_per_user' => \$unread_per_user,";
    $checkContent = str_replace($jsonTarget, $jsonReplacement, $checkContent);

    file_put_contents($checkMensajesPath, $checkContent);
    echo "check_mensajes.php modificado exitosamente.\n";
}

// 2. Modificar js/modules/hermes_global.js
$hermesPath = 'c:/xampp/htdocs/sistema_escolar/js/modules/hermes_global.js';
$hermesContent = file_get_contents($hermesPath);
if ($hermesContent !== false) {
    // Insertar la lógica de actualización de badges individuales antes del refresco del historial
    $badgeLogic = "        // 1.35 Actualizar Badges de No Leídos Individuales en la Barra Lateral\n" .
                  "        document.querySelectorAll('.contact-item').forEach(item => {\n" .
                  "            const groupId = parseInt(item.getAttribute('data-group-id'));\n" .
                  "            const userId = parseInt(item.getAttribute('data-user-id'));\n" .
                  "            let unreadCount = 0;\n\n" .
                  "            if (groupId) {\n" .
                  "                unreadCount = data.unread_per_group && data.unread_per_group[groupId] ? data.unread_per_group[groupId] : 0;\n" .
                  "            } else if (userId) {\n" .
                  "                unreadCount = data.unread_per_user && data.unread_per_user[userId] ? data.unread_per_user[userId] : 0;\n" .
                  "            }\n\n" .
                  "            const nameContainer = item.querySelector('.elite-chat-contact-name')?.parentNode;\n" .
                  "            if (nameContainer) {\n" .
                  "                let badge = nameContainer.querySelector('.badge-elite--danger');\n" .
                  "                if (unreadCount > 0) {\n" .
                  "                    if (!badge) {\n" .
                  "                        badge = document.createElement('span');\n" .
                  "                        badge.className = 'badge-elite badge-elite--danger';\n" .
                  "                        nameContainer.appendChild(badge);\n" .
                  "                    }\n" .
                  "                    badge.textContent = unreadCount;\n" .
                  "                } else if (badge) {\n" .
                  "                    badge.remove();\n" .
                  "                }\n" .
                  "            }\n" .
                  "        });\n\n" .
                  "        // 1.4 Refresco del Chat";

    $hermesContent = str_replace(
        "// 1.4 Refresco del Chat",
        $badgeLogic,
        $hermesContent
    );

    file_put_contents($hermesPath, $hermesContent);
    echo "hermes_global.js modificado exitosamente.\n";
}
