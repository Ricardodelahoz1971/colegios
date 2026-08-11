<?php
$db = new PDO('sqlite:php/database/usuarios.db');
$db->exec("PRAGMA user_version = 0");
echo "Reset user_version to: " . $db->query("PRAGMA user_version")->fetchColumn() . "\n";
