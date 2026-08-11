<?php
// MIGRACIÓN DE DATOS: SQLite -> MariaDB
$sqlite_path = __DIR__ . '/../php/database/usuarios.db';

$host = 'localhost';
$dbname = 'sistema_escolar';
$user = 'root';
$pass = '';

try {
    $sqlite = new PDO("sqlite:" . $sqlite_path);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $maria = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $maria->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $maria->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Obtener tablas de SQLite
    $stmt = $sqlite->query("SELECT name FROM sqlite_master WHERE type='table' AND name != 'sqlite_sequence'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        echo "Migrando tabla: $table... ";
        
        // Vaciar la tabla en MariaDB
        try {
            $maria->exec("TRUNCATE TABLE `$table`");
        } catch (Exception $e) {
            // Si la tabla no existe o algo, ignoramos
        }

        // Obtener datos de SQLite
        $rows = $sqlite->query("SELECT * FROM `$table`")->fetchAll();
        
        if (empty($rows)) {
            echo "0 registros (vacia).\n";
            continue;
        }

        // Preparar INSERT
        $columns = array_keys($rows[0]);
        $colsStr = implode('`, `', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $insertStmt = $maria->prepare("INSERT INTO `$table` (`$colsStr`) VALUES ($placeholders)");

        $count = 0;
        foreach ($rows as $row) {
            // Ajustar valores para compatibilidad
            $values = array_values($row);
            try {
                $insertStmt->execute($values);
                $count++;
            } catch (Exception $e) {
                // Ignorar errores puntuales de constraint para continuar el volcado
                echo "[Error en fila: " . $e->getMessage() . "] ";
            }
        }
        echo "$count registros migrados.\n";
    }

    $maria->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\nVOLCADO DE DATOS FINALIZADO CON ÉXITO.\n";

} catch (Exception $e) {
    echo "ERROR GRAVE: " . $e->getMessage() . "\n";
}
?>
