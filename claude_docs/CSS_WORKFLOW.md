# 🚀 CSS WORKFLOW - Proceso Obligatorio para Agregar Estilos

**Este documento define el proceso ESTÁNDAR para agregar o modificar CSS en el proyecto.**

Todos los developers deben seguir este workflow. No hay excepciones.

---

## 📋 PASO 1: ¿Necesito un color nuevo?

### Opción A: El color ya existe

1. **Abre `styles/elite_colors.css`**
2. **Busca si existe el color:**
   - `--color-primary` (ÚNICO color con variantes - responde a paleta)
   - `--color-gray-50` a `--color-gray-900` (grises neutros)
   - `--color-accent` (naranja/acentuación)

3. **Si lo encontraste:** USA LA VARIABLE
   ```css
   .mi-elemento {
       color: var(--color-success);  /* ✅ CORRECTO */
       background: var(--el-primary);
   }
   ```

### Opción B: Necesito un color nuevo

1. **Abre `styles/elite_colors.css`**

2. **En `:root {}`** (línea ~10), agrega tu color:
   ```css
   --color-nuevo: hsl(XXX, XX%, XX%);         /* Color base */
   --color-nuevo-light: hsl(XXX, XX%, XX%);  /* Variante clara */
   --color-nuevo-dark: hsl(XXX, XX%, XX%);   /* Variante oscura */
   --color-nuevo-rgb: 123, 45, 67;           /* Para rgba() */
   ```

3. **En `.dark-theme-mode {}`** (línea ~150), agrega la variante oscura:
   ```css
   --color-nuevo: hsl(XXX, XX%, XX%);
   --color-nuevo-rgb: 123, 45, 67;
   ```

4. **En tu CSS, usa:**
   ```css
   .mi-elemento {
       color: var(--color-nuevo);
       background: rgba(var(--color-nuevo-rgb), 0.1);
   }
   ```

---

## 📋 PASO 2: ¿Necesito una animación?

### Opción A: La animación ya existe

1. **Abre `styles/elite_animations.css`**

2. **Busca si existe:**
   - `@keyframes pulse` - Pulsación con sombra
   - `@keyframes fadeIn` - Entrada suave
   - `@keyframes slideInUp` - Deslizar desde abajo
   - `@keyframes rotate` - Rotación continua
   - `@keyframes bounce` - Rebote
   - `@keyframes shake` - Sacudida
   - `@keyframes glow` - Brillo

3. **Si la encontraste:** USA LA ANIMACIÓN
   ```css
   .mi-elemento {
       animation: pulse 2s infinite;  /* ✅ CORRECTO */
   }
   ```

### Opción B: Necesito una animación nueva

1. **Abre `styles/elite_animations.css`**

2. **Dentro de `@layer animations {}`**, agrega:
   ```css
   @keyframes mi-animacion {
       0% { /* estado inicial */ }
       50% { /* estado intermedio */ }
       100% { /* estado final */ }
   }
   ```

3. **Usa nombres descriptivos en kebab-case:**
   ```css
   /* ✅ BIEN */
   @keyframes slide-in-left
   @keyframes fade-out
   @keyframes rotate-360

   /* ❌ MAL */
   @keyframes anim1
   @keyframes test
   ```

4. **En tu CSS:**
   ```css
   .mi-elemento {
       animation: mi-animacion 0.5s ease forwards;
   }
   ```

---

## 📋 PASO 3: ¿Necesito un breakpoint?

### ❌ NUNCA hardcodees breakpoints

```css
/* ❌ INCORRECTO */
@media (max-width: 768px) { }
@media (min-width: 992px) { }

/* ✅ CORRECTO */
@media (max-width: var(--breakpoint-md)) { }
@media (min-width: var(--breakpoint-lg)) { }
```

### Breakpoints disponibles

En `styles/elite_breakpoints.css`:

```css
--breakpoint-xs: 0;        /* Todos */
--breakpoint-sm: 576px;    /* Tablets pequeñas */
--breakpoint-md: 768px;    /* Tablets grandes */
--breakpoint-lg: 992px;    /* Laptops */
--breakpoint-xl: 1200px;   /* Monitores */
--breakpoint-2xl: 1400px;  /* Monitores grandes */
```

### Si necesitas un breakpoint nuevo

1. **Abre `styles/elite_breakpoints.css`**
2. **Agrega en `:root {}`:**
   ```css
   --breakpoint-custom: 1600px;
   ```
3. **Usa:**
   ```css
   @media (min-width: var(--breakpoint-custom)) { }
   ```

---

## 📋 PASO 4: ¿Necesito un z-index?

### ❌ NUNCA hagas `z-index: 9999`

```css
/* ❌ INCORRECTO */
.modal { z-index: 9999; }
.dropdown { z-index: 100; }
.tooltip { z-index: 1000; }

/* ✅ CORRECTO */
.modal { z-index: var(--z-modal); }
.dropdown { z-index: var(--z-dropdown); }
.tooltip { z-index: var(--z-tooltip); }
```

### Z-index disponibles

En `styles/elite_z_index.css`:

```css
--z-dropdown: 100;
--z-tooltip: 110;
--z-sticky: 200;
--z-fixed: 210;
--z-overlay: 500;
--z-modal-backdrop: 1000;
--z-modal: 1001;
--z-notification: 1100;
--z-navbar: 1200;
--z-popover: 1300;
```

### Si necesitas un nuevo nivel

1. **Abre `styles/elite_z_index.css`**
2. **Agrega nuevo nivel en `:root {}`:**
   ```css
   --z-custom: 1500;
   ```
3. **Documenta por qué lo necesitas en un comentario**

---

## 📋 PASO 5: ¿Necesito border-radius o línea lateral?

### Radios disponibles

En `elite_colors.css`:

```css
--el-radius-sub: 0.5rem;   /* 8px - Controles, botones */
--el-radius-main: 1.5rem;  /* 24px - Paneles, tarjetas */
--el-radius-pill: 50rem;   /* Píldora - Botones redondos */
```

### Línea lateral (identidad visual)

**TODOS** los componentes principales (tarjetas, alertas, secciones) usan:

```css
border-inline-start: 0.25rem solid var(--el-primary);  /* 4px siempre */
```

### USA LAS VARIABLES

```css
/* ✅ CORRECTO */
.input { border-radius: var(--el-radius-sub); }
.card { 
    border-radius: var(--el-radius-main);
    border-inline-start: 0.25rem solid var(--el-primary);
}
.btn-round { border-radius: var(--el-radius-pill); }

/* ❌ INCORRECTO */
.input { border-radius: 0.5rem; }
.card { border-radius: 24px; border-left: 5px solid #2563eb; }
```

---

## 📋 PASO 6: ¿Necesito transiciones?

### Transiciones disponibles

En `elite_themes.css`:

```css
--el-transition-premium: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
--el-transition-organic: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
--el-transition-fast: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
```

### USA LAS VARIABLES

```css
/* ✅ CORRECTO */
.elemento {
    transition: var(--el-transition-fast);
}

.elemento {
    transition: background 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* ❌ INCORRECTO */
.elemento {
    transition: all 0.3s ease;  /* Easing genérico */
}
```

---

## 📋 PASO 7: Agregar estilos nuevos - UBICACIÓN

### Por módulo

Si tus estilos son **específicos de una funcionalidad:**

```
styles/modules/[nombre-modulo].css

Ejemplos:
- styles/modules/nueva_funcion.css
- styles/modules/editor_nuevo.css
```

### Por componente

Si tus estilos son **reutilizables:**

```
styles/[tipo]_[nombre].css

Ejemplos:
- styles/inputs_customization.css
- styles/buttons_variants.css
```

### NUNCA

```
/* ❌ NO hagas esto */
- Agregar estilos inline (style="...")
- Crear archivos sin estructura
- Mezclar módulos en un archivo
```

---

## 📋 PASO 8: Checklist antes de commit

```
[ ] ¿Usé variables de color? (NO hardcodes #fff)
[ ] ¿Usé variables de animación? (NO @keyframes duplicadas)
[ ] ¿Usé variables de breakpoints? (NO 768px hardcodeado)
[ ] ¿Usé variables de z-index? (NO z-index: 9999)
[ ] ¿Usé variables de radius? (NO border-radius: 0.5rem)
[ ] ¿Usé variables de transición? (NO transition: all 0.3s ease)
[ ] ¿Mi código está limpio? (SIN !important)
[ ] ¿Probé en responsive? (Móvil, tablet, desktop)
[ ] ¿Probé en ambos temas? (Light y dark)
[ ] ¿Probé con todas las paletas? (Verde, rojo, azul, etc.)
```

---

## 🚫 PROHIBICIONES ABSOLUTAS

| ❌ NO HAGAS | ✅ HAGO EN SU LUGAR |
|-----------|-------------------|
| `color: #ff4500;` | `color: var(--color-accent);` |
| `background: #ffffff;` | `background: var(--el-white);` |
| `border-radius: 12px;` | `border-radius: var(--el-radius-sub);` |
| `z-index: 9999;` | `z-index: var(--z-modal);` |
| `transition: all 0.3s;` | `transition: var(--el-transition-fast);` |
| `@media (max-width: 768px)` | `@media (max-width: var(--breakpoint-md))` |
| `!important` | Arreglá la especificidad |
| `style="color: red"` | Define en CSS externo |
| `@keyframes fade {}` dos veces | Usa la existente en elite_animations.css |

---

## 📞 EJEMPLOS REALES

### Ejemplo 1: Crear un botón nuevo

```css
/* styles/modules/nuevo_boton.css */
@layer modules {

.btn-nueva-funcion {
    /* Dimensiones */
    padding: 0.75rem 1.5rem;
    height: var(--el-height-elite);
    
    /* Colores - VARIABLES */
    background-color: var(--el-primary);
    color: var(--el-white);
    border: 1px solid var(--el-primary);
    
    /* Bordes - VARIABLES */
    border-radius: var(--el-radius-sub);
    
    /* Animaciones - VARIABLES */
    transition: var(--el-transition-fast);
    
    /* Estado */
    cursor: pointer;
}

.btn-nueva-funcion:hover {
    background-color: var(--color-primary-dark);
    border-color: var(--color-primary-dark);
}

.btn-nueva-funcion:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgb(var(--el-primary-rgb) / 0.3);
}

}
```

### Ejemplo 2: Crear una animación

```css
/* styles/elite_animations.css */
@layer animations {

@keyframes slide-in-diagonal {
    from {
        opacity: 0;
        transform: translateX(-1rem) translateY(1rem);
    }
    to {
        opacity: 1;
        transform: translateX(0) translateY(0);
    }
}

}

/* styles/modules/nueva_seccion.css */
@layer modules {

.nueva-seccion {
    animation: slide-in-diagonal 0.5s ease-out;
}

}
```

### Ejemplo 3: Responsive con breakpoints

```css
/* styles/modules/responsive_grid.css */
@layer modules {

.grid-nueva {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

/* Tablet y arriba */
@media (min-width: var(--breakpoint-md)) {
    .grid-nueva {
        grid-template-columns: 1fr 1fr;
    }
}

/* Desktop y arriba */
@media (min-width: var(--breakpoint-lg)) {
    .grid-nueva {
        grid-template-columns: 1fr 1fr 1fr;
    }
}

}
```

---

## 🎯 RESUMEN RÁPIDO

1. **Color nuevo?** → Agrega a `elite_colors.css`
2. **Animación nueva?** → Agrega a `elite_animations.css`
3. **Breakpoint nuevo?** → Agrega a `elite_breakpoints.css`
4. **Z-index nuevo?** → Agrega a `elite_z_index.css`
5. **Mi CSS?** → En `styles/modules/[nombre].css` o `styles/[tipo]_[nombre].css`
6. **Antes de commit?** → Pasa el checklist

**= CSS LIMPIO, CONSISTENTE Y ESCALABLE**

---

**Última actualización:** Hoy
**Versión:** 1.0
**Status:** Documento activo - Todos deben seguir este workflow
