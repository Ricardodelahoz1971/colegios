# 🎨 CSS COMPONENTS - Referencia de Componentes Disponibles

**Este documento lista TODOS los componentes, colores, animaciones y utilidades disponibles.**

Úsalo como referencia cuando necesites algo ya existe.

---

## 🎨 PALETA DE COLORES

### Colores Primarios

```css
--color-primary: hsl(224, 78%, 46%);       /* Azul institucional */
--color-primary-light: hsl(224, 78%, 60%);
--color-primary-dark: hsl(224, 78%, 35%);
```

**Uso:**
```css
color: var(--color-primary);
background: rgba(var(--el-primary-rgb), 0.1);
```

---

### ⚠️ NOTA: Colores de Estado Eliminados

Se eliminaron completamente los colores de estado (success, danger, info, warning) para centralizar TODO en `--el-primary`. 

**Por qué:** Todos los elementos, sin importar el tipo de mensaje (éxito, error, info), ahora responden al color primario seleccionado dinámicamente. Esto asegura consistencia total con los cambios de paleta.

---

### Escala de Grises

```css
--color-gray-50:   /* Casi blanco - Fondos claros */
--color-gray-100:  /* Muy claro */
--color-gray-200:  /* Claro - Bordes */
--color-gray-300:  /* Claro-medio - Bordes visibles */
--color-gray-400:  /* Medio - Texto muted */
--color-gray-500:  /* Medio-oscuro - Texto muted */
--color-gray-600:  /* Oscuro */
--color-gray-700:  /* Muy oscuro */
--color-gray-800:  /* Oscuro institucional */
--color-gray-900:  /* Casi negro - Texto principal */
```

**Uso:**
```css
border-color: var(--color-gray-300);
color: var(--color-gray-500);
```

---

## ✨ ANIMACIONES

### Fade (Entrada/Salida)

```css
animation: fadeIn 0.3s ease-out;
animation: fadeOut 0.3s ease-out;
```

**Uso:** Elementos que aparecen/desaparecen

---

### Pulse (Pulsación)

```css
animation: pulse 2s infinite;
```

**Uso:** Indicadores de estado (online/offline), notificaciones activas

---

### Slide (Deslizamiento)

```css
animation: slideInUp 0.3s ease-out;
animation: slideInDown 0.3s ease-out;
animation: slideInLeft 0.3s ease-out;
animation: slideInRight 0.3s ease-out;
```

**Uso:** Menús desplegables, sidebars, modales

---

### Rotate (Rotación)

```css
animation: rotate 3s linear infinite;
animation: rotateSlow 6s linear infinite;
```

**Uso:** Carga (spinners), iconos animados

---

### Bounce (Rebote)

```css
animation: bounce 0.6s ease-in-out infinite;
```

**Uso:** Elementos que saltan, notificaciones activas

---

### Shake (Sacudida)

```css
animation: shake 0.3s ease-out;
```

**Uso:** Errores, validación fallida

---

### Glow (Brillo)

```css
animation: glow 2s ease-in-out infinite;
```

**Uso:** Elementos destacados, foco atención

---

### Scale (Escalado)

```css
animation: scaleIn 0.3s ease-out;
animation: scaleOut 0.3s ease-out;
```

**Uso:** Modal appear/disappear, botones pressed

---

## 📐 ESPACIADO Y GEOMETRÍA

### Alturas, Radios y Bordes

```css
--el-height-elite: 2.75rem;        /* 44px - Controles */
--el-radius-sub: 0.5rem;           /* 8px - Botones, inputs */
--el-radius-main: 1.5rem;          /* 24px - Cards, paneles */
--el-radius-pill: 50rem;           /* Píldora - Botones redondos */

/* Bordes laterales (línea de identidad) */
border-inline-start: 0.25rem solid var(--el-primary);  /* 4px - Estándar en tarjetas */
```

**Uso:**
```css
.btn { height: var(--el-height-elite); }
.input { border-radius: var(--el-radius-sub); }
.card { border-radius: var(--el-radius-main); }
```

---

### Transiciones

```css
--el-transition-premium: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
--el-transition-organic: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
--el-transition-fast: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
```

**Uso:**
```css
.elemento { transition: var(--el-transition-fast); }
.elemento-lento { transition: var(--el-transition-organic); }
```

---

## 📱 BREAKPOINTS

```css
--breakpoint-xs: 0;         /* Todos */
--breakpoint-sm: 576px;     /* Tablets pequeñas */
--breakpoint-md: 768px;     /* Tablets grandes */
--breakpoint-lg: 992px;     /* Laptops */
--breakpoint-xl: 1200px;    /* Monitores */
--breakpoint-2xl: 1400px;   /* Monitores grandes */
```

**Uso:**
```css
@media (max-width: var(--breakpoint-md)) {
    /* Mobile y tablet pequeña */
}

@media (min-width: var(--breakpoint-lg)) {
    /* Desktop y arriba */
}
```

---

## 🔢 Z-INDEX HIERARCHY

```css
--z-dropdown: 100;          /* Dropdowns */
--z-tooltip: 110;           /* Tooltips */
--z-sticky: 200;            /* Sticky headers */
--z-fixed: 210;             /* Fixed banners */
--z-overlay: 500;           /* Overlays */
--z-modal-backdrop: 1000;   /* Modal backdrop */
--z-modal: 1001;            /* Modal content */
--z-notification: 1100;     /* Alerts, toasts */
--z-navbar: 1200;           /* Top navigation */
--z-popover: 1300;          /* Popovers */
```

**Uso:**
```css
.modal { z-index: var(--z-modal); }
.dropdown { z-index: var(--z-dropdown); }
```

---

## 🎯 COMPONENTES UI DISPONIBLES

### Botones

**Clases:**
- `.btn` - Botón base (Bootstrap)
- `.btn-primary` - Botón primario
- `.btn-danger` - Botón de peligro
- `.btn-elite-icon` - Botón circular con icono
- `.btn-action-pill-elite` - Botón acción en forma de píldora

**Ejemplo:**
```html
<button class="btn btn-primary">Guardar</button>
<button class="btn-elite-icon"><i class="icon">✓</i></button>
```

---

### Inputs y Formularios

**Clases:**
- `.form-control` - Input estándar (Bootstrap)
- `.input-elite` - Input customizado
- `.swal2-input` - Input en modales (auto-styled)
- `.form-select` - Select estándar

**Ejemplo:**
```html
<input type="text" class="form-control" placeholder="Nombre">
<select class="form-select">
    <option>Opción 1</option>
</select>
```

---

### Tarjetas

**Clases:**
- `.card` - Tarjeta estándar (Bootstrap)
- `.metric-card-elite` - Tarjeta de métrica
- `.card-perseus` - Tarjeta de seguimiento

**Ejemplo:**
```html
<div class="card">
    <div class="card-body">Contenido</div>
</div>
```

---

### Tablas

**Clases:**
- `.table` - Tabla estándar (Bootstrap)
- `.tabla-maestra` - Tabla elite
- `.table-hover` - Tabla con hover

**Ejemplo:**
```html
<table class="table tabla-maestra">
    <thead>
        <tr><th>Encabezado</th></tr>
    </thead>
</table>
```

---

### Modales

**Clases:**
- `.modal` - Modal estándar (Bootstrap)
- `.swal2-container` - Modal SweetAlert2

**Ejemplo:**
```html
<div class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            Contenido
        </div>
    </div>
</div>
```

---

### Alertas

**Clases:**
- `.alert` - Alerta estándar
- `.alert-success` - Alerta éxito (verde)
- `.alert-danger` - Alerta error (rojo)
- `.alert-warning` - Alerta advertencia (amarillo)
- `.alert-info` - Alerta info (cyan)

**Ejemplo:**
```html
<div class="alert alert-success">¡Guardado!</div>
```

---

### Badges

**Clases:**
- `.badge` - Badge genérico
- `.badge-primary` - Badge primario
- `.badge-success` - Badge éxito

**Ejemplo:**
```html
<span class="badge badge-primary">Nuevo</span>
```

---

## 🔧 UTILIDADES

### Visibilidad

```css
.d-none       /* display: none */
.d-block      /* display: block */
.d-flex       /* display: flex */
.d-grid       /* display: grid */
.u-hidden     /* display: none */
.u-visible    /* opacity: 1 */
```

---

### Flexbox

```css
.d-flex                 /* display: flex */
.justify-content-center /* justify-content: center */
.align-items-center     /* align-items: center */
.gap-3                  /* gap: 1rem */
.flex-wrap              /* flex-wrap: wrap */
```

---

### Espaciado

```css
.m-0 to .m-5           /* margin */
.p-0 to .p-5           /* padding */
.mt-3, .mb-3, .ms-3    /* margin top/bottom/start */
.pt-3, .pb-3, .ps-3    /* padding top/bottom/start */
.mx-auto                /* margin horizontal auto */
.px-elite-1-5           /* padding inline 1.5rem */
```

---

### Tamaños Fijos

```css
.size-48                /* 3rem x 3rem */
.size-50                /* 3.125rem x 3.125rem */
.size-180               /* 11.25rem x 11.25rem */
.w-fixed-280            /* width: 17.5rem */
.h-35, .h-38            /* height fija */
```

---

### Tipografía

```css
.text-xs                /* font-size: 0.75rem */
.text-sm                /* font-size: 0.85rem */
.text-base              /* font-size: 0.9rem (default) */
.text-lg                /* font-size: 1rem */
.text-xl                /* font-size: 1.2rem */
.text-2xl               /* font-size: 1.5rem */
.text-primary           /* color: primary */
.text-muted             /* color: muted */
.text-truncate-2        /* 2 líneas max */
```

---

### Sombras

```css
var(--el-shadow-sm)     /* Sombra pequeña */
var(--el-shadow-md)     /* Sombra media */
var(--el-shadow-lg)     /* Sombra grande */
```

**Uso:**
```css
.card { box-shadow: var(--el-shadow-md); }
```

---

### Bordes

```css
.border                 /* border: 1px solid */
.border-primary         /* border-color: primary */
.rounded                /* border-radius */
.rounded-elite          /* border-radius: var(--el-radius-sub) */
.rounded-elite-lg       /* border-radius: var(--el-radius-main) */
```

---

## 📚 REFERENCIA RÁPIDA

### "Necesito..."

| Necesito | Busco | Ejemplo |
|----------|-------|---------|
| Un color | `styles/elite_colors.css` | `var(--color-primary)` |
| Una animación | `styles/elite_animations.css` | `animation: fadeIn` |
| Un breakpoint | `styles/elite_breakpoints.css` | `@media (max-width: var(--breakpoint-md))` |
| Un z-index | `styles/elite_z_index.css` | `z-index: var(--z-modal)` |
| Un radio | `elite_colors.css` | `border-radius: var(--el-radius-main)` |
| Una transición | `elite_themes.css` | `transition: var(--el-transition-fast)` |
| Un espaciado | Bootstrap utilities | `margin: 1rem; padding: 0.75rem;` |
| Un tamaño fijo | `utilities.css` | `width: var(--el-height-elite)` |

---

## 🚫 NO HAGAS

```css
/* ❌ Hardcoded colors */
color: #ff4500;
background: #ffffff;

/* ❌ Hardcoded sizes */
border-radius: 12px;
padding: 1rem;

/* ❌ Custom animations */
@keyframes custom { }

/* ❌ Custom z-index */
z-index: 9999;

/* ❌ !important */
color: red !important;

/* ❌ Inline styles */
<div style="color: red;"></div>
```

---

**Última actualización:** Hoy
**Versión:** 1.0
**Status:** Documento activo - Referencia para todos los developers
