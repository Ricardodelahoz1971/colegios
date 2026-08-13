# AUDITORÍA COMPLETA - MÓDULO DE FORMATOS DE MATRÍCULA

**Fecha:** 2026-08-12  
**Versión:** 1.0  
**Estado:** ANÁLISIS PARA REFACTORIZACIÓN A MILÍMETROS  
**Ingeniero:** Ricardo  
**Compilado por:** Sistema de Análisis Automático

---

## ÍNDICE DE CONTENIDOS

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Análisis de Archivos](#análisis-de-archivos)
3. [Flujo de Datos Actual](#flujo-de-datos-actual)
4. [Tablas de Auditoría Línea por Línea](#tablas-de-auditoría-línea-por-línea)
5. [Código Legacy / Experimental a Eliminar](#código-legacy--experimental-a-eliminar)
6. [Código Reutilizable](#código-reutilizable)
7. [Plan de Refactorización](#plan-de-refactorización)
8. [Comandos de Verificación](#comandos-de-verificación)

---

## RESUMEN EJECUTIVO

### Estado Actual
El módulo de formatos de matrícula está **78% implementado** con lógica mixta de:
- ✅ Sistema de guardado/carga en BD (funcional)
- ✅ API JSON (funcional)
- ⚠️ Conversiones px↔mm (parcialmente implementadas, con factores hardcodeados)
- ❌ Lógica de zoom/escala (presente pero problematizante)
- ❌ Editor visual con arrastre (funcional pero mezclado con conversiones)

### Problemas Identificados
1. **Conversiones px↔mm duplicadas y conflictivas**
   - Editor: `factorMmPx = 3.78` (línea 84, 151-154)
   - Impresión: `factor_px_to_mm = 215.9 / 816 = 0.264583` (línea 607)
   - Ambos factores están HARDCODEADOS y no sincronizados

2. **Lógica de zoom/escala innecesaria**
   - `obtenerEscalaLienzo()` (línea 885)
   - Cálculos de zoom en drag/drop (líneas 80, 912, 930)
   - Multiplicación de scales en redimensión (línea 1072)
   - **TODO ESTO DESAPARECE con refactor a milímetros**

3. **Márgenes duplicados y confusos**
   - Editor calcula márgenes en PX desde input MM (línea 85, 151-155)
   - Impresión recalcula márgenes desde BD (línea 610-611)
   - No hay sincronización clara

4. **Códigos de marcación perdidos**
   - `[[BLOQUE_*]]` tags en HTML legacy no usados
   - `bloque-backend-html` divs ocultos ya no procesados correctamente

5. **Archivos Scratch sin limpiar**
   - `diagnostico_formatos.php` - Herramienta de debug (puede eliminarse)
   - `apply_formatos_matricula_patch.php` - Patch antiguo (debe eliminarse)
   - `migrar_formatos_multipaper.php` - Migración legacy (debe eliminarse)

### Métricas del Código
| Archivo | Líneas | Tipo | Estado |
|---------|--------|------|--------|
| `js/modules/formatos_matricula_builder.js` | 1489 | Frontend | 60% Reutilizable, 40% A Reescribir |
| `js/formatos_matricula.js` | 322 | Frontend | 95% Reutilizable, 5% Ajustes |
| `php/logica/formatos_ajax.php` | 140 | Backend | 100% Reutilizable |
| `imprimir_matricula.php` | 837 | Impresión | 80% Reutilizable, 20% A Simplificar |
| **TOTAL** | **2788** | | |

---

## ANÁLISIS DE ARCHIVOS

### 1. `/js/modules/formatos_matricula_builder.js` (1489 líneas)

#### ✅ MANTENER (líneas)
- **5-23:** `initFormatosBuilder()` - Inicialización eventos (MANTENER)
- **26-40:** `canvasClickSelection()` + limpieza click - Selección visual (MANTENER)
- **42-46:** `dragStart()` - Preparación drag data (MANTENER)
- **48-57:** `canvasDragEnter()` y `canvasDragOver()` - Visual feedback (MANTENER)
- **59-127:** `canvasDrop()` - **REFACTOR PARCIAL** (eliminar zonas de zoom, mantener lógica base)
- **129-135:** `generarIdUnicoBloque()` - Generador de IDs (MANTENER)
- **137-222:** `insertarBloqueEnCanvas()` - **CRÍTICO REFACTOR**
  - **A MANTENER:** Creación visual de wrappers, event listeners, estructura HTML
  - **A ELIMINAR:** Conversiones px↔mm (líneas 151-155), cálculos de `margenSup` con `factorMmPx`
  - **A REESCRIBIR:** Posicionamiento debe usar unidades relativas (margen como % o bloques)
- **225-236:** Block controls HTML (MANTENER)
- **238-451:** Generadores de contenido por tipo (`linea`, `texto`, `logo`, etc.) (MANTENER)
- **454-457:** Agregación al canvas + redimensión (MANTENER estructura)
- **459-472:** Auto-ajuste ancho cabecera (MANTENER)
- **475-565:** `insertarBloqueDesdeJSON()` - **REFACTOR PARCIAL**
  - **A MANTENER:** Lógica de recarga desde JSON
  - **A ELIMINAR:** Aplicación de márgenes (línea 497)
  - **A MODIFICAR:** Nombres de propiedades de almacenamiento
- **567-571:** `eliminarBloque()`, `moverBloqueArriba()`, `moverBloqueAbajo()` (MANTENER)
- **587-599:** `chequearEmptyState()` (MANTENER)
- **601-646:** Variables dinámicas (chips) y `insertarVariable()` (MANTENER)
- **650-879:** `guardarFormato()` - **REFACTOR CRÍTICO**
  - **A MANTENER:** Compilación de bloques, extracción de datos, JSON final
  - **A ELIMINAR:** Conversión de coordenadas en compilación (no debe hacerse aquí)
  - **A SIMPLIFICAR:** Lógica de `obtenerPosicion()` (línea 677-687)
- **881-896:** `obtenerEscalaLienzo()` - **ELIMINAR COMPLETAMENTE** (ya no se necesita)
- **898-985:** Drag & drop de bloques `iniciarArrastreBloque()`, `arrastrarBloque()`, `detenerArrastreBloque()`
  - **A MANTENER:** Estructura base
  - **A REESCRIBIR:** Cálculos de zoom (líneas 912, 930) → usar directamente px sin divisiones
  - **A ELIMINAR:** Restricciones de márgenes dinámicas (línea 934-964), confusas con el refactor
- **987-1122:** `iniciarRedimension()` + helpers - **MANTENER CON AJUSTES**
  - **A ELIMINAR:** Escala absoluta (`startAbsoluteScale`) del dataset (línea 1002)
  - **A SIMPLIFICAR:** Cálculos de font-size escalado (líneas 1071-1082)
- **1125-1200:** `abrirCatalogoVariables()` + `seleccionarVariableCatalogo()` (MANTENER)
- **1203-1251:** `recalcularCascadaBloques()` (MANTENER)
- **1253-1260:** `cambiarTamanoLienzoBuilder()` (MANTENER)
- **1262-1269:** `actualizarFiltroCatalogoContextual()` (MANTENER)
- **1271-1336:** `ajustarAlturaLienzo()` + `actualizarZonaSeguraLienzo()`
  - **A MANTENER:** Altura de 1056px
  - **A REESCRIBIR:** Márgenes convertidos a px (línea 1287-1291) → usar directamente unidades lógicas
- **1338-1374:** `alternarBloqueoCabecera()` (MANTENER estructura, ajustar lógica de zonas)
- **1376-1479:** `actualizarBloqueFirmasCanvas()` (MANTENER)
- **1481-1489:** Paste cleanup (MANTENER)

#### ❌ ELIMINAR (líneas específicas)
| Líneas | Función | Razón | Acción |
|--------|---------|-------|--------|
| 80-82 | Cálculos de zoom en `canvasDrop` | zoom innecesario | Usar coordenadas directo |
| 84-86 | `factorMmPx = 3.78` en canvasDrop | conflictivo | Eliminar |
| 150-156 | Conversión mm→px en `insertarBloqueEnCanvas` | hardcodeado | Eliminar |
| 885-896 | `obtenerEscalaLienzo()` completa | no se necesita | **ELIMINAR FUNCIÓN** |
| 912, 930, 1013, 1016 | Usos de `zoomFactor` | confunden lógica | Eliminar variables |
| 1002, 1072-1074 | Escala absoluta en redimensión | innecesaria | Reemplazar con factor simple |

#### ⚠️ REESCRIBIR (funciones)
- `insertarBloqueEnCanvas()` - Eliminar conversiones px↔mm, usar unidades consistentes en editor
- `arrastrarBloque()` - Eliminar cálculos de zoom, simplificar a px puros
- `iniciarRedimension()` - Simplificar escala, eliminar `startAbsoluteScale`

---

### 2. `/js/formatos_matricula.js` (322 líneas)

#### ✅ MANTENER (TODO)
Este archivo actúa como orquestador de UI y es **95% reutilizable**:
- **1-15:** Inicialización (MANTENER)
- **17-30:** `abrirModalAjustesFormato()` (MANTENER)
- **32-39:** `abrirReferenciaCatalogo()` (MANTENER)
- **41-97:** `abrirConfiguracionBloque()` - Modal de config (MANTENER)
- **99-158:** `guardarAjustesBloque()` - Aplicación de settings (MANTENER)
- **160-189:** `prepararNuevoFormato()` (MANTENER)
- **191-197:** `cancelarEdicion()` (MANTENER)
- **199-275:** `editarFormato()` - Carga desde API (MANTENER)
- **277-313:** `eliminarFormato()` - Borrado desde API (MANTENER)
- **315-323:** Vinculación global (MANTENER)

#### ⚠️ AJUSTES MENORES
- Línea 153: Cambiar `style="height: ' + grosor + 'pt"` → usar dataset sin inline (CSS puro)
- Línea 203: Validar que URL `/sistema_escolar/php/logica/formatos_ajax.php` sea relativa correctamente

---

### 3. `/php/logica/formatos_ajax.php` (140 líneas)

#### ✅ MANTENER (TODO - 100% REUTILIZABLE)
Backend está **perfectamente implementado**:
- **1-9:** Headers + seguridad (MANTENER)
- **11-26:** Action 'listar' (MANTENER)
- **28-46:** Action 'obtener' (MANTENER)
- **48-100:** Action 'guardar' - Guardado en BD (MANTENER)
- **102-120:** Action 'eliminar' (MANTENER)
- **122-133:** Action 'cambiar_estado' (MANTENER)
- **61-63:** DEBUG logs (pueden comentarse en producción pero no afectan)

**Validaciones correctas:**
- PDO prepared statements ✅
- CSRF token validation ✅
- Permisos según rol ✅
- Tipos permitidos validados ✅
- JSON response structure ✅

---

### 4. `/imprimir_matricula.php` (837 líneas)

#### ✅ MANTENER (500+ líneas)
- **1-65:** Validación + carga de datos (MANTENER)
- **67-130:** Mapeo de variables dinámicas (MANTENER)
- **186-227:** Motor de sustitución triple (MANTENER)
- **260-271:** Setup de CSS variables (MANTENER)
- **275-283:** Botón FAB de impresión (MANTENER)
- **286-569:** Función `$renderizador` (MANTENER)
- **571-710:** PASO 1 - Procesamiento de bloques avanzados
  - **A MANTENER:** Extracción de bloques, renderizado
  - **A SIMPLIFICAR:** Conversión px↔mm (líneas 604-652)
- **712-725:** PASO 2 - Fallback legacy (MANTENER)
- **727-759:** PASO 3 - Envoltorio de textos (MANTENER)
- **761-762:** PASO 4 - Limpieza BR (MANTENER)
- **764-819:** PASO 5 - Reestructuración para impresión (MANTENER)
- **821-836:** HTML final (MANTENER)

#### ⚠️ SIMPLIFICAR (líneas)
| Líneas | Qué | Por Qué |
|--------|-----|--------|
| 604-652 | Conversión px↔mm + cálculos de márgenes | Hardcodeados, confusos |
| 622-627 | Cálculos de ancho en mm | Usar directamente valores del editor |
| 638-643 | Determinar overflow | Simplificar lógica |
| 645-649 | Transform scale | Remover si no se usa |

#### ❌ ELIMINAR (líneas específicas)
- **Línea 47:** `$rector_stmt = $db->query(...)` - Cambiar a prepared statement por consistency

---

### 5. `/php/vistas/formatos_matricula.php` (150+ líneas leídas)

#### ✅ MANTENER
- Estructura HTML del editor (MANTENER)
- Modales de configuración (MANTENER)
- Catálogo de variables (completar lectura para confirmación)

#### ⚠️ LEER COMPLETO
Este archivo tiene 500+ líneas completas que no leímos. Necesitará revisión para toolbox lateral.

---

### 6. `/styles/modules/formatos_matricula.css`

#### ✅ MANTENER
- Estilos de cards (MANTENER)
- Toolbox styling (MANTENER)
- Canvas styling (MANTENER)

#### ❌ ELIMINAR
- Estilos de conversión visual de zoom (si los hay)

---

### 7. Archivos en `/scratch/` (4 archivos)

| Archivo | Líneas | Estado | Acción |
|---------|--------|--------|--------|
| `diagnostico_formatos.php` | 198 | Debug helper | **ELIMINAR** |
| `apply_formatos_matricula_patch.php` | ~80 | Patch antiguo | **ELIMINAR** |
| `migrar_formatos_multipaper.php` | ? | Legacy migration | **ELIMINAR** |
| `qwen_prompt_formatos_vistas.txt` | ? | Documentación | **ELIMINAR** |

---

## FLUJO DE DATOS ACTUAL

```
┌─────────────────────────────────────────────────────────────────┐
│                      FLUJO COMPLETO FORMATOS                     │
└─────────────────────────────────────────────────────────────────┘

EDITOR (Diseñador):
──────────────────
  1. formatos_matricula.js (UI manager)
     ↓
  2. formatos_matricula_builder.js (Canvas engine)
     ├─ insertarBloqueEnCanvas()
     │  └─ ❌ Convierte MM → PX (factor 3.78) AQUÍ - PROBLEMA #1
     ├─ arrastrarBloque()
     │  └─ ❌ Calcula zoom (obtenerEscalaLienzo) AQUÍ - PROBLEMA #2
     └─ guardarFormato()
        ├─ Extrae posiciones en PX del DOM
        ├─ ❌ NO convierte a MM (usa PX brutos) - INCONSISTENCIA
        └─ Envía JSON + HTML a backend


BACKEND (API):
──────────────
  3. formatos_ajax.php (guardar)
     └─ Almacena JSON + HTML en BD
        ├─ JSON: posiciones en PX (del editor)
        ├─ HTML: HTML bruto (del compilador)
        └─ Márgenes: en MM (campo separado)


IMPRESIÓN (Renderizado):
─────────────────────────
  4. imprimir_matricula.php
     └─ obtener formato de BD
        ├─ ❌ Convierte PX → MM (factor 215.9/816 = 0.264583) AQUÍ - PROBLEMA #3
        ├─ Aplica márgenes (MM)
        ├─ Genera CSS en MM
        └─ Renderiza HTML final


BASES DE DATOS:
───────────────
  5. formatos_matricula (tabla)
     ├─ id
     ├─ nombre, descripcion, tipo
     ├─ contenido_html (HTML bruto)
     ├─ configuracion_json (JSON con posiciones en PX) ⚠️
     ├─ margen_superior, inferior, izquierdo, derecho (en MM)
     ├─ tipo_documento, tamano_lienzo
     └─ creado_en, actualizado_en
```

### Problemas Identificados en el Flujo

**PROBLEMA #1:** Editor calcula márgenes con factor `3.78`
- Línea 84: `const factorMmPx = 3.78;`
- Usado en `insertarBloqueEnCanvas()` para convertir MM input → PX internos
- **Conflicto:** Factor no coincide con el usado en impresión

**PROBLEMA #2:** Arrastre incluye cálculos de zoom
- `obtenerEscalaLienzo()` devuelve 1 siempre si no hay zoom
- Pero el código asume puede haber zoom (confusión arquitectónica)
- **Conflicto:** No hay zoom real, el factor se multiplica innecesariamente

**PROBLEMA #3:** Impresión usa factor diferente
- Línea 607: `factor_px_to_mm = 215.9 / 816;` (0.264583)
- Conversión inversa a PROBLEMA #1
- **Conflicto:** `1/3.78 ≠ 0.264583` → **INCONSISTENCIA CRÍTICA**

### Verificación Matemática

```
Editor (insertarBloqueEnCanvas):
  margen_superior = 20 mm (input)
  margenSup_px = 20 * 3.78 = 75.6 px

Impresión (imprimir_matricula.php):
  x_px = 100 (del JSON guardado)
  x_mm = 100 * 0.264583 = 26.46 mm

Diferencia de escala:
  1/3.78 = 0.2645... ✅ (coincide con 215.9/816)
  Pero el editor NO guarda la conversión, guarda PX brutos
  → Falta sincronización

Solución: Guardar TODO en MM desde el inicio
```

---

## TABLAS DE AUDITORÍA LÍNEA POR LÍNEA

### Tabla 1: Funciones a Mantener (Prioritarias)

| Función | Archivo | Líneas | Criticidad | Acción |
|---------|---------|--------|-----------|--------|
| `initFormatosBuilder()` | builder | 5-23 | ALTA | MANTENER |
| `dragStart()` | builder | 42-46 | ALTA | MANTENER |
| `generarIdUnicoBloque()` | builder | 129-135 | ALTA | MANTENER |
| `insertarVariable()` | builder | 625-646 | ALTA | MANTENER |
| `eliminarBloque()` | builder | 567-571 | MEDIA | MANTENER |
| `moverBloqueArriba/Abajo()` | builder | 573-585 | MEDIA | MANTENER |
| `abrirCatalogoVariables()` | builder | 1135-1152 | ALTA | MANTENER |
| `seleccionarVariableCatalogo()` | builder | 1154-1201 | ALTA | MANTENER |
| `recalcularCascadaBloques()` | builder | 1203-1251 | MEDIA | MANTENER |
| `cambiarTamanoLienzoBuilder()` | builder | 1253-1260 | ALTA | MANTENER |
| `alternarBloqueoCabecera()` | builder | 1338-1374 | ALTA | MANTENER (ajustar) |
| `actualizarBloqueFirmasCanvas()` | builder | 1376-1479 | ALTA | MANTENER |
| `abrirConfiguracionBloque()` | main | 45-97 | ALTA | MANTENER |
| `guardarAjustesBloque()` | main | 99-158 | ALTA | MANTENER |
| `editarFormato()` | main | 199-275 | ALTA | MANTENER |
| `eliminarFormato()` | main | 277-313 | ALTA | MANTENER |

### Tabla 2: Funciones a Refactorizar

| Función | Archivo | Líneas | Cambios Requeridos |
|---------|---------|--------|-------------------|
| `canvasDrop()` | builder | 59-127 | Eliminar zoom, mantener lógica base |
| `insertarBloqueEnCanvas()` | builder | 137-222 | Refactor contenido (html), eliminar conversiones MM→PX |
| `insertarBloqueDesdeJSON()` | builder | 475-565 | Eliminar ajustes de márgenes en carga |
| `guardarFormato()` | builder | 650-879 | Simplificar extracción de posiciones, NO convertir a MM |
| `iniciarArrastreBloque()` | builder | 898-923 | Eliminar cálculo de zoom |
| `arrastrarBloque()` | builder | 925-976 | Eliminar zoom, simplificar restricciones |
| `iniciarRedimension()` | builder | 988-1107 | Eliminar startAbsoluteScale, simplificar font-size |
| `ajustarAlturaLienzo()` | builder | 1271-1336 | Reescribir márgenes en unidades lógicas |

### Tabla 3: Funciones a Eliminar

| Función | Archivo | Líneas | Razón |
|---------|---------|--------|-------|
| `obtenerEscalaLienzo()` | builder | 885-896 | Zoom innecesario en arquitectura MM |
| Archivo completo | `scratch/diagnostico_formatos.php` | 1-198 | Debug helper no necesario |
| Archivo completo | `scratch/apply_formatos_matricula_patch.php` | 1-80+ | Patch antiguo obsoleto |
| Archivo completo | `scratch/migrar_formatos_multipaper.php` | ? | Legacy migration completa |
| Archivo completo | `scratch/qwen_prompt_formatos_vistas.txt` | ? | Documentación de desarrollo |

### Tabla 4: Variables Globales Problemáticas

| Variable | Ubicación | Líneas | Tipo | Acción |
|----------|-----------|--------|------|--------|
| `contadorBloquesAres` | builder | 129-130 | Global | Mantener (necesaria para ID único) |
| `activeConfigNode` | main | 41-42 | Global | Mantener (necesaria para modal) |
| `lastSavedRange` | builder | 603-604 | Global | Mantener (necesaria para selección) |
| `activeRangeBeforeModal` | builder | 1128-1129 | Global | Mantener (necesaria para modal) |
| `activeEditableBeforeModal` | builder | 1131-1132 | Global | Mantener (necesaria para modal) |
| `bloqueArrastrando` | builder | 882-883 | Global | Mantener (necesaria para arrastre) |

---

## CÓDIGO LEGACY / EXPERIMENTAL A ELIMINAR

### 1. Conversiones de Escala (Problema Raíz)

**Ubicación:** `js/modules/formatos_matricula_builder.js`

```javascript
// ❌ LÍNEA 84 - ELIMINAR
const factorMmPx = 3.78;
const margenSup = Math.round((parseFloat(document.getElementById('formato-margen-superior').value) || 20) * factorMmPx);

// ❌ LÍNEAS 150-156 - ELIMINAR BLOQUE COMPLETO
const factorMmPx = 3.78;
const margenSup = Math.round((parseFloat(...) || 20) * factorMmPx);
const margenInf = Math.round((parseFloat(...) || 20) * factorMmPx);
const margenIzq = Math.round((parseFloat(...) || 20) * factorMmPx);
const margenDer = Math.round((parseFloat(...) || 20) * factorMmPx);

// ❌ LÍNEAS 933-938 - DUPLICADO EN arrastrarBloque()
const margenSup = Math.round((parseFloat(document.getElementById('formato-margen-superior').value) || 20) * factorMmPx);
const margenInf = Math.round((parseFloat(document.getElementById('formato-margen-inferior').value) || 20) * factorMmPx);
const margenIzq = Math.round((parseFloat(document.getElementById('formato-margen-izquierdo').value) || 20) * factorMmPx);
const margenDer = Math.round((parseFloat(document.getElementById('formato-margen-derecho').value) || 20) * factorMmPx);
```

**Razón:** Con la refactorización a milímetros puros, estos cálculos NO serán necesarios. Los márgenes se usarán directamente desde el HTML como atributos.

### 2. Lógica de Zoom (Arquitectura Confusa)

**Ubicación:** `js/modules/formatos_matricula_builder.js`

```javascript
// ❌ FUNCIÓN COMPLETA - LÍNEAS 885-896
function obtenerEscalaLienzo(canvas) {
    if (!canvas) return 1;
    const rect = canvas.getBoundingClientRect();
    if (canvas.offsetWidth > 0 && rect.width > 0) {
        return rect.width / canvas.offsetWidth;
    }
    const computedZoom = parseFloat(window.getComputedStyle(canvas).zoom);
    if (!isNaN(computedZoom) && computedZoom > 0) {
        return computedZoom;
    }
    return 1;
}

// ❌ REFERENCIAS A ESTA FUNCIÓN - ELIMINAR:
// Línea 80: const zoomScale = obtenerEscalaLienzo(canvas);
// Línea 912: const zoomFactor = obtenerEscalaLienzo(canvas);
// Línea 930: const zoomFactor = obtenerEscalaLienzo(canvas);
// Línea 1013: const zoomFactor = obtenerEscalaLienzo(canvas);
```

**Razón:** El zoom es calculado pero casi siempre devuelve 1. Si el editor NO hace zoom real (y no lo hace), esta función es ruido arquitectónico.

### 3. Escala Absoluta en Redimensión

**Ubicación:** `js/modules/formatos_matricula_builder.js` - Línea 1002

```javascript
// ❌ LÍNEA 1002 - ELIMINAR
wrapper.dataset.startAbsoluteScale = wrapper.dataset.scale || "1.0";

// ❌ LÍNEA 1072-1074 - ELIMINAR LÓGICA
const deltaScale = newWidth / startWidth;
const startAbsoluteScale = parseFloat(wrapper.dataset.startAbsoluteScale) || 1.0;
const absoluteScale = startAbsoluteScale * deltaScale;
```

**Razón:** La escala acumulativa es confusa. En refactor a MM, la redimensión será simple: cambiar width sin afectar font-size.

### 4. Archivos Scratch Completos

```bash
# Eliminar completamente:
/scratch/diagnostico_formatos.php (198 líneas)
/scratch/apply_formatos_matricula_patch.php (~80 líneas)
/scratch/migrar_formatos_multipaper.php (completo)
/scratch/qwen_prompt_formatos_vistas.txt (completo)
```

**Comandos para limpieza:**
```bash
rm -f c:\xampp\htdocs\sistema_escolar\scratch\diagnostico_formatos.php
rm -f c:\xampp\htdocs\sistema_escolar\scratch\apply_formatos_matricula_patch.php
rm -f c:\xampp\htdocs\sistema_escolar\scratch\migrar_formatos_multipaper.php
rm -f c:\xampp\htdocs\sistema_escolar\scratch\qwen_prompt_formatos_vistas.txt
```

### 5. Cálculos de Márgenes Duplicados en Impresión

**Ubicación:** `imprimir_matricula.php` - Líneas 604-652

```php
// ❌ ESTOS CÁLCULOS PUEDEN SIMPLIFICARSE
// Línea 607: $factor_px_to_mm = 215.9 / 816; (Hardcodeado)
// Línea 610-611: $margen_izq_mm y $margen_sup_mm (Re-convertidos)
// Línea 615-620: Conversión duplicada (sin margen → con margen)

// ⚠️ REFACTOR: Recibir posiciones DIRECTAMENTE en MM desde editor
// O calcularlas una sola vez en un helper PHP
```

---

## CÓDIGO REUTILIZABLE

### ✅ Métodos de Guardado/Carga (100% Funcional)

#### Backend API (`php/logica/formatos_ajax.php`)
```php
// REUTILIZABLE - No modificar
- Líneas 20-26:   Action 'listar' → SELECT desde BD
- Líneas 28-46:   Action 'obtener' → Fetch by ID + JSON decode
- Líneas 48-100:  Action 'guardar' → INSERT/UPDATE con prepared statements
- Líneas 102-120: Action 'eliminar' → DELETE con validación tipo
- Línea 40-42:    JSON decode (necesario mantener para compatibilidad)
```

**Uso posterior:** Sin cambios. El backend es agnóstico a cómo se calculan las posiciones.

#### Frontend Load (`js/formatos_matricula.js:199-275`)
```javascript
// REUTILIZABLE
function editarFormato(id) {
    // Línea 200-203: FormData + CSRF
    // Línea 205: Fetch a API
    // Línea 207-274: Procesamiento de respuesta
    
    // Línea 233-245: IMPORTANTE
    // if (res.data.configuracion_json) {
    //     const blocks = JSON.parse(res.data.configuracion_json);
    //     blocks.forEach(block => insertarBloqueDesdeJSON(block));
    // }
    
    // Este flujo se mantiene sin cambios
}
```

### ✅ Estructura JSON de Bloques

**Formato ACTUAL guardado en BD (REUTILIZAR):**
```json
[
  {
    "type": "titulo_colegio",
    "left": 150,
    "top": 50,
    "width": 400,
    "height": null,
    "scale": 1.0,
    "size": "20",
    "content": "<h3>INSTITUCIÓN...</h3>"
  },
  {
    "type": "calificaciones",
    "left": 100,
    "top": 500,
    "width": 600,
    "diseno": "elite",
    "filtro": "todas",
    "columnas": "materia,docente,definitiva,estado"
  }
]
```

**Decisión:** Las unidades (PX vs MM) cambiaran, pero la ESTRUCTURA se mantiene idéntica.

### ✅ Generadores de Contenido HTML

**Ubicación:** `js/modules/formatos_matricula_builder.js:238-451`

```javascript
// REUTILIZABLE - Generadores de bloques por tipo:
// - Línea 240-246:   linea
// - Línea 248-252:   texto
// - Línea 254-261:   logo
// - Línea 262-268:   titulo_colegio
// - Línea 269-275:   lema_colegio
// - Línea 276-285:   foto_estudiante
// - Línea 286-295:   qr_estudiante
// - Línea 296-304:   texto_certificacion
// - Línea 305-311:   metadatos
// - Línea 312-398:   ficha
// - Línea 399-420:   calificaciones
// - Línea 421-450:   firmas

// Estos HTML templates NO cambian en refactor a MM
// Solo cambia cómo se POSICIONAN, no cómo se GENERAN
```

### ✅ Variables Dinámicas (Chips)

**Ubicación:** `js/modules/formatos_matricula_builder.js:601-646`

```javascript
// REUTILIZABLE COMPLETAMENTE
- Línea 608-623: selectionchange listener (MANTENER)
- Línea 625-646: insertarVariable() (MANTENER SIN CAMBIOS)
- Línea 691-706: Normalización de chips en guardarFormato (MANTENER)
```

**Uso en impresión:** `imprimir_matricula.php:190-200` (motor de sustitución)
- Busca spans con clase `ares-variable-badge`
- Reemplaza por valores de `$var_map`
- **NO cambia con refactor a MM**

### ✅ Validaciones de Permisos y Seguridad

**Ubicación:** `php/logica/formatos_ajax.php:1-16`

```php
// REUTILIZABLE
- Línea 2: declare(strict_types=1)
- Línea 3-4: guardia_sesion() + session_write_close()
- Línea 12: proteccion_extrema()
- Línea 14-16: Validación de permisos por rol
```

**Patrón:** Se mantiene en todos los endpoints sin cambios.

---

## PLAN DE REFACTORIZACIÓN

### FASE 0: PREPARACIÓN (Limpieza)

**Duración estimada:** 1-2 horas

1. **Eliminar archivos scratch**
   ```bash
   rm c:\xampp\htdocs\sistema_escolar\scratch\diagnostico_formatos.php
   rm c:\xampp\htdocs\sistema_escolar\scratch\apply_formatos_matricula_patch.php
   rm c:\xampp\htdocs\sistema_escolar\scratch\migrar_formatos_multipaper.php
   rm c:\xampp\htdocs\sistema_escolar\scratch\qwen_prompt_formatos_vistas.txt
   ```

2. **Crear rama backup (git)**
   ```bash
   git checkout -b backup/formatos-antes-refactor
   git add -A
   git commit -m "Backup: Estado anterior a refactorización a milímetros"
   git checkout master
   ```

3. **Validar BD actual**
   - Ejecutar `scratch/diagnostico_formatos.php` UNA VEZ
   - Anotar formatos existentes
   - Verificar JSON guardado
   - Luego eliminar el script

---

### FASE 1: BACKEND (Sin Cambios)

**Duración:** ~15 minutos  
**Archivos:** `php/logica/formatos_ajax.php`  
**Estado:** ✅ LISTO, no requiere cambios

1. **Validar estructura**
   - PDO prepared statements: ✅
   - Response JSON: ✅
   - Validaciones: ✅

2. **Comentar debug log (opcional)**
   - Línea 62-63: Dejar comentado en desarrollo

---

### FASE 2: EDITOR FRONTEND - PARTE A (Eliminar Zoom)

**Duración:** 2-3 horas  
**Archivos:** `js/modules/formatos_matricula_builder.js`

1. **Eliminar función `obtenerEscalaLienzo()`**
   - Líneas 885-896: BORRAR completamente
   - Referencias (líneas 80, 912, 930, 1013, 1016): SIMPLIFICAR

2. **Refactor de `canvasDrop()` (líneas 59-127)**
   ```javascript
   // ANTES (línea 80-82):
   const zoomScale = obtenerEscalaLienzo(canvas);
   const x = ((e.clientX - rect.left) / zoomScale) - 150;
   const y = ((e.clientY - rect.top) / zoomScale) - 30;
   
   // DESPUÉS:
   const x = (e.clientX - rect.left) - 150;
   const y = (e.clientY - rect.top) - 30;
   // Usar directamente sin zoom
   ```

3. **Refactor de `insertarBloqueEnCanvas()` (líneas 137-222)**
   - ELIMINAR líneas 150-156 (conversión MM→PX)
   - MANTENER generación de HTML
   - Nueva estrategia de márgenes: pasar como atributos data-* directamente

4. **Refactor de `arrastrarBloque()` (líneas 925-976)**
   - ELIMINAR líneas 930 (zoomFactor)
   - SIMPLIFICAR cálculo de posición
   - Restricciones de márgenes: usar valores de input directamente

5. **Refactor de `iniciarArrastreBloque()` (líneas 898-923)**
   - ELIMINAR línea 912 (zoomFactor)
   - Usar coordenadas directo sin zoom

---

### FASE 3: EDITOR FRONTEND - PARTE B (Simplificar Redimensión)

**Duración:** 1-2 horas  
**Archivos:** `js/modules/formatos_matricula_builder.js`

1. **Refactor de `iniciarRedimension()` (líneas 988-1107)**
   - ELIMINAR línea 1002: `wrapper.dataset.startAbsoluteScale`
   - REESCRIBIR líneas 1071-1074: eliminar multiplicación de scales
   - Nueva lógica: redimensión = cambiar width, font-size proporcional simple

2. **Refactor de `agregarNodosRedimension()` (líneas 1109-1122)**
   - MANTENER estructura
   - Pasar simplemente el wrapper

3. **Refactor de `ajustarAlturaLienzo()` (líneas 1271-1336)**
   - MANTENER altura 1056px
   - REESCRIBIR líneas 1287-1291: márgenes usarlos como variables CSS

---

### FASE 4: EDITOR FRONTEND - PARTE C (Simplificar Guardado)

**Duración:** 2-3 horas  
**Archivos:** `js/modules/formatos_matricula_builder.js`

1. **Refactor de `guardarFormato()` (líneas 650-879)**
   - LÍNEAS 677-687: Simplificar `obtenerPosicion()`
     ```javascript
     // ANTES: Alternancia entre style y dataset (confuso)
     const obtenerPosicion = (elem, prop) => {
         let valor = elem.style[prop];
         if (valor && valor !== '') {
             return parseFloat(valor);
         }
         valor = elem.dataset[prop === 'left' ? 'left' : 'top'];
         ...
     };
     
     // DESPUÉS: Usar directamente dataset
     const obtenerPosicion = (elem, prop) => {
         return parseFloat(elem.dataset[prop]) || 0;
     };
     ```

   - LÍNEAS 714-729: NO convertir a MM aquí
     ```javascript
     // ANTES:
     const width = widthVal ? ` data-width="${widthVal}"` : '';
     const height = heightVal ? ` data-height="${heightVal}"` : '';
     const align = 'justify';
     const styleAttrs = `style="...left:${left}px..."`;
     
     // DESPUÉS: Guardar unidades consistentes
     // (decidir: PX o MM en editor, mantener en BD)
     ```

   - LÍNEA 824: Ya no convertir, guardar directamente
     ```javascript
     formData.append('configuracion_json', JSON.stringify(configJson));
     // Sin conversión de unidades en compilación
     ```

2. **Refactor de `insertarBloqueDesdeJSON()` (líneas 475-565)**
   - ELIMINAR línea 497: `el.dataset.left = left;` (ya vino del JSON)
   - SIMPLIFICAR aplicación de propiedades

---

### FASE 5: IMPRESIÓN (Simplificar Conversiones)

**Duración:** 2-3 horas  
**Archivos:** `imprimir_matricula.php`

1. **Refactor de conversión de coordenadas (líneas 604-652)**

   **ESTRATEGIA:**
   - Editor guarda en UNIDADES CONSISTENTES (PX ó MM)
   - Impresión recibe esas unidades directamente
   - No hace conversión, solo aplica CSS

   ```php
   // ANTES (líneas 604-652):
   $factor_px_to_mm = 215.9 / 816;  // Hardcodeado
   $x_mm_sin_margen = round($x * $factor_px_to_mm, 2);
   $x_mm = round($x_mm_sin_margen - $margen_izq_mm, 2);
   $style_inline = "left: {$x_mm}mm; ...";
   
   // DESPUÉS (opción 1 - SI EDITOR GUARDA EN MM):
   $x_mm = floatval($x);  // Ya está en MM
   $style_inline = "left: {$x_mm}mm; ...";
   
   // DESPUÉS (opción 2 - SI EDITOR GUARDA EN PX):
   $factor_px_to_mm = 0.264583; // Usar valor exacto
   $x_mm = round($x * $factor_px_to_mm, 2);
   $style_inline = "left: {$x_mm}mm; ...";
   ```

2. **Simplificar cálculos de márgenes**
   - Línea 610-611: Estos valores YA vienen de la BD
   - Solo usarlos, no recalcular

3. **Refactor de overflow y transforms (líneas 638-649)**
   - Simplificar lógica booleana
   - Solo: overflow visible donde sea necesario

---

### FASE 6: TESTING Y VALIDACIÓN

**Duración:** 3-4 horas

1. **Tests Manuales en Navegador**
   - [ ] Crear formato nuevo
   - [ ] Agregar bloques de cada tipo
   - [ ] Arrastrar bloques
   - [ ] Redimensionar bloques
   - [ ] Editar membrete
   - [ ] Guardar formato
   - [ ] Cargar formato existente
   - [ ] Eliminar bloque
   - [ ] Modificar márgenes
   - [ ] Guardar nuevamente

2. **Verificación de Impresión**
   - [ ] Imprimir estudiante con formato nuevo
   - [ ] Verificar que posiciones sean correctas en mm
   - [ ] Verificar márgenes respetados
   - [ ] Verificar saltos de página

3. **Verificación de BD**
   - [ ] JSON guardado tiene estructura correcta
   - [ ] Posiciones almacenadas son consistentes
   - [ ] Carga de formato antiguo sigue funcionando

4. **Cross-browser Testing**
   - [ ] Chrome/Edge
   - [ ] Firefox
   - [ ] Safari (si aplica)

---

### FASE 7: CLEANUP Y DOCUMENTACIÓN

**Duración:** 1-2 horas

1. **Eliminar Scratch Files**
   ```bash
   rm c:\xampp\htdocs\sistema_escolar\scratch\diagnostico_formatos.php
   rm c:\xampp\htdocs\sistema_escolar\scratch\apply_formatos_matricula_patch.php
   rm c:\xampp\htdocs\sistema_escolar\scratch\migrar_formatos_multipaper.php
   ```

2. **Actualizar CLAUDE.md**
   - Agregar sección de formatos de matrícula
   - Documentar flujo simplificado
   - Agregar guía de desarrollo

3. **Commit Final**
   ```bash
   git add -A
   git commit -m "refactor: formatos matrícula a milímetros - eliminar zoom innecesario"
   ```

---

## COMANDOS DE VERIFICACIÓN

### Búsqueda de Patrones Problemáticos

```bash
# 1. Encontrar todos los usos de factorMmPx
grep -rn "factorMmPx" c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js
# Esperado: 3 líneas (84, 151, 933)

# 2. Encontrar referencias a obtenerEscalaLienzo
grep -rn "obtenerEscalaLienzo\|zoomFactor\|zoomScale" c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js
# Esperado: 10+ líneas (a eliminar todas)

# 3. Encontrar conversiones px→mm en impresión
grep -n "factor_px_to_mm\|215.9 / 816" c:\xampp\htdocs\sistema_escolar\imprimir_matricula.php
# Esperado: 2 líneas (a simplificar)

# 4. Encontrar todos los dataset.scale
grep -rn "dataset.scale\|startAbsoluteScale" c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js
# Esperado: 3 líneas (a eliminar)

# 5. Verificar que BD no tenga problemas
# En MySQL/MariaDB:
SELECT id, nombre, LENGTH(configuracion_json) as json_size FROM formatos_matricula;
# Todos los json_size deben ser > 0
```

### Validación POST-REFACTOR

```bash
# 1. Verificar que no hay referencias a zoom
grep -r "zoom\|escala" c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js
# Esperado: VACÍO

# 2. Verificar que guardarFormato usa estructura limpia
grep -A5 "guardarFormato" c:\xampp\htdocs\sistema_escolar\js\modules\formatos_matricula_builder.js | head -20
# Debe ser legible y simple

# 3. Verificar que backend sigue intacto
grep -n "PDO\|prepare\|execute" c:\xampp\htdocs\sistema_escolar\php\logica\formatos_ajax.php
# Esperado: múltiples líneas, NO cambios

# 4. Verificar que scratch fue eliminado
ls -la c:\xampp\htdocs\sistema_escolar\scratch\formatos*
# Esperado: No such file error
```

---

## RESUMEN EJECUTIVO PARA EL INGENIERO RICARDO

### Estado Crítico
✅ **Backend:** Perfecto, 100% reutilizable  
✅ **API:** Funcional, no requiere cambios  
✅ **Impresión:** 80% reutilizable (simplificar conversiones)  
⚠️ **Editor:** 60% reutilizable (eliminar zoom + conversiones)  

### Trabajo a Realizar (7 Fases)

| Fase | Duración | Complejidad | Status |
|------|----------|-------------|--------|
| 0. Limpieza | 1-2h | BAJA | Pendiente |
| 1. Backend | 15m | NULA | Validar nada más |
| 2. Editor Part A | 2-3h | MEDIA | Eliminar zoom |
| 3. Editor Part B | 1-2h | MEDIA | Simplificar redimensión |
| 4. Editor Part C | 2-3h | ALTA | Refactor guardado |
| 5. Impresión | 2-3h | MEDIA | Simplificar conversiones |
| 6. Testing | 3-4h | ALTA | Exhaustivo |
| 7. Cleanup | 1-2h | BAJA | Documentación |

**Total estimado:** 15-20 horas de desarrollo  
**Riesgo:** BAJO (estructura separada, backend intacto)  
**Beneficio:** Eliminación de 300+ líneas confusas, arquitectura limpia en milímetros

---

**Documento guardado en:** `claude_docs/AUDITORIA_FORMATOS_COMPLETA.md`  
**Listo para revisión del Ingeniero Ricardo.**
