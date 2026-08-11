<?php
declare(strict_types=1);

require_once __DIR__ . '/../php/db.php';

// List of some users from DB
$users_to_test = ['LEL', 'LEV', 'LRP', 'ADMIN'];

foreach ($users_to_test as $usr) {
    $stmt = $db->prepare("SELECT password FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usr]);
    $hash = $stmt->fetchColumn();
    if ($hash) {
        $possible_passwords = [$usr, strtolower($usr), '123456', '12345', 'password', 'escolar'];
        foreach ($possible_passwords as $pwd) {
            if (password_verify($pwd, $hash)) {
                echo "User {$usr} matches password: '{$pwd}'\n";
                break;
            }
        }
    }
}
