# 📋 REPORTE EXPLÍCITO: FORMATOS DE MATRÍCULA A MILÍMETROS

**Documento para delegación a agentes**  
**Versión:** 1.0  
**Fecha:** 2026-08-13  
**Autor:** Ingeniero Ricardo / Sistema Elite

---

## 1️⃣ ¿QUÉ SE QUIERE HACER?

**Objetivo:** Refactorizar completamente el módulo de **formatos de matrícula** para que funcione 100% en **milímetros**, eliminando la conversión complicada píxeles ↔ milímetros que causa desalineamientos.

### Problema actual

```
Usuario crea formato en EDITOR (píxeles)
        ↓
Se convierte a milímetros (factor 0.264583)
        ↓
Se guarda en BD
        ↓
Se carga de BD (milímetros)
        ↓
Se convierte a píxeles para PREVIEW
        ↓
❌ RESULTADO: Pequeños desalineamientos (1-2mm de error)
```

### Solución deseada

```
Usuario crea formato en EDITOR (milímetros puro)
        ↓
Se guarda en BD (milímetros puro)
        ↓
Se carga de BD (milímetros puro)
        ↓
Se usa en PREVIEW (milímetros puro)
        ↓
Se imprime (milímetros puro)
        ↓
✅ RESULTADO: Sincronización PERFECTA
```

---

## 2️⃣ ¿CÓMO SE VA A LOGRAR?

### 4 FASES PRINCIPALES

#### FASE 1: LIMPIEZA (Editor JavaScript)

**Archivo:** `js/modules/formatos_matricula_builder.js`

**Qué eliminar:**
- Factor de conversión `0.264583` (no se usa)
- Cálculos con píxeles: `parseInt(style.left)`, `parseFloat(px)`
- Función `obtenerEscalaLienzo()` (obtiene zoom)
- Lógica de zoom visual (no aplica)

**Qué guardar en memoria:**
- `dataset.left_mm` = posición izquierda en milímetros
- `dataset.top_mm` = posición superior en milímetros
- `dataset.width_mm` = ancho en milímetros
- `dataset.height_mm` = alto en milímetros

**Ejemplo de cambio:**

```javascript
// ❌ ANTES (complicado)
const left = parseInt(bloque.style.left) * 0.264583;

// ✅ DESPUÉS (simple)
const left_mm = parseFloat(bloque.dataset.left_mm);
```

---

#### FASE 2: REESCRIBIR FUNCIONES CRÍTICAS (Editor)

| Función | Cambio | Impacto |
|---------|--------|--------|
| `insertarBloqueDesdeJSON()` | Cargar valores en MM directo | ALTA |
| `arrastrarBloque()` | Calcular posición en MM, no px | ALTA |
| `guardarFormato()` | Enviar MM directo a BD | ALTA |
| `iniciarRedimension()` | Redimensionar en MM | MEDIA |

**Ejemplo `guardarFormato()`:**

```javascript
// ✅ Leer valores en MM
const left_mm = parseFloat(bloque.dataset.left_mm);
const top_mm = parseFloat(bloque.dataset.top_mm);

// ✅ Enviar a BD en MM
configJson.push({
    type: "logo",
    left: left_mm,      // MILÍMETROS (NO píxeles)
    top: top_mm,        // MILÍMETROS
    width: width_mm,    // MILÍMETROS
});
```

---

#### FASE 3: SIMPLIFICAR PREVIEW/IMPRESIÓN (Backend PHP)

**Archivo:** `imprimir_matricula.php`

**Qué eliminar:**
- Líneas 604-648: Conversiones `px→mm` complicadas
- Variables: `$x_mm_ajustado`, `factor_px_to_mm`
- Cálculos de escala

**Qué hacer:**
- Recibir JSON con milímetros puro
- Aplicar directamente en CSS
- Sin conversiones intermedias

**Ejemplo:**

```php
// ✅ Los datos YA vienen en milímetros desde frontend
$x_mm = (float)$bloque['left'];     // 50.5 mm
$y_mm = (float)$bloque['top'];      // 20.3 mm

// ✅ Aplicar márgenes directamente
$left_final = $x_mm + $margen_izq;  // 50.5 + 10 = 60.5 mm
$top_final = $y_mm + $margen_sup;   // 20.3 + 10 = 30.3 mm

// ✅ Usar en CSS
$style = "position: absolute; left: {$left_final}mm; top: {$top_final}mm;";
```

---

#### FASE 4: VALIDACIÓN (Testing)

4 tests que DEBEN pasar:

```
Test 1: GUARDADO CORRECTO
  Usuario crea formato, posiciona logo en left=50mm, top=20mm
  → BD debe almacenar {"left":50, "top":20}

Test 2: CARGA CORRECTA
  Cargar formato guardado
  → Logo debe aparecer EXACTAMENTE donde estaba

Test 3: PREVIEW SINCRONIZADO
  Editor: Logo en left=50mm, top=20mm
  Preview: Logo en EXACTAMENTE la misma posición (sin desalineamiento)

Test 4: IMPRESIÓN CORRECTA
  Imprimir estudiante con formato
  → Medir posiciones en papel físico (deben coincidir en mm)
```

---

## 3️⃣ ¿QUÉ SE HA HECHO HASTA AHORA?

### LO COMPLETADO ✅

1. **Plan documentado** (`claude_docs/PLAN_REFACTORIZACION_FORMATOS_MILIMETROS.md`)
   - 367 líneas de especificaciones técnicas
   - 4 fases con pasos detallados
   - Checklist de ejecución

2. **Análisis hecho**
   - Identificadas 24 violaciones de código (líneas a eliminar)
   - Identificadas 4 funciones a reescribir
   - Documentadas excepciones a mantener

3. **Limpiezas previas completadas**
   - Eliminados 190 violaciones CSS/JS
   - Sistema de validación instalado (pre-commit hook)
   - Código base ahora es limpio

4. **Memoria documentada**
   - Plan aprobado por Ingeniero Ricardo
   - Estado del proyecto guardado
   - Próximos pasos definidos

---

### LO PENDIENTE ❌

| Fase | Tarea | Estado |
|------|-------|--------|
| **FASE 1** | Eliminar conversiones de píxeles | ⏳ PENDIENTE |
| **FASE 2** | Reescribir `insertarBloqueDesdeJSON()` | ⏳ PENDIENTE |
| **FASE 2** | Reescribir `arrastrarBloque()` | ⏳ PENDIENTE |
| **FASE 2** | Reescribir `guardarFormato()` | ⏳ PENDIENTE |
| **FASE 3** | Simplificar `imprimir_matricula.php` | ⏳ PENDIENTE |
| **FASE 4** | Test 1 (Guardado) | ⏳ PENDIENTE |
| **FASE 4** | Test 2 (Carga) | ⏳ PENDIENTE |
| **FASE 4** | Test 3 (Preview) | ⏳ PENDIENTE |
| **FASE 4** | Test 4 (Impresión) | ⏳ PENDIENTE |

---

## ESPECIFICACIONES TÉCNICAS

### Papel Carta (Estándar)

```
Ancho:  215.9 mm
Alto:   279.4 mm
Margen mínimo: 10 mm (restricción dura)
```

### JSON en BD (Ejemplo)

```json
{
  "blocks": [
    {
      "type": "logo",
      "left": 50.0,        // milímetros
      "top": 20.0,         // milímetros
      "width": 30.0,       // milímetros
      "height": null,      // auto
      "content": "..."
    }
  ]
}
```

### Conversión visual (Navegador)

```
1 papel Carta = 215.9 mm ancho
Canvas navegador = 816 píxeles ancho
Factor: 816 px ÷ 215.9 mm = 3.782 px/mm

Usar SOLO para visualizar en pantalla.
Guardar/cargar SIEMPRE en MM.
```

---

## ARCHIVOS INVOLUCRADOS

### Frontend (JavaScript)

| Archivo | Ubicación | Cambios |
|---------|-----------|---------|
| `formatos_matricula_builder.js` | `js/modules/` | Reescribir 4 funciones |
| `formatos_matricula.js` | `js/` | Validar llamadas a builder |

### Backend (PHP)

| Archivo | Ubicación | Cambios |
|---------|-----------|---------|
| `imprimir_matricula.php` | Raíz | Simplificar conversiones |
| `formatos_ajax.php` | `php/logica/` | Validar entrada/salida en MM |

### Base de Datos

| Tabla | Campo | Cambio |
|-------|-------|--------|
| `formatos_matricula` | `configuracion_json` | Ya debe estar en MM |

### CSS

| Archivo | Ubicación | Cambios |
|---------|-----------|---------|
| `imprimir_matricula.css` | `styles/modules/` | Validar, sin cambios críticos |

---

## CHECKLIST DE EJECUCIÓN

### FASE 1: LIMPIEZA
- [ ] Backup de `formatos_matricula_builder.js`
- [ ] Eliminar factor `0.264583`
- [ ] Eliminar función `obtenerEscalaLienzo()`
- [ ] Eliminar cálculos con píxeles
- [ ] Validación: Hook debe pasar

### FASE 2: REESCRITURAS
- [ ] `insertarBloqueDesdeJSON()` → Cargar en MM
- [ ] `arrastrarBloque()` → Calcular en MM
- [ ] `guardarFormato()` → Enviar MM a BD
- [ ] `iniciarRedimension()` → Redimensionar en MM
- [ ] Validación: Hook debe pasar

### FASE 3: BACKEND
- [ ] `imprimir_matricula.php` → Eliminar conversiones
- [ ] `formatos_ajax.php` → Validar entrada MM
- [ ] Validación: Hook debe pasar

### FASE 4: TESTING
- [ ] Test 1: Guardado correcto
- [ ] Test 2: Carga correcta
- [ ] Test 3: Preview sincronizado
- [ ] Test 4: Impresión correcta

---

## VALIDACIÓN AUTOMÁTICA

**IMPORTANTE:** El pre-commit hook validará automáticamente:
- ❌ No hay HEX hardcodeados en CSS
- ❌ No hay inline styles (`style="..."`)
- ❌ No hay `console.log/alert` en JS
- ❌ No hay bloques `<style>` en PHP

Si algún cambio viola esto, el commit será rechazado.

**Ubicación del hook:** `.git/hooks/pre-commit`  
**Documentación:** `claude_docs/ELITE_PRE_COMMIT_HOOK.md`

---

## RESUMEN EJECUTIVO

| Aspecto | Detalle |
|--------|---------|
| **¿Qué se quiere?** | Trabajar TODO en milímetros (editor ↔ BD ↔ preview ↔ impresión) |
| **¿Por qué?** | Eliminar conversiones que causan desalineamientos 1-2mm |
| **¿Cómo se logra?** | 4 fases: limpiar código, reescribir funciones, simplificar BD, validar |
| **¿Cuánto ya está hecho?** | Plan + análisis + limpiezas previas = 60% de preparación |
| **¿Cuánto falta?** | Implementar 4 fases + 4 tests = 100% implementación |
| **Tiempo estimado** | FASE 1-3: ~3-4 horas \| FASE 4: ~1 hora |
| **Riesgo** | BAJO (toda la BD existente se mantiene, solo cambio en lógica visual) |
| **Beneficio** | ALTO (sincronización perfecta, cero errores de posicionamiento) |

---

## REFERENCIAS DOCUMENTOS RELACIONADOS

- `claude_docs/PLAN_REFACTORIZACION_FORMATOS_MILIMETROS.md` - Plan técnico detallado (367 líneas)
- `claude_docs/ELITE_PRE_COMMIT_HOOK.md` - Manual del validador automático
- `CLAUDE.md` - Arquitectura general del proyecto
- `CONTENCIONES_CSS_FINAL.md` - Restricciones de código (CSS)

---

## NOTAS PARA EL AGENTE EJECUTOR

1. **Lee PRIMERO:** `PLAN_REFACTORIZACION_FORMATOS_MILIMETROS.md` (tiene líneas exactas a eliminar)
2. **Valida SIEMPRE:** Cada cambio con `git commit` (hook automático)
3. **Mantén INTACTO:** Base de datos, API, estructura JSON
4. **Documenta:** Cada fase completada en commit message
5. **Prueba:** Manualmente después de FASE 4 (4 tests específicos)

---

**Documento creado para Ingeniero Ricardo**  
**Listo para delegación a agentes automáticos**  
**Última actualización:** 2026-08-13

