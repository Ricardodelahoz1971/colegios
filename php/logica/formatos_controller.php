<?php
declare(strict_types=1);

/**
 * Controlador para la lógica de formatos de matrícula.
 * Encapsula las consultas necesarias para imprimir la matrícula.
 */
class FormatosController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los datos necesarios para la matrícula de un estudiante.
     *
     * @param int $estudiante_id ID del estudiante.
     * @param int $formato_id    ID del formato de matrícula.
     * @return array Arreglo asociativo con las claves:
     *               'formato', 'estudiante', 'rector_nombre',
     *               'secretaria_nombre', 'ajustes_estetica', 'notas'.
     * @throws RuntimeException Si ocurre un error en la base de datos.
     */
    public function obtenerDatosMatricula(int $estudiante_id, int $formato_id): array
    {
        try {
            // 1. Obtener el formato de matrícula
            $stmtFormato = $this->pdo->prepare(
                'SELECT * FROM formatos_matricula WHERE id = ?'
            );
            $stmtFormato->execute([$formato_id]);
            $formato = $stmtFormato->fetch(PDO::FETCH_ASSOC);
            if ($formato === false) {
                throw new RuntimeException('Formato de matrícula no encontrado.');
            }

            // 2. Obtener estudiante y datos adicionales
            $stmtEstudiante = $this->pdo->prepare(
                'SELECT e.*, c.nombre_curso, da.* 
                 FROM estudiantes e
                 LEFT JOIN cursos c ON e.curso_id = c.id
                 LEFT JOIN estudiantes_datos_adicionales da ON e.id = da.estudiante_id
                 WHERE e.id = ?'
            );
            $stmtEstudiante->execute([$estudiante_id]);
            $estudiante = $stmtEstudiante->fetch(PDO::FETCH_ASSOC);
            if ($estudiante === false) {
                throw new RuntimeException('Estudiante no encontrado.');
            }

            // 3. Obtener nombre del rector (rol_id = 3)
            $stmtRector = $this->pdo->prepare(
                'SELECT nombre FROM usuarios WHERE rol_id = 3 LIMIT 1'
            );
            $stmtRector->execute();
            $rectorNombre = $stmtRector->fetchColumn();
            if ($rectorNombre === false) {
                $rectorNombre = '';
            }

            // 4. Obtener nombre de la secretaria (rol_id = 4)
            $stmtSecretaria = $this->pdo->prepare(
                'SELECT nombre FROM usuarios WHERE rol_id = 4 LIMIT 1'
            );
            $stmtSecretaria->execute();
            $secretariaNombre = $stmtSecretaria->fetchColumn();
            if ($secretariaNombre === false) {
                $secretariaNombre = '';
            }

            // 5. Obtener ajustes estéticos (clave => valor)
            $stmtAjustes = $this->pdo->prepare(
                'SELECT clave, valor FROM ajustes_estetica'
            );
            $stmtAjustes->execute();
            $ajustesEstetica = $stmtAjustes->fetchAll(PDO::FETCH_KEY_PAIR);

            // 6. Obtener calificaciones (notas) definitivas y docentes
            $stmtNotas = $this->pdo->prepare(
                'SELECT
                    e.nombre_especialidad  AS nombre_materia,
                    AVG(c.calificacion)    AS definitiva,
                    u.nombre               AS docente
                FROM ares_calificaciones_desglose c
                INNER JOIN ares_actividades a       ON c.actividad_id = a.id
                INNER JOIN especialidades e         ON a.especialidad_id = e.id
                LEFT  JOIN carga_academica ca       ON ca.especialidad_id = e.id AND ca.curso_id = (
                    SELECT curso_id FROM estudiantes WHERE id = ?
                )
                LEFT  JOIN usuarios u               ON ca.docente_id = u.id
                WHERE c.estudiante_id = ?
                GROUP BY e.id, e.nombre_especialidad, u.nombre
                ORDER BY e.nombre_especialidad ASC'
            );
            $stmtNotas->execute([$estudiante_id, $estudiante_id]);
            $notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

            // Construir y retornar el arreglo final
            return [
                'formato'           => $formato,
                'estudiante'        => $estudiante,
                'rector_nombre'     => $rectorNombre,
                'secretaria_nombre' => $secretariaNombre,
                'ajustes_estetica'  => $ajustesEstetica,
                'notas'             => $notas,
            ];
        } catch (PDOException $e) {
            throw new RuntimeException('Error al obtener los datos de matrícula.', 0, $e);
        }
    }
}
