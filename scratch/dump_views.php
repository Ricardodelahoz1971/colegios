<?php
$db = new PDO('sqlite:php/database/usuarios.db');
$stmt = $db->query("SELECT sql FROM sqlite_master WHERE type='view'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
