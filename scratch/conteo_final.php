<?php
include_once __DIR__ . '/../php/db.php';
$total = $db->query("SELECT COUNT(*) FROM ares_catalogo_aprendizajes")->fetchColumn();
echo "🏆 TOTAL DE ESTÁNDARES (DBA + LINEAMIENTOS): $total\n";
