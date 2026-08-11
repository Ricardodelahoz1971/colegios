# HALLAZGOS VISUALES - PHASE 5 (Pruebas del User)

**Fecha:** 2026-08-09  
**Tester:** User (búsqueda de vistas raras)  
**Total hallazgos:** 3 bugs verificados  
**Severidad:** MEDIA (no impiden uso, pero rompen consistencia visual)

---

## 🐛 BUG 1: Inconsistencia de nomenclatura en títulos de módulo

**Descripción:**
- Algunos módulos usan: `MÓDULO ASISTENCIA`
- Otros módulos usan: `MATRÍCULA ACADÉMICA` (sin palabra MÓDULO)
- No hay patrón consistente

**Ubicación:**
- `php/dashboard.php` línea 101: `$nombre_modulo = "MÓDULO " . strtoupper($nombres_modulos[$pagina] ?? $pagina);`
- Array `$nombres_modulos` (líneas 79-100) tiene valores inconsistentes

**Impacto Visual:**
- User ve títulos dispares (MÓDULO X vs SECCIÓN Y vs GESTIÓN Z)
- Falta de identidad visual uniforme

**Ejemplo:**
```
❌ MÓDULO ASISTENCIA
❌ MÓDULO AGENDA
✅ MATRÍCULA ACADÉMICA (sin "MÓDULO")
✅ CONFIGURACIÓN (sin "MÓDULO")
```

**Causa raíz:**
El array `$nombres_modulos` fue creciendo sin revisar consistencia. Algunos tienen prefijo, otros no.

**Fix requerido:**
Estandarizar todos los nombres a un patrón (ej: siempre "MÓDULO X" o siempre solo nombre).

---

## 🐛 BUG 2: Tabla matriculados - Columnas se desbordan

**Descripción:**
En la vista `matriculados.php`, las columnas (especialmente USUARIO y ESTADO) no tienen ancho fijo.
El texto se corta y baja a múltiples líneas, haciendo ilegible la tabla.

**Ubicación:**
- `php/vistas/matriculados.php` - tabla con clase `.table-elite`
- `styles/ui_kit.css` - definición de `.table-elite`

**Impacto Visual:**
- Tabla desformateada, difícil de leer
- Filas se expanden verticalmente innecesariamente
- Columnas "USUARIO" y "ESTADO" especialmente afectadas

**Ejemplo visual:**
```
Usuario: 72182949-1    ← Debería estar en una línea
                       ← Pero baja a siguiente línea
Estado: ANTIGUO        ← También se desdobla
```

**Causa probable:**
- Falta de `white-space: nowrap` en `<th>` y `<td>`
- Ancho de tabla no está distribuido correctamente
- Posible colisión: `.table-elite` redefinida en `styles/modules/matriculados.css` o similar

**Fix requerido:**
Revisar `styles/ui_kit.css` para `.table-elite`:
- Agregar `table-layout: fixed` si no existe
- Asegurar que columnas tengan `white-space: nowrap`
- Validar que no haya redefinición en módulos que sobre-escriba el ancho

---

## 🐛 BUG 3: Subtítulo inconsistente en módulos

**Descripción:**
El subtítulo de módulos cambia de color según el módulo:
- ✅ **AGENDA ACADÉMICA** → subtítulo en color PRIMARY (azul/rojo según tema) — CORRECTO
- ❌ **ASISTENCIA** → subtítulo en NARANJA (color accent) — INCORRECTO

**Ubicación:**
- `php/dashboard.php` línea 603: Donde se renderiza el título del módulo
- `styles/modules/layout.css` o `styles/ui_kit.css` - clase `.subtitle-elite`

**Impacto Visual:**
- Inconsistencia visual obvia al navegar entre módulos
- Usuario confundido por cambio de colores
- No respeta identidad visual institucional

**Ejemplo:**
```
✅ AGENDA ACADÉMICA
   Gestión de tareas, trabajos... [Color PRIMARY - correcto]

❌ MÓDULO ASISTENCIA
   Registro diario de asistencia... [Color NARANJA - incorrecto]
```

**Causa probable:**
Clase `.subtitle-elite` o `.hero-module-title` está siendo pisada por:
- `.text-accent` aplicada inconsistentemente
- Redefinición en módulos específicos (ej: `styles/modules/asistencia.css`)
- Conflicto de especificidad (colisión CSS típica)

**Fix requerido:**
Buscar dónde `.subtitle-elite` está tomando color naranja (accent) en lugar de gris/secondary.
Probablemente en:
- `styles/ui_kit.css` - definición de `.subtitle-elite`
- `styles/modules/*.css` - redefiniciones locales

---

## 📊 Resumen para PHASE 6

| Bug | Tipo | Severidad | Archivo | Fix |
|-----|------|-----------|---------|-----|
| 1. Nombres módulos | PHP/Lógica | BAJA | dashboard.php | Estandarizar nombres |
| 2. Tabla rota | CSS Layout | MEDIA | ui_kit.css + módulos | Validar table-layout + white-space |
| 3. Color subtítulo | CSS Especificidad | MEDIA | ui_kit.css + módulos | Resolver colisión de color |

---

## 🎯 Próximo paso

**PHASE 6: CSS NAMESPACE CLEANUP** debe incluir:
1. Revisar todas las redefiniciones de `.table-elite` en módulos
2. Auditar `.subtitle-elite` y `.hero-module-title` por colisiones
3. Estandarizar nomenclatura de módulos en PHP

**No son bugs críticos, pero son síntomas de la misma enfermedad: Colisiones CSS + inconsistencia arquitectónica.**
