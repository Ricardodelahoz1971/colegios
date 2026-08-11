<?php
include_once __DIR__ . '/../php/db.php';

$areas_nuevas = [
    'Educación Ética y Valores',
    'Educación Religiosa',
    'Educación Física, Recreación y Deportes',
    'Educación Artística',
    'Tecnología e Informática',
    'Filosofía',
    'Ciencias Políticas y Económicas',
    'Emprendimiento'
];

echo "🚀 Inyectando Áreas Legales Faltantes...\n";

foreach ($areas_nuevas as $nombre) {
    // Verificar si ya existe para evitar duplicados
    $check = $db->prepare("SELECT COUNT(*) FROM areas WHERE nombre_area = ?");
    $check->execute([$nombre]);
    if ($check->fetchColumn() == 0) {
        $ins = $db->prepare("INSERT INTO areas (nombre_area) VALUES (?)");
        $ins->execute([$nombre]);
        echo "✅ Añadida: $nombre\n";
    } else {
        echo "⚠️ Ya existe: $nombre\n";
    }
}

echo "\n📊 Conteo Final de Áreas:\n";
$total = $db->query("SELECT COUNT(*) FROM areas")->fetchColumn();
echo "Total Áreas en Sistema: $total\n";
