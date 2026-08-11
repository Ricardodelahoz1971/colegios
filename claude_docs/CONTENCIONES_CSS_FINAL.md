# 🛡️ CONTENCIONES CSS FINAL - BARRERA INVIOLABLE v10.0

**Este documento define las RESTRICCIONES ABSOLUTAS que el agente DEBE validar ANTES de escribir cualquier CSS.**

**Validación obligatoria:** El agente SIEMPRE lee este archivo y valida su código contra estas reglas antes de guardar.

---

## ✅ PERMITIDO (OBLIGATORIO)

### 1. Variables de Color
```css
/* ✅ CORRECTO - Siempre usar variables */
color: var(--el-primary);
background: var(--color-gray-50);
border-color: var(--el-accent);
```

**Variables disponibles:**
- `--el-primary` - Color primario (responde a cambios de paleta)
- `--el-accent` - Naranja/acentuación
- `--color-gray-50` a `--color-gray-900` - Escala de grises
- `--el-white`, `--el-dark` - Blancos y oscuros
- `--el-primary-rgb` - Para `rgba(var(--el-primary-rgb), 0.1)`

---

### 2. Variables de Geometría
```css
/* ✅ CORRECTO - Siempre usar variables */
height: var(--el-height-elite);           /* 44px */
border-radius: var(--el-radius-sub);      /* 8px */
border-radius: var(--el-radius-main);     /* 24px */
border-radius: var(--el-radius-pill);     /* Píldora */
```

---

### 3. Línea Lateral (Identidad Visual)
```css
/* ✅ CORRECTO - TODOS los componentes principales usan esto */
border-inline-start: 0.25rem solid var(--el-primary);
```

**Aplicar a:**
- Tarjetas (`.card`, `.metric-card-elite`, etc.)
- Alertas (`.alert`, `.alert-soberania-elite`)
- Secciones principales
- Paneles de contenido

---

### 4. Variables de Z-Index
```css
/* ✅ CORRECTO - Jerarquía semántica */
z-index: var(--z-dropdown);       /* 100 */
z-index: var(--z-modal);          /* 1001 */
z-index: var(--z-notification);   /* 1100 */
```

**Disponibles:** `--z-dropdown`, `--z-tooltip`, `--z-sticky`, `--z-fixed`, `--z-overlay`, `--z-modal-backdrop`, `--z-modal`, `--z-notification`, `--z-navbar`, `--z-popover`

---

### 5. Breakpoints Responsivos
```css
/* ✅ CORRECTO - Variables obligatorias */
@media (max-width: var(--breakpoint-md)) { }
@media (min-width: var(--breakpoint-lg)) { }
```

**Disponibles:** `--breakpoint-xs` (0), `--breakpoint-sm` (576px), `--breakpoint-md` (768px), `--breakpoint-lg` (992px), `--breakpoint-xl` (1200px), `--breakpoint-2xl` (1400px)

---

### 6. Transiciones y Animaciones
```css
/* ✅ CORRECTO - Variables predefinidas */
transition: var(--el-transition-fast);      /* 0.25s */
transition: var(--el-transition-premium);   /* 0.4s */
transition: var(--el-transition-organic);   /* 0.6s */

animation: fadeIn 0.3s ease-out;
animation: slideInUp 0.3s ease-out;
animation: bounce 0.6s ease-in-out infinite;
```

**Animaciones disponibles:** fadeIn, fadeOut, slideInUp, slideInDown, slideInLeft, slideInRight, rotate, rotateSlow, bounce, shake, glow, pulse, scaleIn, scaleOut, flipIn, swing

---

### 7. @layer Estructura
```css
/* ✅ CORRECTO - Respetar jerarquía de capas */
@layer reset { }      /* Bootstrap y librerías (MENOR prioridad) */
@layer base { }       /* Variables, colores, animaciones */
@layer components { } /* Componentes reutilizables */
@layer modules { }    /* Módulos específicos */
@layer utilities { }  /* Utilidades, overrides (MAYOR prioridad) */
```

---

### 8. !important - CASOS PERMITIDOS ÚNICAMENTE
```css
/* ✅ PERMITIDO SOLO EN 3 ARCHIVOS */
/* 1. styles/bootstrap_override.css - Anular clases Bootstrap */
.text-success { color: var(--el-primary) !important; }

/* 2. styles/sweetalert2_customization.css - Anular librería SweetAlert2 */
.swal2-confirm { background-color: var(--el-primary) !important; }

/* 3. styles/elite_print.css - Estilos de impresión */
@media print { .hide-print { display: none !important; } }
```

**⚠️ PROHIBIDO en cualquier otro archivo.**

---

## ❌ PROHIBIDO ABSOLUTAMENTE

### 1. Colores Hardcodeados
```css
/* ❌ INCORRECTO */
color: #ff4500;
background: #ffffff;
border: 1px solid hsl(224, 78%, 46%);
fill: rgb(255, 69, 0);

/* ✅ CORRECTO */
color: var(--el-accent);
background: var(--el-white);
border: 1px solid var(--el-primary);
fill: var(--el-accent);
```

---

### 2. Tamaños Hardcodeados
```css
/* ❌ INCORRECTO */
border-radius: 12px;
padding: 1rem;
height: 44px;
z-index: 9999;
width: 768px;

/* ✅ CORRECTO */
border-radius: var(--el-radius-sub);
padding: 1rem;  /* OK si es valor estándar */
height: var(--el-height-elite);
z-index: var(--z-modal);
width: 100%;  /* Responsive siempre */
```

---

### 3. !important en Archivos Nuevos
```css
/* ❌ PROHIBIDO - A menos que sea en los 3 archivos especiales */
.componente { color: red !important; }
```

Si necesitas `!important`, es síntoma de **especificidad mal resuelta**. Arregla la cascada en lugar de forzar con `!important`.

---

### 4. Inline Styles (Atributo style="...")
```html
<!-- ❌ PROHIBIDO -->
<div style="color: red; border: 1px solid blue;">

<!-- ✅ CORRECTO -->
<div class="mi-componente">
<!-- CSS en archivo externo -->
```

---

### 5. Variables de Estado (Eliminadas Completamente)
```css
/* ❌ PROHIBIDO - Estas variables NO existen */
color: var(--el-success);      /* NO EXISTE */
color: var(--el-danger);       /* NO EXISTE */
color: var(--el-info);         /* NO EXISTE */
color: var(--el-warning);      /* NO EXISTE */

/* ❌ PROHIBIDO - Clases Bootstrap de estado */
class="text-success"
class="bg-danger"
class="alert-warning"

/* ✅ USAR SIEMPRE */
color: var(--el-primary);
class="text-primary"
class="bg-primary"
```

---

### 6. Animaciones Duplicadas
```css
/* ❌ PROHIBIDO - Crear nueva animación */
@keyframes miAnimacion { }
@keyframes fadeIn { }  /* YA EXISTE en elite_animations.css */

/* ✅ CORRECTO - Usar existente */
animation: fadeIn 0.3s ease-out;
```

---

### 7. Breakpoints Hardcodeados
```css
/* ❌ INCORRECTO */
@media (max-width: 768px) { }
@media (min-width: 992px) { }

/* ✅ CORRECTO */
@media (max-width: var(--breakpoint-md)) { }
@media (min-width: var(--breakpoint-lg)) { }
```

---

## 🔍 CHECKLIST DE AUTO-VALIDACIÓN (Agente DEBE hacer esto)

Antes de guardar cualquier archivo CSS, el agente valida:

```
[ ] ¿Todos los colores usan var(--)?
[ ] ¿Ningún #hexadecimal, hsl(), rgb() hardcodeado?
[ ] ¿Todos los tamaños usan variables?
[ ] ¿Z-index son variables (--z-*)?
[ ] ¿Breakpoints son variables (--breakpoint-*)?
[ ] ¿Transiciones usan var(--el-transition-*)?
[ ] ¿Animaciones existen en elite_animations.css?
[ ] ¿Sin !important EXCEPTO en 3 archivos permitidos?
[ ] ¿Sin inline styles (style="...")?
[ ] ¿Sin variables de estado (success, danger, info, warning)?
[ ] ¿Usar @layer correctamente?
[ ] ¿border-inline-start: 0.25rem en componentes principales?
```

---

## 📋 ARCHIVOS DE REFERENCIA

- `styles/elite_colors.css` - Paleta centralizada
- `styles/elite_animations.css` - Animaciones consolidadas
- `styles/elite_z_index.css` - Z-index semántico
- `styles/elite_breakpoints.css` - Breakpoints estándar
- `styles/bootstrap_override.css` - Blindaje Bootstrap (!important permitido)
- `styles/sweetalert2_customization.css` - Blindaje SweetAlert2 (!important permitido)
- `styles/elite_print.css` - Estilos de impresión (!important permitido)

---

## 🚨 VIOLACIÓN = RECHAZO

Si el agente detecta que va a escribir CSS que viole estas reglas:

1. **RECHAZA** el código
2. **CORRIGE** automáticamente
3. **REPORTA** qué cambió y por qué

**Ejemplo:**
```
Usuario: "Agrega un badge rojo"

Agente intenta:
.badge-custom { background: #ff0000; }  ← VIOLA

Agente detecta:
❌ Hardcode #ff0000 detectado
Corrigiendo automáticamente...

Agente escribe:
.badge-custom { background: var(--el-primary); }  ← VÁLIDO ✅

Agente reporta:
✅ Badge creado con var(--el-primary)
   (responderá dinámicamente a cambios de paleta)
```

---

## 💡 ESPÍRITU DE LAS CONTENCIONES

**No es sobre reglas por reglas.** Es sobre:

1. **Coherencia** - Todo responde al mismo sistema
2. **Mantenibilidad** - Cambios en un lugar = efecto global
3. **Dinamismo** - Cuando cambias la paleta, TODO cambia
4. **Prevención** - Evitar la deuda técnica CSS que ya limpiamos

---

**Última actualización:** Hoy
**Versión:** 10.0
**Status:** ACTIVO - El agente DEBE validar contra esto SIEMPRE
