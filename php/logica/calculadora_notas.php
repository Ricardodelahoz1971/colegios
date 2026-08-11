<?php
declare(strict_types=1);

/**
 * 🏛️ MOTOR DE LÓGICA Y REGULACIÓN ACADÉMICA (PERSEUS SYSTEM NUCLEUS)
 * Cálculo matemático de notas institucionales, promedios ponderados y aplicación de políticas de recuperación.
 * 
 * @author Ingeniería Élite v9.5
 */

class CalculadoraNotas {
    private static ?array $escalaActiva = null;
    private static ?string $politicaRecuperacion = null;

    /**
     * Recupera la escala de notas activa de la base de datos
     */
    public static function obtenerEscala(PDO $db): array {
        if (self::$escalaActiva !== null) {
            return self::$escalaActiva;
        }

        $stmt = $db->prepare("SELECT * FROM eval_config_escala WHERE activo = :activo LIMIT 1");
        $stmt->execute([':activo' => 1]);
        $escala = $stmt->fetch();

        if (!$escala) {
            // Valores fallback por defecto
            $escala = [
                'nota_minima' => 1.0,
                'nota_maxima' => 5.0,
                'nota_aprobacion' => 3.0,
                'rango_superior_min' => 4.6,
                'rango_alto_min' => 4.0,
                'rango_basico_min' => 3.0
            ];
        } else {
            $escala['nota_minima'] = (float)$escala['nota_minima'];
            $escala['nota_maxima'] = (float)$escala['nota_maxima'];
            $escala['nota_aprobacion'] = (float)$escala['nota_aprobacion'];
            $escala['rango_superior_min'] = (float)$escala['rango_superior_min'];
            $escala['rango_alto_min'] = (float)$escala['rango_alto_min'];
            $escala['rango_basico_min'] = (float)$escala['rango_basico_min'];
        }

        self::$escalaActiva = $escala;
        return $escala;
    }

    /**
     * Recupera la política de recuperación global
     */
    public static function obtenerPoliticaRecuperacion(PDO $db): string {
        if (self::$politicaRecuperacion !== null) {
            return self::$politicaRecuperacion;
        }

        $stmt = $db->prepare("SELECT valor FROM configuracion_global WHERE clave = 'politica_recuperacion' LIMIT 1");
        $stmt->execute();
        $politica = $stmt->fetchColumn();

        self::$politicaRecuperacion = $politica ? (string)$politica : 'reemplazo';
        return self::$politicaRecuperacion;
    }

    /**
     * Aplica la política de recuperación sobre una nota original y una nota de recuperación
     */
    public static function aplicarPoliticaRecuperacion(float $notaOriginal, ?float $notaRecuperacion, string $politica, float $notaAprobacion): float {
        if ($notaRecuperacion === null || $notaRecuperacion < 0.0) {
            return $notaOriginal;
        }

        // Si la nota de recuperación es menor o igual a la original, no se aplica (no perjudica al alumno)
        if ($notaRecuperacion <= $notaOriginal) {
            return $notaOriginal;
        }

        switch ($politica) {
            case 'promedio':
                return round(($notaOriginal + $notaRecuperacion) / 2.0, 2);
            
            case 'tope_aprobacion':
                // La recuperación sube la nota original, pero tiene un tope que es la nota de aprobación
                return round(max($notaOriginal, min($notaRecuperacion, $notaAprobacion)), 2);

            case 'reemplazo':
            default:
                // Reemplaza directamente la nota original por la de recuperación
                return round($notaRecuperacion, 2);
        }
    }

    /**
     * Calcula la nota definitiva ponderada de una materia para un estudiante en un rango de fechas opcional.
     */
    public static function calcularDefinitivaMateria(PDO $db, int $estudianteId, int $materiaId, ?string $f_inicio = null, ?string $f_fin = null): float {
        $politica = self::obtenerPoliticaRecuperacion($db);
        $escala = self::obtenerEscala($db);
        $nota_aprobacion = $escala['nota_aprobacion'];

        // Obtener pesos de las dimensiones evaluativas
        $pesos_dimensiones = [];
        $stmt_pesos = $db->prepare("SELECT id, peso_global FROM ares_clases_nota WHERE estado = :estado");
        $stmt_pesos->execute([':estado' => 1]);
        while ($row_p = $stmt_pesos->fetch(PDO::FETCH_ASSOC)) {
            $pesos_dimensiones[(int)$row_p['id']] = (float)$row_p['peso_global'] / 100.0;
        }
        if (empty($pesos_dimensiones)) {
            $pesos_dimensiones = [
                1 => 0.40, // Saber
                2 => 0.40, // Hacer
                3 => 0.20  // Ser
            ];
        }

        // Cargar calificaciones diarias de clase para esta materia
        $sql_act = "
            SELECT cd.calificacion, cd.nota_recuperacion, a.clase_nota_id
            FROM ares_calificaciones_desglose cd
            JOIN ares_actividades a ON cd.actividad_id = a.id
            LEFT JOIN aula_recursos r ON r.actividad_vinculada_id = a.id
            WHERE cd.estudiante_id = ? AND a.especialidad_id = ?
              AND (r.id IS NULL OR (r.visibilidad = 1 AND (r.fecha_inicio IS NULL OR r.fecha_inicio = '' OR NOW() >= r.fecha_inicio)))
              AND a.ambito = 'estandar'
        ";
        $params_act = [$estudianteId, $materiaId];
        if ($f_inicio !== null && $f_fin !== null) {
            $sql_act .= " AND a.fecha_registro BETWEEN ? AND ?";
            $params_act[] = $f_inicio;
            $params_act[] = $f_fin;
        }
        $stmt_calif_act = $db->prepare($sql_act);
        $stmt_calif_act->execute($params_act);
        $calif_act = $stmt_calif_act->fetchAll(PDO::FETCH_ASSOC);

        // Cargar calificaciones de exámenes en línea
        $sql_ex = "
            SELECT IFNULL(r.calificacion_manual, r.calificacion_automatica) as calificacion,
                   r.calificacion_recuperacion as nota_recuperacion
            FROM eval_respuestas r
            JOIN eval_asignaciones asig ON r.asignacion_id = asig.id
            JOIN eval_pruebas p ON asig.prueba_id = p.id
            WHERE r.estudiante_id = ? AND p.materia_id = ?
        ";
        $params_ex = [$estudianteId, $materiaId];
        if ($f_inicio !== null && $f_fin !== null) {
            $sql_ex .= " AND asig.fecha_inicio BETWEEN ? AND ?";
            $params_ex[] = $f_inicio;
            $params_ex[] = $f_fin;
        }
        $stmt_calif_ex = $db->prepare($sql_ex);
        $stmt_calif_ex->execute($params_ex);
        $calif_ex = $stmt_calif_ex->fetchAll(PDO::FETCH_ASSOC);

        // Consolidar calificaciones en dimensiones
        $dimensiones_notas = [];
        foreach ($calif_act as $c) {
            $dim_id = (int)$c['clase_nota_id'];
            if ($c['calificacion'] !== null) {
                $val = (float)$c['calificacion'];
                $recup = $c['nota_recuperacion'] !== null ? (float)$c['nota_recuperacion'] : null;
                $val_final = self::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
                $dimensiones_notas[$dim_id][] = $val_final;
            }
        }

        foreach ($calif_ex as $c) {
            $dim_id = 1; // Exámenes siempre pertenecen a Saber (1)
            if ($c['calificacion'] !== null) {
                $val = (float)$c['calificacion'];
                $recup = $c['nota_recuperacion'] !== null ? (float)$c['nota_recuperacion'] : null;
                $val_final = self::aplicarPoliticaRecuperacion($val, $recup, $politica, $nota_aprobacion);
                $dimensiones_notas[$dim_id][] = $val_final;
            }
        }

        if (empty($dimensiones_notas)) {
            return 0.0;
        }

        // Calcular promedio ponderado con redistribución
        $promedios_dim = [];
        $suma_pesos_activos = 0.0;

        foreach ($pesos_dimensiones as $dim_id => $peso) {
            $dim_notas = $dimensiones_notas[$dim_id] ?? [];
            if (!empty($dim_notas)) {
                $promedios_dim[$dim_id] = array_sum($dim_notas) / count($dim_notas);
                $suma_pesos_activos += $peso;
            }
        }

        if (empty($promedios_dim)) {
            return 0.0;
        }

        $nota_definitiva = 0.0;
        foreach ($promedios_dim as $dim_id => $prom) {
            $peso_original = $pesos_dimensiones[$dim_id];
            $peso_ajustado = $peso_original / $suma_pesos_activos;
            $nota_definitiva += $prom * $peso_ajustado;
        }

        return round($nota_definitiva, 2);
    }
}
