# 🏛️ ESPECIFICACIÓN TÉCNICA: MOTOR DE ESCALAS FLEXIBLES Y EVALUACIÓN CUALITATIVA (LETRAS)

Este documento detalla el diseño arquitectónico y de base de datos para dotar al motor de evaluación **Ares** de la capacidad de operar bajo rangos numéricos personalizados y calificaciones cualitativas (letras/descriptores). Esta especificación habilita la incursión estratégica de **Perseus** en los mercados de educación preescolar (jardines infantiles) e instituciones internacionales (IB/Americanas).

---

## ⚖️ REGLA DE ORO DE LA PERSISTENCIA
> [!IMPORTANT]
> **Inmutabilidad Matemática Subyacente:**
> Independientemente de cómo el docente ingrese la calificación (número, letra o descriptor) y de cómo se dibuje en los boletines o sábanas de notas, **el sistema siempre calculará y guardará las calificaciones de forma numérica continua** en la base de datos.
> 
> Esto garantiza el rendimiento de las consultas SQL, la consistencia matemática de los promedios ponderados y el correcto funcionamiento del motor de analítica (BI).

---

## 🧱 1. DISEÑO DE BASE DE DATOS (ESQUEMA SQL)

Para soportar escalas dinámicas y traducciones de letras a números, se incorporan dos tablas estructuradas en el motor SQLite de **Perseus**:

### Tabla A: `escala_calificaciones_config` (Configuración Global)
Almacena las reglas macro del rango de calificación del plantel educativo.

```sql
CREATE TABLE IF NOT EXISTS escala_calificaciones_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_escala TEXT NOT NULL,          -- Ej. "Escala Tradicional", "SIEE Preescolar", "Escala Bilingüe A-F"
    nota_minima REAL NOT NULL,             -- Ej. 0.0 o 1.0 o 0.0
    nota_maxima REAL NOT NULL,             -- Ej. 5.0 o 10.0 o 100.0
    nota_aprobacion REAL NOT NULL,         -- Ej. 3.0 o 6.0 o 60.0
    tipo_entrada TEXT CHECK(tipo_entrada IN ('numero', 'letra')) NOT NULL,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Tabla B: `escala_cualitativa_equivalencias` (Mapa de Traducción)
Define la equivalencia numérica y el rango de cada letra o juicio valorativo.

```sql
CREATE TABLE IF NOT EXISTS escala_cualitativa_equivalencias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    escala_config_id INTEGER NOT NULL,
    letra TEXT NOT NULL,                   -- Ej. "S", "A", "B", "I" o "A+", "A", "B", "C", "F"
    rango_minimo REAL NOT NULL,            -- Límite inferior para traducción de visualización (Ej. 80.0)
    rango_maximo REAL NOT NULL,            -- Límite superior para traducción de visualización (Ej. 89.9)
    valor_equivalente_entrada REAL NOT NULL, -- Nota numérica exacta que se guardará si el docente digita esta letra (Ej. 85.0)
    desempeno_nacional TEXT CHECK(desempeno_nacional IN ('SUPERIOR', 'ALTO', 'BASICO', 'BAJO')) NOT NULL, -- Decreto 1290
    FOREIGN KEY (escala_config_id) REFERENCES escala_calificaciones_config(id) ON DELETE CASCADE
);
```

---

## ⚙️ 2. EL TRADUCTOR DE NOTAS (HEFESTO GRADE TRANSLATOR)

El backend de **Perseus** implementará una clase auxiliar de PSR-12 para realizar traducciones en caliente de forma quirúrgica:

```php
declare(strict_types=1);

namespace Perseus\Grading;

use PDO;
use Exception;

class HefestoGradeTranslator {
    private PDO $db;
    private int $escalaId;
    private array $equivalencias = [];
    private array $config = [];

    public function __construct(PDO $db, int $escalaId) {
        $this->db = $db;
        $this->escalaId = $escalaId;
        $this->cargarConfiguracion();
    }

    private function cargarConfiguracion(): void {
        // Cargar parámetros macro
        $stmt = $this->db->prepare("SELECT * FROM escala_calificaciones_config WHERE id = ?");
        $stmt->execute([$this->escalaId]);
        $this->config = $stmt->fetch() ?: [];

        // Cargar mapa de equivalencias
        $stmt = $this->db->prepare("SELECT * FROM escala_cualitativa_equivalencias WHERE escala_config_id = ?");
        $stmt->execute([$this->escalaId]);
        $this->equivalencias = $stmt->fetchAll() ?: [];
    }

    /**
     * TRADUCCIÓN DE ENTRADA: Letra a Número
     * Convierte la letra ingresada por el docente al valor numérico correspondiente para persistir en BD.
     */
    public function letraANumero(string $letra): float {
        if ($this->config['tipo_entrada'] === 'numero') {
            return (float)$letra;
        }

        $letraClean = strtoupper(trim($letra));
        foreach ($this->equivalencias as $eq) {
            if ($eq['letra'] === $letraClean) {
                return (float)$eq['valor_equivalente_entrada'];
            }
        }

        throw new Exception("❌ Letra de calificación no permitida en la escala configurada: " . $letra);
    }

    /**
     * TRADUCCIÓN DE SALIDA: Número a Letra
     * Convierte el promedio numérico acumulado en la base de datos a su representación en letra para la UI/Boletín.
     */
    public function numeroALetra(float $notaNum): string {
        if ($this->config['tipo_entrada'] === 'numero') {
            return number_format($notaNum, 2);
        }

        foreach ($this->equivalencias as $eq) {
            if ($notaNum >= $eq['rango_minimo'] && $notaNum <= $eq['rango_maximo']) {
                return $eq['letra'];
            }
        }

        return "N/A";
    }
}
```

---

## 🎨 3. INTERFAZ Y CONTROL VISUAL (ESTÁNDAR VITRINA 06)

Cuando la institución configura una escala de tipo `letra` (cualitativa), la sábana de notas *Ares* debe mutar su control de entrada de datos para garantizar una usabilidad impecable:

1.  **Bloqueo de Teclado Arbitrario:** El input de calificación en la celda no permitirá ingresar texto libre para evitar que el docente digite letras inexistentes.
2.  **Selector Maestro de 44px:** Al dar doble clic en la celda de la sábana de notas, esta se transformará en un select dinámico o un menú dropdown flotante con las letras autorizadas de la escala.
    *   **Geometría:** Altura de **44px** obligatoria para el selector interactivo.
    *   **Bordes:** Radios de prestigio de **12px** en el dropdown y **24px** en los paneles de equivalencia.
    *   **Seda:** Transiciones fluidas con `cubic-bezier(0.4, 0, 0.2, 1)`.
    *   **Variables CSS:** Colores configurados con variables `var(--el-primary)` y matices HSL para los fondos de los rangos (ej: verde suave para Excelente, rojo suave para Insuficiente).

---

## 🏛️ 4. ESTRATEGIA DE MERCADO: LA CONQUISTA DEL PREESCOLAR
*El nicho más desatendido y altamente rentable.*

Los jardines infantiles y colegios de transición tienen una particularidad pedagógica y legal: **tienen prohibido calificar por números**. Sus boletines e informes deben ser netamente descriptivos y cualitativos.

### ¿Por qué Perseus ganará este mercado con esta arquitectura?
1.  **Simplicidad para las Docentes:** Las maestras de preescolar suelen estar abrumadas con la redacción de descriptores extensos. En Perseus, solo seleccionan la valoración rápida (*Superior, Alto, Básico, Bajo*) en su tablet o dispositivo móvil, y el sistema traduce internamente los rangos matemáticos para generar los boletines automáticos.
2.  **El "Puente de Transición":** Muchos colegios K-12 sufren porque tienen un sistema para primaria/bachillerato (numérico) y deben llevar el preescolar en hojas de cálculo de Excel porque el software no soporta ambos. **Perseus permite configurar múltiples escalas dentro del mismo plantel**:
    *   Grados Transición y Jardín ➔ Asociados a la escala `Cualitativa Preescolar` (Letras).
    *   Grados Primero a Once ➔ Asociados a la escala `Tradicional Numérica 0.0 a 5.0`.
    *   **Resultado:** Consolidación total de la institución en una sola base de datos Perseus limpia y soberana.

---

## 🛡️ 5. CONTROL DE INTEGRIDAD Y SEGURIDAD MÁXIMA
*   **Bloqueo en Caliente de la Escala:** El sistema inyectará un trigger en la base de datos o validación en PHP que verifique: `SELECT COUNT(*) FROM calificaciones`. Si el conteo es mayor a `0` (ya existe al menos una nota calificada por un docente en el año lectivo), el endpoint de administración de la escala bloqueará cualquier `UPDATE` en los campos `nota_minima`, `nota_maxima` y `nota_aprobacion`, arrojando una excepción controlada.
*   **Protección contra Hackeos en AJAX:** El endpoint de guardado de notas asíncrono siempre re-validará en el servidor el valor que intenta inyectar el docente, verificando que la nota numérica resultante esté estrictamente dentro de los rangos legales de la escala del año vigente (`nota_minima` y `nota_maxima`).
