<?php
/**
 * Sembrador de Catálogo DBA MEN Completo
 * 
 * Script para procesar archivos de lineamientos DBA y poblar las tablas:
 * - ares_catalogo_competencias
 * - ares_catalogo_aprendizajes
 * - ares_catalogo_evidencias
 * 
 * Ejecución: php database/migraciones/sembrar_dba_men_completo.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../../php/db.php';

/**
 * Clase principal para la siembra del catálogo DBA
 */
class SembradorDBA
{
    private PDO $conexion;
    private string $baseDir;
    private array $conteos = [
        'competencias_existentes' => 0,
        'competencias_nuevas' => 0,
        'aprendizajes_existentes' => 0,
        'aprendizajes_nuevos' => 0,
        'evidencias_eliminadas' => 0,
        'evidencias_insertadas' => 0,
        'archivos_procesados' => 0,
        'errores' => 0,
    ];

    /**
     * Mapa de archivos y sus correspondientes áreas
     */
    private const ARCHIVOS_AREAS = [
        [
            'archivo' => 'DBA_Matematicas_Limpio.md',
            'area_id' => 1,
            'nombre_area' => 'Matemáticas',
            'descripcion' => 'Competencias y directivas del área de Matemáticas',
        ],
        [
            'archivo' => 'DBA_Ciencias_Naturales_Limpio.md',
            'area_id' => 2,
            'nombre_area' => 'Ciencias Naturales',
            'descripcion' => 'Competencias y directivas del área de Ciencias Naturales y Educación Ambiental',
        ],
        [
            'archivo' => 'DBA_Sociales_Limpio.md',
            'area_id' => 3,
            'nombre_area' => 'Ciencias Sociales',
            'descripcion' => 'Competencias y directivas del área de Ciencias Sociales, Historia y Geografía',
        ],
        [
            'archivo' => 'DBA_Lenguaje_Limpio.md',
            'area_id' => 8,
            'nombre_area' => 'Lengua Castellana',
            'descripcion' => 'Competencias y directivas del área de Humanidades y Lengua Castellana',
        ],
    ];

    public function __construct(PDO $db)
    {
        $this->conexion = $db;
        $this->baseDir = dirname(__DIR__, 2);
    }

    public function ejecutar(): void
    {
        $tiempoInicio = microtime(true);

        echo "===============================================\n";
        echo "   SEMBRADOR DE CATÁLOGO DBA MEN COMPLETO\n";
        echo "===============================================\n";
        echo "Inicio: " . date('Y-m-d H:i:s') . "\n\n";

        $this->conexion->beginTransaction();

        try {
            foreach (self::ARCHIVOS_AREAS as $areaData) {
                $this->procesarArchivo($areaData);
            }

            $this->conexion->commit();
            $this->imprimirResumen($tiempoInicio);
        } catch (Exception $e) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            $this->conteos['errores']++;
            echo "\n❌ ERROR: " . $e->getMessage() . "\n";
            echo "   En: " . $e->getFile() . ":" . $e->getLine() . "\n";
            echo "Se realizó rollback de la transacción.\n";
            exit(1);
        }
    }

    private function procesarArchivo(array $areaData): void
    {
        $nombreArchivo = $areaData['archivo'];
        $areaId = (int)$areaData['area_id'];
        $rutaArchivo = $this->baseDir . '/lineamientos/' . $nombreArchivo;

        echo "📂 Procesando: {$nombreArchivo} (Área ID: {$areaId})\n";

        if (!file_exists($rutaArchivo)) {
            throw new Exception("El archivo no existe: {$rutaArchivo}");
        }

        $competenciaId = $this->obtenerOCrearCompetencia(
            $areaId,
            $areaData['nombre_area'],
            $areaData['descripcion']
        );

        $contenido = file_get_contents($rutaArchivo);
        if ($contenido === false) {
            throw new Exception("No se pudo leer el archivo: {$rutaArchivo}");
        }

        $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
        $lineas = explode("\n", $contenido);

        $gradoActual = null;
        $numDbaActual = null;
        $enunciadoActual = null;
        $evidenciasActuales = [];
        $enModoEvidencias = false;
        $enModoEnunciado = false;

        foreach ($lineas as $linea) {
            $lineaTrim = trim($linea);

            if ($this->esLineaGrado($lineaTrim) || $this->esLineaDBA($lineaTrim)) {
                $this->procesarDBA(
                    $competenciaId,
                    $areaId,
                    $gradoActual,
                    $numDbaActual,
                    $enunciadoActual,
                    $evidenciasActuales
                );

                $enunciadoActual = null;
                $evidenciasActuales = [];
                $enModoEvidencias = false;
                $enModoEnunciado = false;
            }

            if ($this->esLineaGrado($lineaTrim)) {
                $gradoActual = $this->extraerGrado($lineaTrim);
                $numDbaActual = null;
                continue;
            }

            if ($this->esLineaDBA($lineaTrim)) {
                $numDbaActual = $this->extraerNumDBA($lineaTrim);
                $enModoEnunciado = true;
                continue;
            }

            if ($enModoEnunciado && str_starts_with($lineaTrim, '**Enunciado:**')) {
                $enunciadoActual = trim(substr($lineaTrim, strlen('**Enunciado:**')));
                continue;
            }

            if ($enModoEnunciado && !$enModoEvidencias && $enunciadoActual !== null && !empty($lineaTrim)) {
                if (str_starts_with($lineaTrim, '**Evidencias de Aprendizaje:**') || str_starts_with($lineaTrim, 'Evidencias de Aprendizaje:')) {
                    $enModoEvidencias = true;
                    $enModoEnunciado = false;
                } elseif (!$this->esLineaGrado($lineaTrim) && !$this->esLineaDBA($lineaTrim) && !str_starts_with($lineaTrim, '---')) {
                    $enunciadoActual .= ' ' . $lineaTrim;
                }
                continue;
            }

            if ($enunciadoActual !== null && (str_starts_with($lineaTrim, '**Evidencias de Aprendizaje:**') || str_starts_with($lineaTrim, 'Evidencias de Aprendizaje:'))) {
                $enModoEvidencias = true;
                continue;
            }

            if ($enModoEvidencias && (str_starts_with($lineaTrim, '- ') || str_starts_with($lineaTrim, '• '))) {
                $evidenciaTexto = trim(substr($lineaTrim, 2));
                if (!empty($evidenciaTexto)) {
                    $evidenciasActuales[] = $evidenciaTexto;
                }
                continue;
            }
        }

        $this->procesarDBA(
            $competenciaId,
            $areaId,
            $gradoActual,
            $numDbaActual,
            $enunciadoActual,
            $evidenciasActuales
        );

        $this->conteos['archivos_procesados']++;
        echo "  ✓ {$areaData['nombre_area']} procesada con éxito.\n\n";
    }

    private function procesarDBA(
        int $competenciaId,
        int $areaId,
        ?string $grado,
        ?int $numDba,
        ?string $enunciado,
        array $evidencias
    ): void {
        if ($grado === null || $numDba === null || $enunciado === null || trim($enunciado) === '') {
            return;
        }

        $enunciadoLimpio = trim($enunciado);

        $aprendizajeId = $this->obtenerOCrearAprendizaje(
            $competenciaId,
            $areaId,
            $grado,
            $numDba,
            $enunciadoLimpio
        );

        $this->procesarEvidencias($aprendizajeId, $evidencias);
    }

    private function obtenerOCrearCompetencia(int $areaId, string $nombre, string $descripcion): int
    {
        $sql = "SELECT id FROM ares_catalogo_competencias WHERE area_id = :area_id LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':area_id' => $areaId]);
        $resultado = $stmt->fetch();

        if ($resultado) {
            $this->conteos['competencias_existentes']++;
            return (int)$resultado['id'];
        }

        $sql = "INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) 
                VALUES (:area_id, :nombre, :descripcion)";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':area_id' => $areaId,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
        ]);

        $this->conteos['competencias_nuevas']++;
        return (int)$this->conexion->lastInsertId();
    }

    private function obtenerOCrearAprendizaje(
        int $competenciaId,
        int $areaId,
        string $grado,
        int $numDba,
        string $enunciado
    ): int {
        $sql = "SELECT id FROM ares_catalogo_aprendizajes 
                WHERE area_id = :area_id AND grado = :grado AND num_dba = :num_dba 
                LIMIT 1";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':area_id' => $areaId,
            ':grado' => $grado,
            ':num_dba' => $numDba,
        ]);
        $resultado = $stmt->fetch();

        if ($resultado) {
            $sql = "UPDATE ares_catalogo_aprendizajes 
                    SET enunciado = :enunciado, 
                        competencia_id = :competencia_id, 
                        version = 'V.1', 
                        disciplina = 'general' 
                    WHERE id = :id";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':enunciado' => $enunciado,
                ':competencia_id' => $competenciaId,
                ':id' => $resultado['id'],
            ]);

            $this->conteos['aprendizajes_existentes']++;
            return (int)$resultado['id'];
        }

        $sql = "INSERT INTO ares_catalogo_aprendizajes 
                (competencia_id, area_id, grado, num_dba, enunciado, version, disciplina) 
                VALUES (:competencia_id, :area_id, :grado, :num_dba, :enunciado, 'V.1', 'general')";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([
            ':competencia_id' => $competenciaId,
            ':area_id' => $areaId,
            ':grado' => $grado,
            ':num_dba' => $numDba,
            ':enunciado' => $enunciado,
        ]);

        $this->conteos['aprendizajes_nuevos']++;
        return (int)$this->conexion->lastInsertId();
    }

    private function procesarEvidencias(int $aprendizajeId, array $evidencias): void
    {
        if (empty($evidencias)) {
            return;
        }

        $sql = "DELETE FROM ares_catalogo_evidencias WHERE aprendizaje_id = :aprendizaje_id";
        $stmt = $this->conexion->prepare($sql);
        $stmt->execute([':aprendizaje_id' => $aprendizajeId]);
        $this->conteos['evidencias_eliminadas'] += $stmt->rowCount();

        $sql = "INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) 
                VALUES (:aprendizaje_id, :texto)";
        $stmt = $this->conexion->prepare($sql);

        foreach ($evidencias as $evidencia) {
            $stmt->execute([
                ':aprendizaje_id' => $aprendizajeId,
                ':texto' => $evidencia,
            ]);
            $this->conteos['evidencias_insertadas']++;
        }
    }

    private function esLineaGrado(string $linea): bool
    {
        return preg_match('/^##\s+🎓\s+Grado\s+\d+º/u', $linea) === 1;
    }

    private function esLineaDBA(string $linea): bool
    {
        return preg_match('/^###\s+📌\s+DBA\s+#\d+/u', $linea) === 1;
    }

    private function extraerGrado(string $linea): string
    {
        if (preg_match('/Grado\s+(\d+º)/u', $linea, $coincidencias)) {
            return 'Grado ' . $coincidencias[1];
        }
        throw new Exception("No se pudo extraer el grado de: {$linea}");
    }

    private function extraerNumDBA(string $linea): int
    {
        if (preg_match('/DBA\s+#(\d+)/u', $linea, $coincidencias)) {
            return (int)$coincidencias[1];
        }
        throw new Exception("No se pudo extraer el número de DBA de: {$linea}");
    }

    private function imprimirResumen(float $tiempoInicio): void
    {
        $tiempoTotal = microtime(true) - $tiempoInicio;

        echo "===============================================\n";
        echo "   RESUMEN DE LA SIEMBRA CURRICULAR MEN\n";
        echo "===============================================\n";
        echo "Archivos procesados: {$this->conteos['archivos_procesados']}\n";
        echo "Competencias existentes: {$this->conteos['competencias_existentes']}\n";
        echo "Competencias nuevas: {$this->conteos['competencias_nuevas']}\n";
        echo "Aprendizajes actualizados: {$this->conteos['aprendizajes_existentes']}\n";
        echo "Aprendizajes nuevos: {$this->conteos['aprendizajes_nuevos']}\n";
        echo "Evidencias insertadas: {$this->conteos['evidencias_insertadas']}\n";
        echo "Errores: {$this->conteos['errores']}\n";
        echo "Tiempo total: " . round($tiempoTotal, 2) . " segundos\n";
        echo "===============================================\n";
        echo "Estado: COMPLETADO EXITOSAMENTE ✅\n";
    }
}

if (PHP_SAPI === 'cli' || !debug_backtrace()) {
    $sembrador = new SembradorDBA($db);
    $sembrador->ejecutar();
}
