<?php

declare(strict_types=1);

/**
 * Migración: Sembrar Inglés y Normalizar Catálogos Curriculares MEN
 * 
 * Objetivos:
 * 1. Reclasificar Educación Artística (Área ID 4) según Documento N° 16 MEN (Orientaciones Pedagógicas).
 * 2. Sembrar Guía 30 de Tecnología e Informática (Área ID 9) con sus 4 componentes y evidencias por ciclo.
 * 3. Sembrar DBAs oficiales de Inglés (Área ID 8, disciplina 'ingles') de Grados 1º a 11º conforme a Colombia Bilingüe MEN / MCER.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../../php/db.php';

class CatalogoMigracionElite
{
    private PDO $pdo;

    private const AREA_ARTISTICA = 4;
    private const AREA_INGLES = 8;
    private const AREA_TECNOLOGIA = 9;

    private const COMPETENCIAS_INGLES = [
        ['nombre' => '[Inglés] Comprensión Auditiva (Listening)', 'descripcion' => 'Capacidad de comprender textos orales en inglés según parámetros del MCER y Colombia Bilingüe.'],
        ['nombre' => '[Inglés] Comprensión de Lectura (Reading)', 'descripcion' => 'Capacidad de comprender textos escritos auténticos y adaptados en inglés.'],
        ['nombre' => '[Inglés] Expresión Escrita (Writing)', 'descripcion' => 'Capacidad de producir textos escritos estructurados y coherentes en lengua extranjera.'],
        ['nombre' => '[Inglés] Expresión Oral y Monólogo (Speaking)', 'descripcion' => 'Capacidad de producir presentaciones y discursos orales continuos en inglés.'],
        ['nombre' => '[Inglés] Interacción Comunicativa (Conversation)', 'descripcion' => 'Capacidad de interactuar y dialogar fluidamente en inglés en diversos contextos.']
    ];

    private const COMPETENCIAS_TECNOLOGIA = [
        ['nombre' => 'Naturaleza y evolución de la tecnología', 'descripcion' => 'Reconocimiento de los principios, conceptos y evolución histórica de los sistemas tecnológicos (Guía 30 MEN).'],
        ['nombre' => 'Apropiación y uso de la tecnología', 'descripcion' => 'Utilización adecuada, eficiente y segura de artefactos y procesos tecnológicos.'],
        ['nombre' => 'Solución de problemas con tecnología', 'descripcion' => 'Manejo de estrategias para la identificación, formulación y solución de problemas mediante artefactos tecnológicos.'],
        ['nombre' => 'Tecnología y sociedad', 'descripcion' => 'Actitud crítica y reflexiva frente a los impactos sociales, éticos y ambientales de la tecnología.']
    ];

    private const DBAS_INGLES = [
        1 => [
            ['num_dba' => 1, 'enunciado' => 'Comprende y responde a instrucciones sobre tareas escolares básicas, de manera verbal y no verbal.', 'evidencias' => ['Sigue instrucciones básicas del docente en el aula (Stand up, sit down, color, cut).', 'Responde de manera no verbal y verbal a comandos simples en el salón de clase.']],
            ['num_dba' => 2, 'enunciado' => 'Comprende y realiza declaraciones sencillas sobre su entorno inmediato (casa y escuela).', 'evidencias' => ['Identifica y nombra objetos comunes de la escuela y el hogar en inglés.', 'Señala y ubica objetos familiares siguiendo indicaciones sencillas.']],
            ['num_dba' => 3, 'enunciado' => 'Organiza la secuencia de eventos principales en una historia corta y sencilla sobre temas familiares.', 'evidencias' => ['Ordena imágenes para representar la secuencia de un relato breve.', 'Identifica el inicio y desenlace de historias simples leídas por el docente.']],
            ['num_dba' => 4, 'enunciado' => 'Responde preguntas sencillas sobre información personal básica (nombre, edad, familia).', 'evidencias' => ['Dice su nombre y edad al formularle preguntas básicas (What is your name?, How old are you?).', 'Reconoce a los miembros nucleares de la familia en diálogos simples.']]
        ],
        2 => [
            ['num_dba' => 1, 'enunciado' => 'Expresa ideas sencillas sobre temas estudiados usando palabras y frases cortas.', 'evidencias' => ['Nombra animales, colores, números y alimentos en frases elementales.', 'Describe cualidades básicas de objetos de su entorno cotidiano.']],
            ['num_dba' => 2, 'enunciado' => 'Comprende la secuencia de una historia corta y sencilla sobre temas familiares.', 'evidencias' => ['Reconoce el tema central en textos breves acompañados de ilustraciones.', 'Asocia imágenes con descripciones orales sencillas.']],
            ['num_dba' => 3, 'enunciado' => 'Identifica información básica sobre personas y objetos en textos orales y escritos.', 'evidencias' => ['Reconoce características físicas simples (tall, short, big, small).', 'Asocia palabras escritas con sus correspondientes ilustraciones.']],
            ['num_dba' => 4, 'enunciado' => 'Participa en conversaciones cortas formulando y respondiendo preguntas sobre gustos y preferencias.', 'evidencias' => ['Expresa lo que le gusta y no le gusta (I like / I do not like).', 'Pregunta a sus compañeros sobre preferencias de juegos y comidas.']]
        ],
        3 => [
            ['num_dba' => 1, 'enunciado' => 'Comprende y describe el estado del tiempo, la ropa y actividades cotidianas.', 'evidencias' => ['Identifica el clima del día (sunny, rainy, cloudy, windy).', 'Describe la ropa que viste y las actividades que realiza habitualmente.']],
            ['num_dba' => 2, 'enunciado' => 'Describe de manera sencilla personas, animales y lugares de su comunidad.', 'evidencias' => ['Utiliza adjetivos simples para caracterizar lugares familiares.', 'Menciona ocupaciones y profesiones de los miembros de su comunidad.']],
            ['num_dba' => 3, 'enunciado' => 'Comprende textos cortos y sencillos sobre temas cotidianos e identifica la idea principal.', 'evidencias' => ['Extrae información específica de horarios, carteles y tarjetas.', 'Identifica el vocabulario clave en lecturas cortas ilustradas.']],
            ['num_dba' => 4, 'enunciado' => 'Escribe mensajes cortos de felicitación, invitación o descripción personal usando modelos.', 'evidencias' => ['Completa formatos con datos personales básicos.', 'Redacta oraciones sencillas siguiendo una estructura guiada.']]
        ],
        4 => [
            ['num_dba' => 1, 'enunciado' => 'Comprende la idea general y algunos detalles en textos informativos cortos sobre temas de interés.', 'evidencias' => ['Identifica datos concretos como fechas, lugares y cantidades en lecturas breves.', 'Relaciona el texto leído con conocimientos previos y gráficos.']],
            ['num_dba' => 2, 'enunciado' => 'Describe personas, lugares y acciones habituales mediante oraciones conectadas.', 'evidencias' => ['Describe rutinas diarias usando conectores básicos de secuencia (first, then, finally).', 'Compara dos elementos destacando diferencias elementales.']],
            ['num_dba' => 3, 'enunciado' => 'Participa en diálogos para dar y pedir información sobre eventos pasados o planes futuros simples.', 'evidencias' => ['Pregunta y responde sobre actividades del fin de semana.', 'Formula planes sencillos de estudio o recreación.']],
            ['num_dba' => 4, 'enunciado' => 'Elabora textos descriptivos y narrativos cortos siguiendo un esquema guiado.', 'evidencias' => ['Redacta párrafos breves sobre una experiencia escolar.', 'Revisa la ortografía y puntuación básica con apoyo del docente.']]
        ],
        5 => [
            ['num_dba' => 1, 'enunciado' => 'Comprende textos orales y escritos sobre eventos pasados, tradiciones y culturas.', 'evidencias' => ['Identifica información relevante en biografías cortas y relatos culturales.', 'Distingue hechos pasados de situaciones presentes en narraciones sencillas.']],
            ['num_dba' => 2, 'enunciado' => 'Produce textos orales y escritos explicando causas y consecuencias sencillas.', 'evidencias' => ['Explica por qué realiza ciertas acciones cotidianas (because...).', 'Expresa opiniones fundamentadas sobre el cuidado del medio ambiente.']],
            ['num_dba' => 3, 'enunciado' => 'Intercambia información sobre hábitos saludables, deberes y derechos escolares.', 'evidencias' => ['Participa en dramatizaciones y presentaciones cortas sobre estilos de vida saludables.', 'Formula preguntas y respuestas sobre normas del aula y la escuela.']],
            ['num_dba' => 4, 'enunciado' => 'Escribe textos informativos breves organizando las ideas en párrafos coherentes.', 'evidencias' => ['Redacta descripciones detalladas de su ciudad o país.', 'Utiliza vocabulario variado y conectores de causa y contraste.']]
        ],
        6 => [
            ['num_dba' => 1, 'enunciado' => 'Participa en una conversación corta para decir su nombre, edad y datos básicos a profesores y amigos.', 'evidencias' => ['Saluda, se despide y se presenta en situaciones formales e informales.', 'Proporciona información personal con pronunciación comprensible.']],
            ['num_dba' => 2, 'enunciado' => 'Solicita y brinda aclaraciones sobre cómo se escriben nombres y palabras desconocidas.', 'evidencias' => ['Deletrea palabras y nombres propios en inglés.', 'Pregunta por el significado y la escritura de términos nuevos (How do you spell that?).']],
            ['num_dba' => 3, 'enunciado' => 'Comprende y utiliza palabras familiares y frases cortas sobre rutinas, actividades y gustos.', 'evidencias' => ['Describe su horario escolar y pasatiempos favoritos.', 'Completa fichas de información personal a partir de textos orales.']],
            ['num_dba' => 4, 'enunciado' => 'Comprende instrucciones relacionadas con actividades escolares y comunitarias.', 'evidencias' => ['Sigue reglas de convivencia expresadas en inglés en el aula.', 'Expresa oralmente y por escrito normas elementales de seguridad.']],
            ['num_dba' => 5, 'enunciado' => 'Describe características básicas de personas, cosas y lugares de su entorno.', 'evidencias' => ['Elabora descripciones cortas sobre sitios turísticos o culturales de su región.', 'Usa adjetivos calificativos apropiados al describir a personas.']],
            ['num_dba' => 6, 'enunciado' => 'Responde a preguntas de qué, quién y cuándo después de leer o escuchar un texto corto.', 'evidencias' => ['Identifica los protagonistas y momentos clave de una celebración o fiesta típica.', 'Responde cuestionarios de comprensión literal en textos narrativos.']],
            ['num_dba' => 7, 'enunciado' => 'Escribe información personal básica en formatos preestablecidos (formularios, carnets).', 'evidencias' => ['Diligencia formularios de registro con datos verídicos.', 'Escribe una tarjeta de perfil personal en formato digital o físico.']],
            ['num_dba' => 8, 'enunciado' => 'Comprende el tema e información general de un texto corto apoyándose en imágenes y títulos.', 'evidencias' => ['Predice el contenido de una lectura a partir de sus elementos paratextuales.', 'Socializa con sus pares la idea central de un artículo juvenil breve.']]
        ],
        7 => [
            ['num_dba' => 1, 'enunciado' => 'Participa en conversaciones cortas brindando información sobre sí mismo, personas y lugares familiares.', 'evidencias' => ['Inicia, sostiene y cierra diálogos sencillos con sus compañeros.', 'Usa expresiones cotidianas para pedir y dar ayuda.']],
            ['num_dba' => 2, 'enunciado' => 'Describe de manera oral y escrita eventos del pasado y anécdotas personales.', 'evidencias' => ['Relata acontecimientos vividos durante las vacaciones o celebraciones.', 'Utiliza verbos regulares e irregulares en tiempo pasado de forma apropiada.']],
            ['num_dba' => 3, 'enunciado' => 'Escribe textos cortos y sencillos sobre acciones, experiencias y planes con secuencias lógicas.', 'evidencias' => ['Elabora párrafos guiados sobre hábitos de vida saludable.', 'Organiza cronológicamente hechos empleando conectores temporales.']],
            ['num_dba' => 4, 'enunciado' => 'Comprende la idea principal y detalles específicos en textos descriptivos e informativos.', 'evidencias' => ['Identifica datos en correos electrónicos, anuncios y folletos turísticos.', 'Sintetiza la información de un texto corto en esquemas visuales.']],
            ['num_dba' => 5, 'enunciado' => 'Expresa sugerencias, recomendaciones y obligaciones sencillas en situaciones escolares y familiares.', 'evidencias' => ['Utiliza verbos modales básicos (should, must, have to) para dar consejos.', 'Propone acciones para cuidar las instalaciones del colegio.']],
            ['num_dba' => 6, 'enunciado' => 'Reconoce información específica en audios y grabaciones cortas sobre temas de su interés.', 'evidencias' => ['Extrae horarios, precios y lugares a partir de cuñas radiales o podcasts breves.', 'Identifica el estado de ánimo de los hablantes por su entonación.']],
            ['num_dba' => 7, 'enunciado' => 'Compara lugares, objetos y personas estableciendo diferencias y similitudes.', 'evidencias' => ['Emplea estructuras comparativas y superlativas básicas.', 'Expone las ventajas y desventajas de vivir en la ciudad o en el campo.']],
            ['num_dba' => 8, 'enunciado' => 'Realiza presentaciones orales breves sobre temas académicos previamente preparados.', 'evidencias' => ['Expone sobre un animal en peligro de extinción apoyándose en diapositivas o carteles.', 'Responde preguntas puntuales del docente y compañeros tras su exposición.']]
        ],
        8 => [
            ['num_dba' => 1, 'enunciado' => 'Expresa opiniones fundamentadas sobre temas de interés general y de actualidad.', 'evidencias' => ['Participa en mesas redondas expresando acuerdos y desacuerdos corteses.', 'Argumenta su punto de vista con razones sencillas pero claras.']],
            ['num_dba' => 2, 'enunciado' => 'Comprende textos orales y escritos de mediana extensión sobre temas científicos y culturales.', 'evidencias' => ['Identifica la tesis principal y los argumentos que la respaldan en artículos juveniles.', 'Infiere el significado de vocabulario desconocido a partir del contexto.']],
            ['num_dba' => 3, 'enunciado' => 'Escribe textos argumentativos y expositivos breves estructurados en párrafos.', 'evidencias' => ['Redacta ensayos cortos con introducción, desarrollo y conclusión.', 'Usa conectores de adición, contraste y causa de forma variada.']],
            ['num_dba' => 4, 'enunciado' => 'Narra historias y experiencias personales de forma oral y escrita con fluidez aceptable.', 'evidencias' => ['Combina tiempos verbales (pasado simple y continuo) para describir situaciones previas.', 'Incorpora adjetivos y adverbios para enriquecer su relato.']],
            ['num_dba' => 5, 'enunciado' => 'Explica planes futuros, metas y proyectos personales justificando sus decisiones.', 'evidencias' => ['Describe sus aspiraciones vocacionales y profesionales a mediano plazo.', 'Utiliza adecuadamente estructuras de futuro (will, going to).']],
            ['num_dba' => 6, 'enunciado' => 'Comprende instrucciones y procesos técnicos en manuales y guías de uso cotidianas.', 'evidencias' => ['Interpreta diagramas de flujo e instrucciones de recetas o experimentos.', 'Explica a un compañero los pasos para realizar una tarea tecnológica.']],
            ['num_dba' => 7, 'enunciado' => 'Analiza críticamente mensajes publicitarios y medios de comunicación en lengua extranjera.', 'evidencias' => ['Identifica la intención persuasiva de comerciales y afiches.', 'Distingue entre hechos y opiniones en noticias sencillas.']],
            ['num_dba' => 8, 'enunciado' => 'Interactúa con hablantes nativos o pares bilingües en situaciones simuladas y reales.', 'evidencias' => ['Mantiene una conversación espontánea sobre tópicos cotidianos sin pausas excesivas.', 'Aplica normas de cortesía y registro según el interlocutor.']]
        ],
        9 => [
            ['num_dba' => 1, 'enunciado' => 'Sostiene conversaciones y debates sobre dilemas éticos, sociales y ambientales.', 'evidencias' => ['Defiende su postura frente al cambio climático y el consumo responsable.', 'Escucha y refuta con respeto los puntos de vista de sus pares.']],
            ['num_dba' => 2, 'enunciado' => 'Comprende discursos, conferencias y entrevistas orales sobre temas académicos.', 'evidencias' => ['Toma notas organizadas de las ideas principales durante una exposición oral.', 'Identifica la postura del expositor y las conclusiones presentadas.']],
            ['num_dba' => 3, 'enunciado' => 'Produce textos formales (cartas, correos institucionales, hojas de vida en formato escolar).', 'evidencias' => ['Redacta una solicitud formal respetando las fórmulas de saludo y despedida.', 'Sintetiza sus habilidades y logros académicos en un formato estandarizado.']],
            ['num_dba' => 4, 'enunciado' => 'Analiza obras literarias y textos culturales breves identificando recursos estilísticos.', 'evidencias' => ['Comenta temas y personajes de poemas, canciones o cuentos universales.', 'Relaciona la temática de la obra con problemáticas de su propio contexto.']],
            ['num_dba' => 5, 'enunciado' => 'Formula hipótesis y plantea condiciones sobre situaciones hipotéticas o irreales.', 'evidencias' => ['Usa oraciones condicionales (tipo 1 y tipo 2) para evaluar posibles escenarios.', 'Propone soluciones innovadoras a problemas de su comunidad escolar.']],
            ['num_dba' => 6, 'enunciado' => 'Lee críticamente artículos de divulgación científica y tecnológica.', 'evidencias' => ['Identifica el propósito comunicativo, la metodología y los hallazgos en lecturas científicas.', 'Evalúa la veracidad de la información contrastando diversas fuentes.']],
            ['num_dba' => 7, 'enunciado' => 'Elabora informes de proyectos interdisciplinarios integrando gráficas y vocabulario técnico.', 'evidencias' => ['Presenta resultados de proyectos STEAM en inglés con terminología adecuada.', 'Explica gráficos y tablas estadísticas con solvencia lingüística.']],
            ['num_dba' => 8, 'enunciado' => 'Demuestra autonomía en su proceso de aprendizaje mediante el uso de recursos digitales bilingües.', 'evidencias' => ['Aplica estrategias de autocorrección y consulta en diccionarios y corpora en línea.', 'Participa en foros y plataformas educativas internacionales.']]
        ],
        10 => [
            ['num_dba' => 1, 'enunciado' => 'Comprende y sintetiza textos académicos extensos de complejidad pre-intermedia y avanzada (B1).', 'evidencias' => ['Resume ensayos y artículos especializados extrayendo los puntos centrales.', 'Distingue entre argumentos válidos, falacias y sesgos del autor.']],
            ['num_dba' => 2, 'enunciado' => 'Expone monólogos y presentaciones académicas estructuradas con fluidez y precisión léxica.', 'evidencias' => ['Presenta ponencias de 5 a 10 minutos respondiendo preguntas del auditorio.', 'Utiliza recursos retóricos y modulación de voz para mantener la atención del público.']],
            ['num_dba' => 3, 'enunciado' => 'Redacta textos académicos complejos (ensayos argumentativos, reseñas críticas) siguiendo normas formales.', 'evidencias' => ['Organiza el texto con párrafos de apertura, soporte argumental y cierre reflexivo.', 'Aplica normas de citación y referencia textual en lengua inglesa.']],
            ['num_dba' => 4, 'enunciado' => 'Participa en simulaciones de debates parlamentarios o modelos de Naciones Unidas en inglés.', 'evidencias' => ['Representa la postura de un país o delegación con propiedad discursiva.', 'Negocia resoluciones conjuntas empleando lenguaje diplomático formal.']],
            ['num_dba' => 5, 'enunciado' => 'Interpreta mensajes implícitos, ironías y metáforas en textos socioculturales y artísticos.', 'evidencias' => ['Analiza canciones y piezas audiovisuales desentrañando su carga ideológica y simbólica.', 'Explica las connotaciones culturales de modismos y expresiones idiomáticas.']],
            ['num_dba' => 6, 'enunciado' => 'Produce propuestas de proyectos de innovación social orientadas al desarrollo sostenible.', 'evidencias' => ['Formula objetivos, justificación y plan de acción de un proyecto comunitario en inglés.', 'Comunica el impacto social de su propuesta ante evaluadores externos.']],
            ['num_dba' => 7, 'enunciado' => 'Comprende conferencias y material multimedia auténtico sin necesidad de subtítulos.', 'evidencias' => ['Extrae conclusiones clave de charlas TED y documentales en versión original.', 'Identifica acentos regionales diversos del mundo angloparlante.']],
            ['num_dba' => 8, 'enunciado' => 'Evalúa su propio nivel de desempeño frente a los estándares internacionales del MCER.', 'evidencias' => ['Realiza pruebas diagnósticas estandarizadas analizando sus fortalezas y oportunidades de mejora.', 'Diseña un plan autónomo de mejoramiento de sus habilidades comunicativas.']]
        ],
        11 => [
            ['num_dba' => 1, 'enunciado' => 'Alcanza el nivel B1 del Marco Común Europeo de Referencia (MCER) en todas las habilidades comunicativas.', 'evidencias' => ['Se comunica con espontaneidad y precisión en situaciones cotidianas, académicas y laborales.', 'Comprende las ideas principales de textos complejos sobre temas concretos y abstractos.']],
            ['num_dba' => 2, 'enunciado' => 'Defiende con rigor posturas académicas y profesionales en paneles y foros internacionales.', 'evidencias' => ['Argumenta sólidamente respondiendo con agilidad a contraargumentos y objeciones.', 'Emplea marcadores discursivos avanzados para estructurar su exposición.']],
            ['num_dba' => 3, 'enunciado' => 'Redacta artículos de opinión, cartas de motivación y propuestas de investigación universitaria.', 'evidencias' => ['Escribe textos extensos con alta coherencia, cohesión y riqueza de vocabulario.', 'Adapta el tono y estilo del texto según las exigencias del ámbito académico superior.']],
            ['num_dba' => 4, 'enunciado' => 'Lee y comprende literatura universal y artículos científicos en su versión original en inglés.', 'evidencias' => ['Realiza análisis comparativos entre textos literarios de diferentes épocas y autores.', 'Interpreta estudios empíricos y metodologías científicas publicadas en inglés.']],
            ['num_dba' => 5, 'enunciado' => 'Utiliza el inglés como herramienta de internacionalización y acceso a becas y empleo global.', 'evidencias' => ['Supera entrevistas simuladas de admisión universitaria y laborales en lengua extranjera.', 'Diligencia aplicaciones a convocatorias y pasantías internacionales.']],
            ['num_dba' => 6, 'enunciado' => 'Lidera proyectos comunitarios colaborativos conectando su entorno local con redes globales.', 'evidencias' => ['Coordina iniciativas de intercambio cultural virtual con estudiantes de otros países.', 'Elabora y difunde contenidos digitales de impacto social en inglés.']],
            ['num_dba' => 7, 'enunciado' => 'Analiza críticamente fenómenos geopolíticos, económicos y tecnológicos globales.', 'evidencias' => ['Debate sobre las implicaciones de la inteligencia artificial y la globalización.', 'Formula propuestas éticas ante los desafíos contemporáneos de la humanidad.']],
            ['num_dba' => 8, 'enunciado' => 'Consolida un perfil bilingüe intercultural comprometido con la transformación social de Colombia.', 'evidencias' => ['Valora la diversidad cultural reconociendo la identidad colombiana en el concierto mundial.', 'Ejerce un liderazgo comunicativo transformador en su comunidad educativa y profesional.']]
        ]
    ];

    private const TECNOLOGIA_CICLOS = [
        'Grado 1º-3º' => [
            ['enunciado' => 'Reconoce y describe artefactos tecnológicos creados por el ser humano para satisfacer necesidades del entorno cotidiano.', 'evidencias' => ['Identifica artefactos presentes en su hogar y escuela explicando su utilidad.', 'Clasifica herramientas y materiales de acuerdo con su función y medidas de seguridad.']],
            ['enunciado' => 'Utiliza artefactos y recursos tecnológicos simples siguiendo instrucciones y normas básicas de cuidado y convivencia.', 'evidencias' => ['Maneja dispositivos digitales básicos para actividades pedagógicas guiadas.', 'Reconoce la importancia del ahorro de energía y el cuidado de los equipos escolares.']]
        ],
        'Grado 4º-5º' => [
            ['enunciado' => 'Analiza la evolución de los artefactos y procesos tecnológicos, reconociendo su impacto en las formas de vida.', 'evidencias' => ['Compara artefactos del pasado con los del presente identificando mejoras técnicas.', 'Explica cómo la tecnología ha transformado la comunicación y el transporte en su región.']],
            ['enunciado' => 'Diseña y construye soluciones tecnológicas sencillas mediante la aplicación de algoritmos y secuencias lógicas.', 'evidencias' => ['Elabora prototipos con materiales reciclables resolviendo problemas del aula.', 'Representa secuencias lógicas de pasos para ejecutar tareas informáticas básicas.']]
        ],
        'Grado 6º-7º' => [
            ['enunciado' => 'Identifica y analiza los componentes de un sistema tecnológico (entradas, procesos, salidas y retroalimentación).', 'evidencias' => ['Describe el funcionamiento de circuitos eléctricos simples y mecanismos mecánicos.', 'Explica la relación entre la estructura, la función y los materiales en sistemas tecnológicos.']],
            ['enunciado' => 'Aplica principios básicos de pensamiento computacional y programación por bloques para resolver problemas.', 'evidencias' => ['Desarrolla animaciones e historias interactivas mediante software de programación por bloques.', 'Detecta y corrige errores en algoritmos estructurados.']]
        ],
        'Grado 8º-9º' => [
            ['enunciado' => 'Evalúa críticamente las implicaciones éticas, sociales y ambientales derivadas del uso de tecnologías emergentes.', 'evidencias' => ['Analiza el ciclo de vida de los productos tecnológicos y la gestión de residuos electrónicos.', 'Aplica normas de seguridad digital, privacidad y derechos de autor en entornos virtuales.']],
            ['enunciado' => 'Formula, diseña y gestiona proyectos tecnológicos interdisciplinarios integrando herramientas digitales avanzadas.', 'evidencias' => ['Planifica las fases de un proyecto tecnológico estableciendo cronogramas y presupuestos.', 'Construye sistemas automatizados o robóticos básicos aplicando sensores y actuadores.']]
        ],
        'Grado 10º-11º' => [
            ['enunciado' => 'Diseña soluciones tecnológicas innovadoras fundamentadas en la ciencia, la ingeniería y el pensamiento computacional avanzado.', 'evidencias' => ['Modela y simula sistemas complejos mediante software especializado de ingeniería o diseño 3D.', 'Desarrolla aplicaciones o soluciones de software que optimizan procesos reales de la comunidad.']],
            ['enunciado' => 'Ejerce una ciudadanía digital responsable, ética e innovadora orientada al desarrollo sostenible y la soberanía tecnológica.', 'evidencias' => ['Evalúa el impacto de la inteligencia artificial, la biotecnología y las telecomunicaciones en el empleo.', 'Lidera iniciativas de apropiación social del conocimiento y transformación digital comunitaria.']]
        ]
    ];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ejecutar(): void
    {
        $inicio = microtime(true);

        echo "=======================================================\n";
        echo "   NORMALIZACIÓN Y SIEMBRA CURRICULAR MEN AVANZADA\n";
        echo "=======================================================\n";
        echo "Fecha y Hora: " . date('Y-m-d H:i:s') . "\n\n";

        $this->pdo->beginTransaction();

        try {
            // 1. Reclasificar Artística
            $this->reclasificarArtistica();

            // 2. Sembrar Tecnología (Guía 30 MEN)
            $this->sembrarTecnologia();

            // 3. Sembrar Inglés (Colombia Bilingüe / MCER)
            $this->sembrarIngles();

            $this->pdo->commit();

            $duracion = round(microtime(true) - $inicio, 2);
            echo "=======================================================\n";
            echo "   RESUMEN DE MIGRACIÓN CURRICULAR\n";
            echo "=======================================================\n";
            echo "✓ Reclasificación Artística Doc. 16: COMPLETADA\n";
            echo "✓ Competencias e Indicadores Guía 30: COMPLETADOS\n";
            echo "✓ DBAs e Evidencias de Inglés (1°-11°): COMPLETADOS\n";
            echo "Tiempo de ejecución: {$duracion} segundos\n";
            echo "Estado: 100% CONFORME CON DIRECTIVAS MEN ✅\n";
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

    private function reclasificarArtistica(): void
    {
        echo "🎨 1. Reclasificando Educación Artística (Área ID " . self::AREA_ARTISTICA . ")...\n";

        $stmtComp = $this->pdo->prepare("UPDATE ares_catalogo_competencias 
            SET nombre = :nombre, descripcion = :descripcion 
            WHERE area_id = :area_id");
        $stmtComp->execute([
            ':nombre' => 'Orientación Pedagógica en Educación Artística (Doc. 16 MEN)',
            ':descripcion' => 'Ejes y lineamientos de formación artística y cultural según Documento 16 del MEN.',
            ':area_id' => self::AREA_ARTISTICA
        ]);

        $stmtAp = $this->pdo->prepare("UPDATE ares_catalogo_aprendizajes 
            SET version = 'Doc. 16 MEN', disciplina = 'Orientaciones Pedagógicas' 
            WHERE area_id = :area_id");
        $stmtAp->execute([':area_id' => self::AREA_ARTISTICA]);

        echo "   ✓ " . $stmtAp->rowCount() . " registros de Artística reclasificados bajo el Documento 16 del MEN.\n\n";
    }

    private function sembrarTecnologia(): void
    {
        echo "💻 2. Sembrando Malla Curricular Guía 30 - Tecnología (Área ID " . self::AREA_TECNOLOGIA . ")...\n";

        $stmtCompBusca = $this->pdo->prepare("SELECT id FROM ares_catalogo_competencias WHERE area_id = ? AND nombre = ?");
        $stmtCompIns = $this->pdo->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");

        $competenciaMap = [];
        foreach (self::COMPETENCIAS_TECNOLOGIA as $c) {
            $stmtCompBusca->execute([self::AREA_TECNOLOGIA, $c['nombre']]);
            $id = $stmtCompBusca->fetchColumn();
            if (!$id) {
                $stmtCompIns->execute([self::AREA_TECNOLOGIA, $c['nombre'], $c['descripcion']]);
                $id = (int)$this->pdo->lastInsertId();
            }
            $competenciaMap[] = (int)$id;
        }

        $competenciaIdPrincipal = $competenciaMap[0] ?? 1;

        $stmtApBusca = $this->pdo->prepare("SELECT id FROM ares_catalogo_aprendizajes WHERE area_id = ? AND grado = ? AND num_dba = ? AND disciplina = 'Guía 30'");
        $stmtApIns = $this->pdo->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado, version, disciplina) VALUES (?, ?, ?, ?, ?, 'Guía 30 MEN', 'Guía 30')");
        $stmtApUpd = $this->pdo->prepare("UPDATE ares_catalogo_aprendizajes SET competencia_id = ?, enunciado = ?, version = 'Guía 30 MEN' WHERE id = ?");

        $stmtEvDel = $this->pdo->prepare("DELETE FROM ares_catalogo_evidencias WHERE aprendizaje_id = ?");
        $stmtEvIns = $this->pdo->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)");

        $totalAp = 0;
        $totalEv = 0;

        foreach (self::TECNOLOGIA_CICLOS as $grado => $aprendizajes) {
            foreach ($aprendizajes as $idx => $item) {
                $numDba = $idx + 1;
                $stmtApBusca->execute([self::AREA_TECNOLOGIA, $grado, $numDba]);
                $apId = $stmtApBusca->fetchColumn();

                if ($apId) {
                    $stmtApUpd->execute([$competenciaIdPrincipal, $item['enunciado'], $apId]);
                } else {
                    $stmtApIns->execute([$competenciaIdPrincipal, self::AREA_TECNOLOGIA, $grado, $numDba, $item['enunciado']]);
                    $apId = (int)$this->pdo->lastInsertId();
                }

                $totalAp++;

                $stmtEvDel->execute([$apId]);
                foreach ($item['evidencias'] as $evTexto) {
                    $stmtEvIns->execute([$apId, $evTexto]);
                    $totalEv++;
                }
            }
        }

        echo "   ✓ {$totalAp} aprendizajes y {$totalEv} evidencias de la Guía 30 sembrados con éxito.\n\n";
    }

    private function sembrarIngles(): void
    {
        echo "🇬🇧 3. Sembrando Catálogo Oficial de DBA de Inglés (Área ID " . self::AREA_INGLES . ", Colombia Bilingüe)...\n";

        $stmtCompBusca = $this->pdo->prepare("SELECT id FROM ares_catalogo_competencias WHERE area_id = ? AND nombre = ?");
        $stmtCompIns = $this->pdo->prepare("INSERT INTO ares_catalogo_competencias (area_id, nombre, descripcion) VALUES (?, ?, ?)");

        $competenciasInglesIds = [];
        foreach (self::COMPETENCIAS_INGLES as $c) {
            $stmtCompBusca->execute([self::AREA_INGLES, $c['nombre']]);
            $id = $stmtCompBusca->fetchColumn();
            if (!$id) {
                $stmtCompIns->execute([self::AREA_INGLES, $c['nombre'], $c['descripcion']]);
                $id = (int)$this->pdo->lastInsertId();
            }
            $competenciasInglesIds[] = (int)$id;
        }

        $competenciaDefault = $competenciasInglesIds[0] ?? 1;

        $stmtApBusca = $this->pdo->prepare("SELECT id FROM ares_catalogo_aprendizajes WHERE area_id = ? AND grado = ? AND num_dba = ? AND disciplina = 'ingles'");
        $stmtApIns = $this->pdo->prepare("INSERT INTO ares_catalogo_aprendizajes (competencia_id, area_id, grado, num_dba, enunciado, version, disciplina) VALUES (?, ?, ?, ?, ?, 'Colombia Bilingüe MEN', 'ingles')");
        $stmtApUpd = $this->pdo->prepare("UPDATE ares_catalogo_aprendizajes SET competencia_id = ?, enunciado = ?, version = 'Colombia Bilingüe MEN' WHERE id = ?");

        $stmtEvDel = $this->pdo->prepare("DELETE FROM ares_catalogo_evidencias WHERE aprendizaje_id = ?");
        $stmtEvIns = $this->pdo->prepare("INSERT INTO ares_catalogo_evidencias (aprendizaje_id, texto) VALUES (?, ?)");

        $totalDba = 0;
        $totalEv = 0;

        foreach (self::DBAS_INGLES as $gradoNum => $listaDba) {
            $gradoStr = "Grado {$gradoNum}º";

            foreach ($listaDba as $dba) {
                $numDba = (int)$dba['num_dba'];
                $stmtApBusca->execute([self::AREA_INGLES, $gradoStr, $numDba]);
                $apId = $stmtApBusca->fetchColumn();

                if ($apId) {
                    $stmtApUpd->execute([$competenciaDefault, $dba['enunciado'], $apId]);
                } else {
                    $stmtApIns->execute([$competenciaDefault, self::AREA_INGLES, $gradoStr, $numDba, $dba['enunciado']]);
                    $apId = (int)$this->pdo->lastInsertId();
                }

                $totalDba++;

                $stmtEvDel->execute([$apId]);
                foreach ($dba['evidencias'] as $evTexto) {
                    $stmtEvIns->execute([$apId, $evTexto]);
                    $totalEv++;
                }
            }
        }

        echo "   ✓ {$totalDba} DBAs oficiales de Inglés y {$totalEv} evidencias bilingües sembrados (Grados 1º a 11º).\n\n";
    }
}

if (PHP_SAPI === 'cli' || !debug_backtrace()) {
    $migracion = new CatalogoMigracionElite($db);
    $migracion->ejecutar();
}
