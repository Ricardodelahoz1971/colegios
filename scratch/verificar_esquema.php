<?php
include_once __DIR__ . '/../php/db.php';
$res = $db->query("PRAGMA table_info(ares_catalogo_evidencias)")->fetchAll(PDO::FETCH_ASSOC);
echo "📋 ESQUEMA DE 'ares_catalogo_evidencias':\n";
print_r($res);
