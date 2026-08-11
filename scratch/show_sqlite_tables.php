<?php
$db_path = __DIR__ . '/../php/database/usuarios.db';
$sqlite = new PDO("sqlite:$db_path");
$stmt = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $tables);
