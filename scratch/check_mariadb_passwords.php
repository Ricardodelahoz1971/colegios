<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

$users_to_test = ['admin', 'LRR', 'rector', 'CPP', 'LJP', '999999999'];
$possible_passwords = [
    '123456', '1234', '123', 'admin', 'password', 'escolar', '12345',
    'LRR', 'lrr', 'CPP', 'cpp', 'rector', '999999999', 'perseus', 'perseus123',
    'admin123', 'docente', 'coordinador', 'estudiante'
];

foreach ($users_to_test as $usr) {
    $stmt = $db->prepare("SELECT password, nombre FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usr]);
    $row = $stmt->fetch();
    if ($row) {
        $hash = $row['password'];
        $nombre = $row['nombre'];
        $found = false;
        foreach ($possible_passwords as $pwd) {
            if (password_verify($pwd, $hash)) {
                echo "User '{$usr}' ({$nombre}) matches password: '{$pwd}'\n";
                $found = true;
                break;
            }
        }
        if (!$found) {
            echo "User '{$usr}' ({$nombre}) hash: {$hash} (No basic password matched)\n";
        }
    } else {
        echo "User '{$usr}' not found in database.\n";
    }
}
