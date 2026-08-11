# ¿QUÉ SON LAS FASES Y POR QUÉ LAS HACEMOS?

**Fecha:** 2026-08-10  
**Audiencia:** Cualquiera que necesite entender la estrategia de remediación

---

## 🎯 CONCEPTO BÁSICO

**Las fases son "tranches" de trabajo que dividimos por:**
1. **Criticidad** (qué puede quebrar el sistema)
2. **Tiempo** (cuánto tarda arreglarlo)
3. **Riesgo** (qué pasa si NO lo hacemos ahora)
4. **Dependencias** (qué necesita estar hecho antes)

Es como una **construcción de casa:**
- Fase 1: Cerrar los agujeros del techo (URGENTE — lluvia entra)
- Fase 2: Arreglar las ventanas rotas (IMPORTANTE — se pierde calor)
- Fase 3: Pintar las paredes (MEJOR — pero no es urgente)
- Fase 4: Instalar sistemas de detección (PREVENCIÓN — para no volver a tener problemas)

---

## 🔴 FASE 1: CRÍTICO (48 HORAS)

### ¿Qué es?
Es el trabajo que **NO PUEDE ESPERAR**. Son vulnerabilidades que:
- **Te cierran el negocio** si ocurren
- **Te cuesta dinero** (multas, pérdida de confianza)
- **Son ilegales** (GDPR, LOPD)
- **Afectan menores de edad** (crítico educativo)

### Las 2 tareas de Fase 1:

#### Tarea 1: `.env` para credenciales

**¿Qué es el problema?**
```php
// EN: php/db.php (líneas 7-10)
$host = 'localhost';
$user = 'root';  // ← EXPUESTO EN GITHUB
$pass = '';      // ← CUALQUIERA PUEDE ROBARLO
```

**¿Por qué es crítico?**
- Si tu repo se filtra (GitHub hackeado, empleado disconforme, etc.)
- Cualquiera obtiene acceso a la BD MariaDB
- Acceso a datos de **MENORES DE EDAD**
- Multa GDPR: €20k-50k
- Cierre de institución
- Responsabilidad legal personal

**¿Cómo lo arreglamos?**
```php
// Después
$host = $_ENV['DB_HOST'];  // Lee desde .env (NO en código)
$user = $_ENV['DB_USER'];  // Seguro, no está en GitHub
$pass = $_ENV['DB_PASS'];  // Variable de entorno
```

**¿Por qué es "FASE 1" y no "FASE 4"?**
Porque si alguien te hackea GitHub hoy, todo se cae. Ahora. Antes de que termines las otras fases.

---

#### Tarea 2: Auditar dead code

**¿Qué es el problema?**
```
php/logica/
├── check_ares.php          ← ¿Se usa? ¿No se usa?
├── check_estudiantes.php   ← Incompleto
├── purga_vuelo_total.php   ← ¿Debo ejecutarlo?
├── migracion_elite_v3.php  ← ¿Es antiguo?
└── ... (25 archivos más)
```

**¿Por qué es crítico?**
- Alguien ejecuta `check_ares.php` en producción por error
- Script viejo borra estudiantes
- Datos inconsistentes
- Debugging pesadilla

**¿Cómo lo arreglamos?**
```bash
# Auditamos: ¿se usa este archivo en el código?
grep -r "check_ares" php/logica --include="*.php"

# Si dice "0 referencias" → es muerto
# Si dice "5 referencias" → CRÍTICO, mantener
```

**¿Por qué es "FASE 1"?**
Porque mientras no lo audites, vives en incertidumbre. Alguien podría ejecutar un script legacy que cause daño. Mejor saber QUÉ scripts son seguros usar.

---

### ¿Por qué 48 HORAS para Fase 1?

```
Si NO haces Fase 1:
─────────────────
Hoy (2026-08-10):  Sistema funciona, pero credenciales en código
Mañana:            Empleado disconforme filtra repo en GitHub
Semana 1:          Hacker entra, obtiene BD completa
Semana 2:          Noticia: "Millones de estudiantes comprometidos"
Semana 3:          Multa GDPR: €50k-100k
Mes 2:             Institución se cierra

SI haces Fase 1:
──────────────
Hoy:               2 horas de trabajo
Mañana:            Credenciales ya NO están en código
Hacker intenta:    No puede entrar (credenciales no están)
Mes 1-2:           Sigue operando sin sobresaltos
```

**TL;DR:** Si esperas a "tener tiempo", quizá sea demasiado tarde. 2 horas ahora = evitar €100k de pérdidas después.

---

---

## 🟡 FASE 2: ALTO (1-2 SEMANAS DESPUÉS)

### ¿Qué es?
Vulnerabilidades que **NO son inmediatas pero crecen con el tiempo**. Son como "goteras en la pared" — pequeñas ahora, pero si llueve fuerte, inundan.

### Las 2 tareas de Fase 2:

#### Tarea 1: Reemplazar `→query()` con `prepare()`

**¿Qué es el problema?**
```php
// ACTUAL (13 archivos)
$stmt = $db->query("SELECT * FROM tabla WHERE activo = 1");
// Hoy: SEGURO (no hay variables)
// Mañana: Si alguien refactoriza y mete variable → SQL INJECTION
```

**¿Por qué es ALTO pero no CRÍTICO?**
- Hoy: Safe (queries estáticas)
- Pero viola regla CLAUDE.md
- Y es una **bomba de tiempo** si el código evoluciona

**Ejemplo del riesgo:**
```php
// Alguien refactoriza y NO respeta la regla:
$id = $_GET['id'];
$stmt = $db->query("SELECT * FROM tabla WHERE id = " . $id);  // ← SQL INJECTION
```

Atacante hace: `?id=1 OR 1=1` → obtiene TODO  
Atacante hace: `?id=1; DROP TABLE estudiantes;` → BD destruida

**¿Por qué es "FASE 2" y no "FASE 1"?**
Porque hoy NO duele. Pero en 6 meses cuando alguien refactorice, duele MUCHO. Mejor arreglar ahora preventivamente.

---

#### Tarea 2: Eliminar `window.location.reload()`

**¿Qué es el problema?**
```javascript
// ACTUAL (5 instancias)
fetch('/guardar.php').then(data => {
    window.location.reload();  // ← RECARGA TODO
});

// Problemas:
// 1. Sesión se regenera → timing attack
// 2. Usuario pierde contexto → bad UX
// 3. Performance pobre → carga toda página
```

**Ejemplo del riesgo:**
```
Usuario está haciendo examen:
  Responde pregunta 1... Responde pregunta 2...
  Presiona "guardar"
  → reload() recarga página
  → pierde TODAS las respuestas
  → "Se me borraron mis respuestas!"
  → reclamo legal
```

**¿Por qué es "FASE 2" y no "FASE 1"?**
Porque es UX/seguridad, no "cierre de operaciones". Pero duele bastante (estudiantes pierden data).

---

### ¿Por qué 1-2 SEMANAS para Fase 2?

```
Si NO haces Fase 2:
──────────────────
Mes 1:  Desarrollador refactoriza código, mete variable en query
Mes 2:  Hacker prueba endpoints, encuentra uno vulnerable
Mes 3:  Hacker inyecta SQL, obtiene datos de todos los estudiantes
Mes 4:  Auditoría externa descubre → Multa + cierre

Si haces Fase 2:
───────────────
Semana 1-2:  7-10 horas de trabajo arreglando queries
Semana 3:    Código es 100% seguro, preparado para evolución
Año 1-5:     Puedes refactorizar sin miedo a introducir SQL injection
```

**TL;DR:** Arreglar AHORA = protección a largo plazo.

---

---

## 🟢 FASE 3: MEDIO (3-4 SEMANAS DESPUÉS)

### ¿Qué es?
**Deuda técnica menor.** Cosas que funcionan, pero son feas o inconsistentes. Como tener un auto que funciona pero necesita una pintura.

### Las 2 tareas de Fase 3:

#### Tarea 1: `var` → `const`/`let`

**¿Qué es el problema?**
```javascript
// ACTUAL (4 instancias)
var quill;
var tiposPregunta = [];

// Problema: var tiene "scope global" → puede colisionar
```

**Ejemplo del riesgo (raro pero posible):**
```javascript
// En ares_editor.js
var quill;

// En otro módulo cargado después
var quill = "string";  // ← SOBRESCRIBIÓ la variable

// Ahora quill es string, no el objeto Quill
// Código se quiebra: quill.enable() → error
```

**¿Por qué es MEDIO y no CRÍTICO?**
- Ocurre raramente
- Pero cuando ocurre, bug es muy raro de debuguear
- Mejor arreglarlo AHORA

---

#### Tarea 2: `.then()` → `async/await`

**¿Qué es el problema?**
```javascript
// ACTUAL (5 instancias)
Swal.fire({...}).then((result) => {
    if (result.isConfirmed) { ... }
});

// Problema: Inconsistencia con FRONTEND_MANIFESTO (async/await obligatorio)
```

**¿Por qué es MEDIO?**
- Funciona OK
- Es inconsistente con resto del código
- Hace mantenimiento más difícil ("¿por qué este usa .then() y los otros async/await?")

---

### ¿Por qué 3-4 SEMANAS para Fase 3?

```
Si NO haces Fase 3:
──────────────────
Años 1-5:  Código funciona, pero:
           - Tiene inconsistencias (.then() vs async/await)
           - Tiene riesgos de scope (var)
           - Más difícil mantener

Si haces Fase 3:
────────────────
Semana 3-4:  2-3 horas limpiando deuda técnica
Años 1-5:    Código limpio, consistente, fácil mantener
             Nuevos devs entienden las reglas al tiro
```

**TL;DR:** No es urgente, pero mejora calidad a largo plazo.

---

---

## 🛡️ FASE 4: PREVENCIÓN (CONTINUO)

### ¿Qué es?
**Automatización para NUNCA VOLVER a estos errores.** Es como instalar un sistema de alarma después de que te roba alguien.

### Las 4 tareas de Fase 4:

#### Tarea 1: Pre-commit hooks

**¿Qué es?**
Un script que **BLOQUEA tu commit** si violás las reglas.

**Ejemplo:**
```bash
$ git commit -m "agregar feature"

# Git ejecuta pre-commit hook automáticamente
> 🔍 Validando CLAUDE.md compliance...
> ❌ ERROR: Encontré →query() sin prepared statements
> Commit RECHAZADO
```

**¿Por qué es importante?**
- Previene que alguien introduzca `→query()` "por accidente"
- Educación automática ("ah, no se puede hacer esto")
- No requiere code review para detectarlo

---

#### Tarea 2: Auditoría semanal

**¿Qué es?**
Un script que **cada semana** te reporta:
```
📊 AUDITORÍA SEMANAL
✅ PASS: 0 instancias de →query()
✅ PASS: 0 instancias de var
✅ PASS: 0 window.location.reload()
❌ FAIL: 2 archivos sin declare(strict_types=1)
```

**¿Por qué es importante?**
- Early warning: si algo se cuela, lo sabes en 7 días
- No esperas 6 meses a descubrir un problema

---

#### Tarea 3: Code review checklist

**¿Qué es?**
Una lista de verificación que **revisor debe completar** antes de aprobar PR:
```markdown
## ✅ SEGURIDAD
- [ ] No hay →query() sin prepare()
- [ ] CSRF token validado
- [ ] No hay credenciales hardcodeadas
```

**¿Por qué es importante?**
- Estandariza cómo revisamos código
- Nadie "se olvida" de revisar seguridad
- Es documentación de "qué es aceptable"

---

#### Tarea 4: Guía buenas prácticas

**¿Qué es?**
Un documento que dice:
```
❌ NO HAGAS ESTO
$db->query("SELECT * FROM tabla WHERE id = " . $_POST['id']);

✅ HAZ ESTO
$stmt = $db->prepare("SELECT * FROM tabla WHERE id = :id");
$stmt->execute([':id' => $_POST['id']]);
```

**¿Por qué es importante?**
- Referencia rápida para devs nuevos
- "No tengo que descifrar CLAUDE.md, aquí está el patrón"
- Reduce fricción ("¿cómo hago un endpoint seguro?")

---

### ¿Por qué FASE 4 es CONTINUO?

```
Sin Fase 4:
──────────
Mes 1-2:  Haces Fase 1-3 bien
Mes 3:    Nuevo dev llega, agrega →query() sin saber
Mes 4:    SQL injection introducida sin que nadie lo note
Mes 6:    Audit encuentra, tienes que arreglar TODO DE NUEVO

Con Fase 4:
───────────
Mes 1-2:  Haces Fase 1-3
Mes 3:    Pre-commit hook rechaza el commit del nuevo dev
Mes 3 (minutos después):  Dev lee el error, aprende la regla
Mes 3+:   Nunca vuelve a intentar →query()
```

**TL;DR:** Sin prevención, terminas en un ciclo infinito de "arreglar lo mismo".

---

---

## 📊 COMPARATIVA: ¿POR QUÉ FASES Y NO TODO JUNTO?

### Opción A: Arreglar TODO de una vez (MÁS RÁPIDO pero RIESGOSO)

```
Día 1:    Inicio. 22 horas de trabajo continuo
Días 2-3: Testing pesadilla (cambié demasiado, ¿qué rompí?)
Día 4:    Descubro que cometí error en Fase 2 mientras hacía Fase 4
Día 5:    Rollback. Vuelvo a empezar.
          
Resultado: 40+ horas de trabajo, más estresante, más errores
```

### Opción B: Fases (MÁS LENTO pero SEGURO y ORDENADO)

```
Día 1 (2h):     Fase 1 completa. Testing rápido: funciona.
Día 2:          Descansa. Sientes que avanzaste.
Día 8 (8h):     Fase 2 completa. Testing: funciona.
Día 9-14:       Descansa. Otra vez descubres si rompiste algo.
Día 21 (3h):    Fase 3 completa. Testing: funciona.
Día 22-27:      Descansa.
Día 28-30 (5h): Fase 4 (prevención) implementada.

Resultado: 22 horas de trabajo, menos estresante, sin sorpresas
```

**La diferencia:**
- **Opción A:** Rápido pero frágil (riesgo de rollback)
- **Opción B:** Gradual pero sólido (cada fase es validada)

---

---

## 🎯 RESUMEN: ¿QUÉ SON Y POR QUÉ?

### Las 4 Fases son una ESTRATEGIA DE REMEDIACIÓN

| Fase | Qué | Por qué | Cuándo | Riesgo si no |
|------|-----|--------|--------|-------------|
| **1. CRÍTICO** | Credenciales + dead code | ILEGAL en GDPR | **HOY** | Multa + cierre |
| **2. ALTO** | Queries + reload | Vulnerabilidades latentes | Próximas 2 sem | SQL injection |
| **3. MEDIO** | var + await | Deuda técnica | Próximas 3 sem | Bugs impredecibles |
| **4. PREVENCIÓN** | Hooks + auditoría | Evitar reincidencia | Continuo | Volver a Fase 1 |

### ¿Por qué NO todas juntas?

1. **Cognitivamente difícil:** 22 horas de cambios = muchos errores
2. **Priorización:** Si falla algo en Fase 1, cierras el negocio. Mejor arreglar eso primero.
3. **Testing:** Cada fase pequeña = testing más simple
4. **Motivación:** "Completé Fase 1!" es mejor que "trabajé 8 horas, no veo progreso"
5. **Rollback:** Si algo falla, vuelves atrás sin perder 20 horas

---

## 🚀 ANALOGÍA: CONSTRUCCIÓN DE CASA

```
FASE 1 (CRÍTICO): Techo gotea
├─ 2 horas de trabajo
├─ Cierras agujeros
└─ Lluvia no entra más

FASE 2 (ALTO): Ventanas rotas
├─ 8 horas de trabajo
├─ Reemplazas ventanas
└─ Casa más habitable

FASE 3 (MEDIO): Paredes sin pintar
├─ 3 horas de trabajo
├─ Pintas
└─ Casa se ve mejor

FASE 4 (PREVENCIÓN): Instalar alarma
├─ 5 horas de trabajo
├─ Instalas cámaras, detector de movimiento
└─ Si alguien intenta entrar, sabes al instante
```

Sin Fase 4 → dentro de 2 años, vuelve a llover y gotea (volviste a Fase 1)  
Con Fase 4 → dentro de 2 años, sistema está 100% optimizado y mantenido

---

## ✅ CONCLUSIÓN

**Las fases son una METODOLOGÍA para:**

1. ✅ Arreglar los problemas CRÍTICOS primero (no esperar es tonto)
2. ✅ Hacer cambios en tamaño pequeño (testing más fácil)
3. ✅ Educación gradual (nuevo dev aprende una regla por semana)
4. ✅ Prevención permanente (no volver a tener estos problemas)
5. ✅ Documentación clara (aquí dice qué hacer)

**Si NO haces fases:**
- Arreglas todo de una vez → frágil
- Te olvidas de algo → reincidencia
- Nuevo dev no sabe qué es importante → vuelve a quebrar

**Si haces fases:**
- Arreglas gradualmente → sólido
- Cada fase tiene validación → confianza
- Fase 4 previene futuros problemas → sustainability

---

**¿Preguntas?** Esto es una estrategia de ingeniería de software estándar. Se llama "risk prioritization" o "agile remediation". Usan las grandes empresas para mantener sistemas legacy sin quebrar en el intento.

---

Creado por: Explicación didáctica de fases  
Fecha: 2026-08-10  
Audiencia: Cualquiera que necesite entender la estrategia
