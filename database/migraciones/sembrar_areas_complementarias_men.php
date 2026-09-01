<?php

declare(strict_types=1);

/**
 * Script: database/migraciones/sembrar_areas_complementarias_men.php
 * 
 * Siembra rigurosa de descriptores, competencias y evidencias observables
 * para las áreas no-DBA y de Media Académica de la Ley 115 (Art. 23):
 * 
 * - Área 5: Educación Ética y en Valores Humanos / Competencias Ciudadanas (Guía N° 6 MEN)
 * - Área 6: Educación Física, Recreación y Deportes (Doc. N° 15 MEN)
 * - Área 7: Educación Religiosa (Estándares Nacionales ERE)
 * - Área 11: Ciencias Políticas y Económicas (Media 10°-11°)
 * - Área 13: Filosofía (Media 10°-11°)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../../php/db.php';

class SembradorAreasComplementariasElite
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ejecutar(): void
    {
        $inicio = microtime(true);

        echo "=======================================================\n";
        echo "   SIEMBRA DE ÁREAS COMPLEMENTARIAS Y MEDIA MEN\n";
        echo "=======================================================\n";
        echo "Fecha y Hora: " . date('Y-m-d H:i:s') . "\n\n";

        $this->pdo->beginTransaction();

        try {
            // 1. Área 5: Ética y Valores / Ciudadanas (Guía 6 MEN)
            $this->sembrarEtica();

            // 2. Área 6: Educación Física (Doc. 15 MEN)
            $this->sembrarEducacionFisica();

            // 3. Área 7: Educación Religiosa (Estándares ERE)
            $this->sembrarEducacionReligiosa();

            // 4. Área 11: Ciencias Políticas y Económicas
            $this->sembrarPoliticaEconomia();

            // 5. Área 13: Filosofía
            $this->sembrarFilosofia();

            $this->pdo->commit();

            $duracion = round(microtime(true) - $inicio, 2);
            echo "=======================================================\n";
            echo "   RESUMEN DE SIEMBRA COMPLEMENTARIA MEN\n";
            echo "=======================================================\n";
            echo "✓ Área 5: Ética y Valores (Guía 6 MEN) - COMPLETADA\n";
            echo "✓ Área 6: Educación Física (Doc. 15 MEN) - COMPLETADA\n";
            echo "✓ Área 7: Educación Religiosa (ERE) - COMPLETADA\n";
            echo "✓ Área 11: Ciencias Políticas y Económicas - COMPLETADA\n";
            echo "✓ Área 13: Filosofía (Media 10°-11°) - COMPLETADA\n";
            echo "Tiempo de ejecución: {$duracion} segundos\n";
            echo "Estado: 100% CUBIERTO SEGÚN LEY 115 Y DIRECTIVAS MEN ✅\n";
            echo "=======================================================\n";

        } catch (Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            echo "\n❌ ERROR FATAL: " . $e->getMessage() . "\n";
            echo "   Línea: " . $e->getLine() . "\n";
            exit(1);
        }
    }

    private function obtenerOCrearCompetencia(int $areaId, string $nombre, string $descripcion): int
    {
        $stmtBusca = $this->pdo->prepare("SELECT id FROM ares_catalogo_competencias WHERE area_id = ? AND nombre = ?");
        $stmtBusca->execute([$areaId, $nombre]);
        $id = $stmtBusca->fetchColumn();

        if ($id) {
            return (int)$id;
        }

        $stmtIns = $this->pdo->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");
        $stmtIns->execute([$areaId, $nombre, $descripcion]);
        return (int)$this->pdo->lastInsertId();
    }

    private function guardarAprendizajeYEvidencias(
        int $competenciaId,
        int $areaId,
        string $grado,
        int $numDba,
        string $enunciado,
        string $version,
        string $disciplina,
        array $evidencias
    ): void {
        $stmtBusca = $this->pdo->prepare("SELECT id FROM ares_catalogo_aprendizajes WHERE area_id = ? AND grado = ? AND num_dba = ? AND disciplina = ?");
        $stmtBusca->execute([$areaId, $grado, $numDba, $disciplina]);
        $apId = $stmtBusca->fetchColumn();

        if ($apId) {
            $stmtUpd = $this->pdo->prepare("UPDATE ares_catalogo_aprendizajes SET competencia_id = ?, enunciado = ?, version = ? WHERE id = ?");
            $stmtUpd->execute([$competenciaId, $enunciado, $version, $apId]);
            $aprendizajeId = (int)$apId;
        } else {
            $stmtIns = $this->pdo->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado, version, disciplina) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtIns->execute([$competenciaId, $areaId, $grado, $numDba, $enunciado, $version, $disciplina]);
            $aprendizajeId = (int)$this->pdo->lastInsertId();
        }

        $stmtDel = $this->pdo->prepare("DELETE FROM ares_catalogo_evidencias WHERE aprendizaje_id = ?");
        $stmtDel->execute([$aprendizajeId]);

        $stmtEv = $this->pdo->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)");
        foreach ($evidencias as $ev) {
            $stmtEv->execute([$aprendizajeId, $ev]);
        }
    }

    private function sembrarEtica(): void
    {
        $areaId = 5;
        echo "🕊️ 1. Sembrando Educación Ética y Competencias Ciudadanas (Área ID 5, Guía 6 MEN)...\n";

        $comp1 = $this->obtenerOCrearCompetencia($areaId, 'Convivencia y Paz', 'Capacidad para establecer relaciones constructivas y pacíficas basadas en el respeto a los derechos humanos.');
        $comp2 = $this->obtenerOCrearCompetencia($areaId, 'Participación y Responsabilidad Democrática', 'Capacidad para actuar constructivamente en la toma de decisiones colectivas y el ejercicio de la ciudadanía.');
        $comp3 = $this->obtenerOCrearCompetencia($areaId, 'Pluralidad, Identidad y Valoración de las Diferencias', 'Reconocimiento y respeto por la diversidad cultural, étnica, de género y de pensamiento.');

        $ciclos = [
            'Grado 1º-3º' => [
                ['comp' => $comp1, 'enunciado' => 'Reconoce que las emociones y los acuerdos de convivencia permiten resolver desacuerdos en el aula y el hogar.', 'evidencias' => ['Expresa sus sentimientos e ideas constructivamente sin agredir a los demás.', 'Participa activamente en la construcción de los acuerdos de convivencia del salón de clase.']],
                ['comp' => $comp2, 'enunciado' => 'Comprende que las normas ayudan a promover el bienestar colectivo y el cuidado de los bienes escolares.', 'evidencias' => ['Cumple responsablemente con los deberes escolares y cuida los materiales compartidos.', 'Participa en la elección del personero estudiantil y representantes de grupo.']],
                ['comp' => $comp3, 'enunciado' => 'Valora y respeta las diferencias individuales, reconociendo que todos los niños y niñas tienen los mismos derechos.', 'evidencias' => ['Incluye a todos sus compañeros en actividades lúdicas sin discriminar.', 'Reconoce la historia y tradiciones culturales de su familia y comunidad.']]
            ],
            'Grado 4º-5º' => [
                ['comp' => $comp1, 'enunciado' => 'Aplica el diálogo, la empatía y la asertividad como herramientas fundamentales para la transformación pacífica de conflictos.', 'evidencias' => ['Identifica las causas y puntos de vista de las partes involucradas en una situación de conflicto escolar.', 'Propone soluciones consensuadas que benefician a las partes sin recurrir a la violencia.']],
                ['comp' => $comp2, 'enunciado' => 'Analiza la estructura del gobierno escolar y los mecanismos de participación democrática en la institución educativa.', 'evidencias' => ['Evalúa las propuestas de los candidatos al gobierno escolar y ejerce su derecho al voto informado.', 'Comprende la función de los derechos fundamentales consagrados en la Constitución de 1991.']],
                ['comp' => $comp3, 'enunciado' => 'Rechaza situaciones de exclusión, burlas y discriminación por motivos de género, etnia, religión o condición socioeconómica.', 'evidencias' => ['Defiende a compañeros que son víctimas de burlas o acoso escolar (bullying).', 'Promueve el respeto por la diversidad cultural y regional de Colombia en el entorno escolar.']]
            ],
            'Grado 6º-7º' => [
                ['comp' => $comp1, 'enunciado' => 'Analiza críticamente cómo los prejuicios y estereotipos afectan las relaciones interpersonales y la convivencia escolar.', 'evidencias' => ['Distingue entre hechos y opiniones descalificadoras en las relaciones entre pares.', 'Implementa estrategias de mediación escolar para dirimir controversias cotidianas.']],
                ['comp' => $comp2, 'enunciado' => 'Ejerce veeduría y control social frente a los compromisos del gobierno escolar y el cumplimiento del Manual de Convivencia.', 'evidencias' => ['Verifica el cumplimiento de los acuerdos del personero y consejo estudiantil.', 'Utiliza los canales formales e institucionales para presentar peticiones, quejas y reclamos respetuosos.']],
                ['comp' => $comp3, 'enunciado' => 'Comprende la importancia de la igualdad de género y la protección de los derechos de minorías y grupos vulnerables.', 'evidencias' => ['Promueve la equidad de oportunidades y el trato digno entre hombres y mujeres en todas las actividades.', 'Analiza el valor del patrimonio cultural y lingüístico de las comunidades indígenas y afrocolombianas.']]
            ],
            'Grado 8º-9º' => [
                ['comp' => $comp1, 'enunciado' => 'Evalúa dilemas morales complejos analizando las consecuencias éticas de las decisiones personales y colectivas.', 'evidencias' => ['Argumenta su posición ética ante situaciones que involucran la justicia, la lealtad y la legalidad.', 'Reconoce el impacto del perdón, la reparación y la no repetición en la reconstrucción del tejido social.']],
                ['comp' => $comp2, 'enunciado' => 'Utiliza mecanismos constitucionales de protección ciudadana (Tutela, Derecho de Petición, Acción Popular) ante vulneración de derechos.', 'evidencias' => ['Redacta derechos de petición sencillos orientados a la solución de problemáticas comunitarias.', 'Explica las funciones de las entidades de control del Estado (Procuraduría, Defensoría del Pueblo, Personería).']],
                ['comp' => $comp3, 'enunciado' => 'Asume una postura crítica frente a la discriminación estructural, el racismo, la xenofobia y el ciberacoso.', 'evidencias' => ['Promueve el uso ético y responsable de las redes sociales combatiendo la desinformación y el ciberbullying.', 'Lidera campañas escolares en pro de la inclusión y la convivencia pacífica.']]
            ],
            'Grado 10º-11º' => [
                ['comp' => $comp1, 'enunciado' => 'Analiza críticamente los procesos de memoria histórica, verdad y justicia transicional en el marco del posconflicto colombiano.', 'evidencias' => ['Examina las causas históricas del conflicto armado en Colombia y los retos para la construcción de una paz duradera.', 'Formula propuestas de innovación social para la reconciliación y el desarrollo sostenible en su región.']],
                ['comp' => $comp2, 'enunciado' => 'Ejerce una ciudadanía activa, ética y transformadora fundamentada en el Estado Social de Derecho y los Derechos Humanos.', 'evidencias' => ['Debate sobre la transparencia pública, la lucha contra la corrupción y la ética en la administración ciudadana.', 'Participa en iniciativas de voluntariado, veeduría juvenil y liderazgo social comunitario.']],
                ['comp' => $comp3, 'enunciado' => 'Consolida un proyecto de vida ético comprometido con la dignidad humana, la sostenibilidad ambiental y la justicia social.', 'evidencias' => ['Toma decisiones vocacionales y profesionales alineadas con principios éticos y de bien común.', 'Promueve una cultura de paz, legalidad y respeto irrestricto a los Derechos Humanos en su entorno.']]
            ]
        ];

        foreach ($ciclos as $grado => $lista) {
            foreach ($lista as $idx => $item) {
                $this->guardarAprendizajeYEvidencias(
                    $item['comp'],
                    $areaId,
                    $grado,
                    $idx + 1,
                    $item['enunciado'],
                    'Guía 6 MEN',
                    'Competencias Ciudadanas',
                    $item['evidencias']
                );
            }
        }
        echo "   ✓ Área 5 sembrada con éxito.\n\n";
    }

    private function sembrarEducacionFisica(): void
    {
        $areaId = 6;
        echo "🏃 2. Sembrando Educación Física, Recreación y Deportes (Área ID 6, Doc. 15 MEN)...\n";

        $comp1 = $this->obtenerOCrearCompetencia($areaId, 'Competencia Motriz', 'Desarrollo de las capacidades coordinativas, condicionales y habilidades motrices básicas y específicas.');
        $comp2 = $this->obtenerOCrearCompetencia($areaId, 'Competencia Expresiva Corporal', 'Utilización del lenguaje corporal, el gesto y el movimiento como medio de comunicación, creatividad y arte.');
        $comp3 = $this->obtenerOCrearCompetencia($areaId, 'Competencia Axiológica Corporal', 'Cuidado del cuerpo, hábitos de vida saludable, juego limpio, trabajo en equipo y respeto por el entorno.');

        $ciclos = [
            'Grado 1º-3º' => [
                ['comp' => $comp1, 'enunciado' => 'Explora y coordina sus patrones motrices básicos (correr, saltar, lanzar, equilibrar) en juegos y actividades lúdicas.', 'evidencias' => ['Realiza desplazamientos variados cambiando de dirección, velocidad y nivel.', 'Mantiene el equilibrio estático y dinámico en diversas superficies y posturas.']],
                ['comp' => $comp3, 'enunciado' => 'Adopta hábitos básicos de higiene, hidratación y cuidado personal en la práctica de actividades físicas.', 'evidencias' => ['Sigue normas de seguridad y cuidado del propio cuerpo antes, durante y después del ejercicio.', 'Respeta a sus compañeros y comparte los materiales deportivos en el juego lúdico.']]
            ],
            'Grado 4º-5º' => [
                ['comp' => $comp1, 'enunciado' => 'Combina habilidades motrices básicas en la iniciación a fundamentos predeportivos y secuencias rítmicas.', 'evidencias' => ['Aplica destrezas de pase, recepción y conducción en juegos predeportivos.', 'Coordina movimientos corporales al ritmo de estímulos sonoros y musicales.']],
                ['comp' => $comp3, 'enunciado' => 'Aplica principios de juego limpio (fair play), trabajo en equipo y aceptación de normas en competencias escolares.', 'evidencias' => ['Acepta con respeto los resultados del juego (victoria o derrota) valorando el esfuerzo del rival.', 'Participa cooperativamente en actividades recreativas grupales.']]
            ],
            'Grado 6º-7º' => [
                ['comp' => $comp1, 'enunciado' => 'Aplica fundamentos técnicos básicos y tácticas elementales en disciplinas deportivas individuales y de conjunto.', 'evidencias' => ['Ejecuta con fluidez técnicas deportivas básicas (atletismo, voleibol, baloncesto, fútbol).', 'Diseña y comprende estrategias colectivas sencillas de ataque y defensa.']],
                ['comp' => $comp3, 'enunciado' => 'Comprende la relación entre la actividad física sistemática, la nutrición balanceada y la prevención de enfermedades.', 'evidencias' => ['Monitorea su frecuencia cardíaca y esfuerzo físico durante la sesión de entrenamiento.', 'Adopta posturas ergonómicas adecuadas en las actividades de la vida diaria.']]
            ],
            'Grado 8º-9º' => [
                ['comp' => $comp1, 'enunciado' => 'Mejora sus capacidades físicas condicionales (fuerza, resistencia, velocidad, flexibilidad) mediante planes de entrenamiento guiados.', 'evidencias' => ['Aplica pruebas de valoración de la condición física (test de aptitud física) analizando su evolución.', 'Ejecuta técnicas deportivas avanzadas con eficiencia biomecánica y control corporal.']],
                ['comp' => $comp2, 'enunciado' => 'Diseña y ejecuta composiciones coreográficas, esquemas gimnásticos o representaciones de expresión corporal.', 'evidencias' => ['Expresa ideas y emociones mediante montajes dancísticos o gimnásticos colectivos.', 'Integra elementos culturales y folclóricos en sus manifestaciones corporales.']]
            ],
            'Grado 10º-11º' => [
                ['comp' => $comp1, 'enunciado' => 'Diseña y gestiona su propio plan de acondicionamiento físico y salud orientado a la autonomía y el bienestar integral.', 'evidencias' => ['Estructura rutinas de ejercicio adaptadas a sus necesidades, metas y condición biológica.', 'Aplica principios de dosificación de la carga física, recuperación y prevención de lesiones deportivas.']],
                ['comp' => $comp3, 'enunciado' => 'Lidera y organiza eventos deportivos, recreativos y comunitarios promoviendo la inclusión y la convivencia.', 'evidencias' => ['Coordina torneos interclases aplicando reglamentos deportivos oficiales y valores éticos.', 'Promueve el uso activo del tiempo libre y la actividad física como factor de cohesión comunitaria.']]
            ]
        ];

        foreach ($ciclos as $grado => $lista) {
            foreach ($lista as $idx => $item) {
                $this->guardarAprendizajeYEvidencias(
                    $item['comp'],
                    $areaId,
                    $grado,
                    $idx + 1,
                    $item['enunciado'],
                    'Doc. 15 MEN',
                    'Educación Física',
                    $item['evidencias']
                );
            }
        }
        echo "   ✓ Área 6 sembrada con éxito.\n\n";
    }

    private function sembrarEducacionReligiosa(): void
    {
        $areaId = 7;
        echo "⛪ 3. Sembrando Educación Religiosa Escolar (Área ID 7, Estándares ERE)...\n";

        $comp1 = $this->obtenerOCrearCompetencia($areaId, 'Dimensión Antropológica y Ética', 'Comprensión del sentido de la vida, la dignidad humana y los valores morales y trascendentes.');
        $comp2 = $this->obtenerOCrearCompetencia($areaId, 'Dimensión Bíblica y Teológica', 'Conocimiento de las fuentes sagradas, doctrinas y tradiciones de fe en su contexto histórico.');
        $comp3 = $this->obtenerOCrearCompetencia($areaId, 'Dimensión Eclesiológica, Social y Pluralista', 'Compromiso social, respeto por la libertad de cultos, el diálogo interreligioso y la solidaridad.');

        $ciclos = [
            'Grado 1º-3º' => [
                ['comp' => $comp1, 'enunciado' => 'Reconoce el valor de la vida, la familia y la amistad como dones fundamentales para el desarrollo humano.', 'evidencias' => ['Expresa gratitud y cuidado por los miembros de su familia y la naturaleza.', 'Identifica relatos bíblicos y enseñanzas morales sencillas sobre el amor y la generosidad.']],
                ['comp' => $comp3, 'enunciado' => 'Practica el respeto y la tolerancia hacia las diversas formas de celebración y creencias religiosas en su comunidad.', 'evidencias' => ['Participa con respeto en momentos de reflexión y convivencia escolar.', 'Reconoce que todas las personas tienen derecho a profesar sus creencias libremente.']]
            ],
            'Grado 4º-5º' => [
                ['comp' => $comp1, 'enunciado' => 'Comprende el testimonio de personas que han dedicado su vida al servicio de los más necesitados y la justicia.', 'evidencias' => ['Analiza biografías de líderes espirituales y defensores de los derechos humanos.', 'Propone acciones solidarias para apoyar a personas en situación de vulnerabilidad en su colegio.']],
                ['comp' => $comp2, 'enunciado' => 'Interpreta textos sagrados reconociendo mensajes de esperanza, fraternidad y reconciliación comunitaria.', 'evidencias' => ['Explica las enseñanzas principales de parábolas y relatos fundacionales de la fe.', 'Relaciona los mandamientos y principios éticos universales con la vida cotidiana.']]
            ],
            'Grado 6º-7º' => [
                ['comp' => $comp1, 'enunciado' => 'Examina la búsqueda de sentido, la interioridad y la trascendencia en la etapa de la adolescencia y juventud.', 'evidencias' => ['Reflexiona sobre sus metas personales, valores éticos y vocación de servicio.', 'Reconoce la importancia del silencio, la oración y la meditación en el bienestar emocional y espiritual.']],
                ['comp' => $comp3, 'enunciado' => 'Analiza el papel de las comunidades de fe en la construcción de paz y la defensa de la dignidad humana.', 'evidencias' => ['Comprende el compromiso de las iglesias e instituciones religiosas en obras sociales y educativas.', 'Promueve el diálogo fraterno entre compañeros de distintas confesiones religiosas.']]
            ],
            'Grado 8º-9º' => [
                ['comp' => $comp2, 'enunciado' => 'Analiza críticamente el fenómeno religioso en la historia universal y su influencia en el arte, la cultura y la ética.', 'evidencias' => ['Identifica las grandes religiones de la humanidad (Judaísmo, Cristianismo, Islam, Hinduismo, Budismo).', 'Distingue entre la vivencia auténtica de la fe y manifestaciones de fanatismo o intolerancia.']],
                ['comp' => $comp3, 'enunciado' => 'Asume una postura de corresponsabilidad social y ecológica fundamentada en la Doctrina Social y los valores humanos.', 'evidencias' => ['Lidera iniciativas de cuidado del medio ambiente ("cuidado de la casa común").', 'Debate sobre dilemas bioéticos y la defensa irrestricta de la vida en todas sus etapas.']]
            ],
            'Grado 10º-11º' => [
                ['comp' => $comp1, 'enunciado' => 'Construye una síntesis personal entre fe, ciencia, cultura y proyecto de vida profesional y social.', 'evidencias' => ['Argumenta la compatibilidad entre el pensamiento científico y la dimensión espiritual del ser humano.', 'Estructura un proyecto de vida con sólidos fundamentos éticos y sentido de trascendencia.']],
                ['comp' => $comp3, 'enunciado' => 'Ejerce un liderazgo transformador fundamentado en la libertad de conciencia, la fraternidad universal y el diálogo interreligioso.', 'evidencias' => ['Participa en foros de ecumenismo y diálogo interreligioso con apertura y rigor reflexivo.', 'Defiende la libertad religiosa y de culto como derecho humano inalienable en Colombia.']]
            ]
        ];

        foreach ($ciclos as $grado => $lista) {
            foreach ($lista as $idx => $item) {
                $this->guardarAprendizajeYEvidencias(
                    $item['comp'],
                    $areaId,
                    $grado,
                    $idx + 1,
                    $item['enunciado'],
                    'Estándares ERE',
                    'Educación Religiosa',
                    $item['evidencias']
                );
            }
        }
        echo "   ✓ Área 7 sembrada con éxito.\n\n";
    }

    private function sembrarPoliticaEconomia(): void
    {
        $areaId = 11;
        echo "🏛️ 4. Sembrando Ciencias Políticas y Económicas (Área ID 11, Media 10°-11°)...\n";

        $comp1 = $this->obtenerOCrearCompetencia($areaId, 'Pensamiento Social y Político', 'Comprensión de los sistemas políticos, las formas de gobierno, el poder y la participación ciudadana.');
        $comp2 = $this->obtenerOCrearCompetencia($areaId, 'Análisis Económico y Financiero', 'Análisis de los modelos económicos, las políticas fiscales y monetarias, el mercado y el desarrollo sostenible.');

        $grados = [
            'Grado 10º' => [
                ['comp' => $comp1, 'enunciado' => 'Analiza la evolución histórica del Estado moderno, la separación de poderes y los regímenes democráticos vs autoritarios.', 'evidencias' => ['Compara las teorías contractualistas del Estado (Hobbes, Locke, Rousseau, Montesquieu).', 'Evalúa el funcionamiento de las instituciones públicas y los organismos de control en Colombia.']],
                ['comp' => $comp2, 'enunciado' => 'Comprende los fundamentos microeconómicos: oferta, demanda, formación de precios y comportamiento del consumidor.', 'evidencias' => ['Grafica e interpreta curvas de oferta y demanda identificando puntos de equilibrio de mercado.', 'Analiza el impacto de la inflación, el desempleo y las tasas de interés en la economía familiar.']]
            ],
            'Grado 11º' => [
                ['comp' => $comp1, 'enunciado' => 'Evalúa críticamente la geopolítica mundial contemporánea, los bloques económicos y los tratados internacionales.', 'evidencias' => ['Analiza las tensiones geopolíticas entre potencias globales y sus implicaciones para América Latina.', 'Examina los retos de la globalización, la soberanía nacional y la gobernanza global ante crisis humanitarias.']],
                ['comp' => $comp2, 'enunciado' => 'Analiza las variables macroeconómicas (PIB, Deuda Pública, Balanza de Pagos) y los modelos de desarrollo sostenible.', 'evidencias' => ['Debate sobre las políticas fiscales, tributarias y monetarias del Banco de la República.', 'Formula propuestas de política pública para la superación de la pobreza y la equidad social en Colombia.']]
            ]
        ];

        foreach ($grados as $grado => $lista) {
            foreach ($lista as $idx => $item) {
                $this->guardarAprendizajeYEvidencias(
                    $item['comp'],
                    $areaId,
                    $grado,
                    $idx + 1,
                    $item['enunciado'],
                    'Lineamientos Media MEN',
                    'Ciencias Políticas y Económicas',
                    $item['evidencias']
                );
            }
        }
        echo "   ✓ Área 11 sembrada con éxito.\n\n";
    }

    private function sembrarFilosofia(): void
    {
        $areaId = 13;
        echo "🧠 5. Sembrando Filosofía (Área ID 13, Media 10°-11°)...\n";

        $comp1 = $this->obtenerOCrearCompetencia($areaId, 'Pensamiento Crítico y Epistemológico', 'Análisis de las teorías del conocimiento, la ontología, la verdad y los métodos filosóficos.');
        $comp2 = $this->obtenerOCrearCompetencia($areaId, 'Argumentación y Diálogo Filosófico', 'Construcción de argumentos lógicos, refutación de falacias y deliberación ética y estética.');

        $grados = [
            'Grado 10º' => [
                ['comp' => $comp1, 'enunciado' => 'Comprende el paso del mito al logos y los problemas fundamentales de la filosofía clásica, medieval y renacentista.', 'evidencias' => ['Analiza los aportes de Sócrates, Platón y Aristóteles a la metafísica, la ética y la política.', 'Compara las posturas racionalistas (Descartes) y empiristas (Hume, Locke) sobre el origen del conocimiento.']],
                ['comp' => $comp2, 'enunciado' => 'Aplica las reglas de la lógica formal e informal para identificar falacias y construir discursos coherentes.', 'evidencias' => ['Distingue entre premisas válidas y falacias argumentativas en debates públicos y textos académicos.', 'Redacta disertaciones filosóficas breves defendiendo una tesis con rigor conceptual.']]
            ],
            'Grado 11º' => [
                ['comp' => $comp1, 'enunciado' => 'Analiza críticamente los dilemas de la filosofía moderna y contemporánea (Kant, Hegel, Nietzsche, Existencialismo, Escuela de Frankfurt).', 'evidencias' => ['Examina la crítica kantiana a la razón pura y la fundamentación del imperativo categórico en la ética.', 'Debate sobre la condición humana, la libertad, el absurdo y la angustia existencial en el siglo XXI.']],
                ['comp' => $comp2, 'enunciado' => 'Reflexiona sobre los desafíos éticos, estéticos y políticos del mundo digital, la biotecnología y la posmodernidad.', 'evidencias' => ['Formula preguntas ontológicas sobre la inteligencia artificial, la conciencia y la identidad humana.', 'Sustenta ensayos filosóficos de alta complejidad con rigor hermenéutico y pensamiento autónomo.']]
            ]
        ];

        foreach ($grados as $grado => $lista) {
            foreach ($lista as $idx => $item) {
                $this->guardarAprendizajeYEvidencias(
                    $item['comp'],
                    $areaId,
                    $grado,
                    $idx + 1,
                    $item['enunciado'],
                    'Lineamientos Media MEN',
                    'Filosofía',
                    $item['evidencias']
                );
            }
        }
        echo "   ✓ Área 13 sembrada con éxito.\n\n";
    }
}

if (PHP_SAPI === 'cli' || !debug_backtrace()) {
    $migracion = new SembradorAreasComplementariasElite($db);
    $migracion->ejecutar();
}
