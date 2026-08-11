<?php
$db = new PDO('sqlite:' . __DIR__ . '/../php/database/usuarios.db');
$user = $db->query('SELECT password FROM usuarios WHERE usuario = "LEL"')->fetchColumn();
echo "Hash: $user\n";
$passwords = ["123", "123456", "LEL", "lel", "admin", "password", "12345"];
foreach($passwords as $p) {
    if (password_verify($p, $user)) {
        echo "Found! Password is: $p\n";
        break;
    }
}
