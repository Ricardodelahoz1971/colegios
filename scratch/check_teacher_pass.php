<?php
require_once 'php/db.php';
try {
    $stmt = $db->prepare("SELECT usuario, password FROM usuarios WHERE usuario IN ('LJP', 'LMA', 'LEM', 'LAA', 'LRR', 'LCR')");
    $stmt->execute();
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
        foreach([$u['usuario'], strtolower($u['usuario']), '123456', '12345', 'password', 'escolar'] as $p) {
            if(password_verify($p, $u['password'])) {
                echo "User: " . $u['usuario'] . " | Pass: " . $p . "\n";
                break;
            }
        }
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
