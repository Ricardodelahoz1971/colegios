<?php
declare(strict_types=1);
/**
 * 🩺 MOTOR DE TELEMETRÍA ÉLITE v1.0
 * Realiza un diagnóstico proactivo del estado real del servidor y la base de datos.
 */

class SistemaSalud {
    public static function obtenerDiagnostico(PDO $db): array {
        $alertas = [];
        $puntuacion = 100;
        $detalles = [];

        // 1. TEST DE LATENCIA DE BASE DE DATOS
        $start = microtime(true);
        try {
            $stmt_health = $db->prepare("SELECT 1");
            $stmt_health->execute();
            $latency = (microtime(true) - $start) * 1000; // ms
            if ($latency > 50) {
                $alertas[] = "Latencia de DB elevada: " . round($latency, 2) . "ms";
                $puntuacion -= 20;
            }
            $detalles['latencia'] = round($latency, 2) . "ms";
        } catch (Exception $e) {
            $alertas[] = "Error de conexión a DB";
            $puntuacion = 0;
        }

        // 2. VERIFICACIÓN DE PERMISOS DE ESCRITURA
        $directorios = [
            '../../uploads' => 'Cargas de Archivos',
            '../../temp' => 'Archivos Temporales'
        ];

        foreach ($directorios as $path => $nombre) {
            $abs_path = realpath(__DIR__ . DIRECTORY_SEPARATOR . $path);
            if (!$abs_path) {
                $abs_path = __DIR__ . DIRECTORY_SEPARATOR . $path;
            }
            if (!is_dir($abs_path)) {
                @mkdir($abs_path, 0777, true);
            }
            if (!is_writable($abs_path)) {
                $alertas[] = "Sin acceso de escritura en: $nombre";
                $puntuacion -= 15;
            }
        }

        // 3. ESTADO DEL MOTOR PHP & EXTENSIONES
        $extensiones_criticas = ['pdo_mysql', 'gd', 'mbstring'];
        foreach ($extensiones_criticas as $ext) {
            if (!extension_loaded($ext)) {
                $alertas[] = "Extensión faltante: $ext";
                $puntuacion -= 10;
            }
        }

        // 4. VERIFICACIÓN DE ESPACIO (Simulado para XAMPP si no hay cuotas)
        $free_space = disk_free_space(".");
        if ($free_space < 100 * 1024 * 1024) { // Menos de 100MB
            $alertas[] = "Espacio en disco críticamente bajo";
            $puntuacion -= 30;
        }

        // 5. DETERMINAR ESTADO GLOBAL
        $estado = "ÓPTIMO";
        $color_class = "text-success";
        $pulsar_class = "pulsar-primary-elite";

        if ($puntuacion <= 0) {
            $estado = "CRÍTICO";
            $color_class = "text-danger";
            $pulsar_class = "text-danger";
        } elseif ($puntuacion < 70) {
            $estado = "DEGRADADO";
            $color_class = "text-warning";
            $pulsar_class = "text-warning";
        } elseif ($puntuacion < 95) {
            $estado = "ADVERTENCIA";
            $color_class = "text-info";
            $pulsar_class = "text-info";
        }

        return [
            'puntuacion' => $puntuacion,
            'estado' => $estado,
            'color' => $color_class,
            'pulsar' => $pulsar_class,
            'alertas' => $alertas,
            'detalles' => $detalles,
            'php_version' => PHP_VERSION,
            'db_size' => self::getDbSize($db)
        ];
    }

    private static function getDbSize(PDO $db): string {
        try {
            $stmt = $db->prepare("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = :schema");
            $stmt->execute([':schema' => 'sistema_escolar']);
            $size = (float)$stmt->fetchColumn();
            if ($size > 0) {
                $units = array('B', 'KB', 'MB', 'GB', 'TB');
                for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
                return round($size, 2) . ' ' . $units[$i];
            }
        } catch (Exception $e) {}
        return "N/A";
    }
}
