<?php
require 'c:/xampp/htdocs/sistema_escolar/php/db.php';
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
