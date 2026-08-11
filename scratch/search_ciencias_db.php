<?php
declare(strict_types=1);
require_once __DIR__ . '/../php/db.php';

try {
    // List all tables
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        // get columns
        $cols = $db->query("PRAGMA table_info($t)")->fetchAll(PDO::FETCH_ASSOC);
        $like_queries = [];
        $params = [];
        foreach ($cols as $col) {
            if (in_array(strtolower($col['type']), ['text', 'varchar'])) {
                $like_queries[] = "{$col['name']} LIKE ?";
                $like_queries[] = "{$col['name']} LIKE ?";
                $params[] = "%ciencias%";
                $params[] = "%educación%";
            }
        }
        if (!empty($like_queries)) {
            $sql = "SELECT COUNT(*) FROM $t WHERE " . implode(" OR ", $like_queries);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            if ($count > 0) {
                echo "Table '$t' has $count matching rows!\n";
                // Let's print some matching rows
                $sql_select = "SELECT * FROM $t WHERE " . implode(" OR ", $like_queries) . " LIMIT 5";
                $stmt_select = $db->prepare($sql_select);
                $stmt_select->execute($params);
                $rows = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    echo "  Row: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "\n";
                }
            }
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
