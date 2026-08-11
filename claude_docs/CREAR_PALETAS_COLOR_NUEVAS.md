# ¿PUEDO CREAR PALETAS DE COLORES NUEVAS? SÍ, AQUÍ ESTÁ CÓMO

**Respuesta corta:** SÍ, puedes crear paletas de colores nuevas y personalizadas. El sistema está diseñado para esto.

**Pero:** Hay un proceso que debes seguir para que funcione con el resto del sistema (temas dinámicos, accesibilidad, etc.).

---

## 🎨 LA REALIDAD ACTUAL

Después de la refactorización CSS (Phase 1-4):

### ✅ SÍ PUEDES:
- Crear **paletas de colores NUEVAS** y personalizadas
- Los colores responden **dinámicamente** a cambios de tema
- Todos los colores se pueden **cambiar en tiempo real** sin recargar

### ❌ NO PUEDES:
- Hardcodear colores en HEX (`#FF0000`)
- Hardcodear colores en RGB/HSL directamente (`rgb(255,0,0)`)
- Crear colores sin documentarlos en `elite_colors.css`

---

## 🎯 CÓMO CREAR UNA PALETA NUEVA

### PASO 1: Entender la estructura actual

El archivo **central** de colores es:
```
styles/elite_colors.css
```

Contiene:
```css
:root {
    /* Colores tema CLARO */
    --color-primary: ...
    --color-accent: ...
    --color-gray-*: ...
}

.dark-theme-mode {
    /* Colores tema OSCURO */
    --color-primary: ...
    --color-accent: ...
}
```

---

### PASO 2: Crear tu paleta nueva

**Ejemplo: Paleta "Verde Institucional" para módulo de sostenibilidad**

1. **Abre `styles/elite_colors.css`**

2. **En `:root {}`**, agrega tu paleta:

```css
:root {
  /* ========================================
     PRIMARIOS - [Colores existentes] ...
     ======================================== */
  
  /* ========================================
     NUEVOS: PALETA VERDE INSTITUCIONAL
     Para módulo de sostenibilidad/ambiente
     ======================================== */
  --color-green: hsl(120, 100%, 40%);           /* Verde puro */
  --color-green-light: hsl(120, 100%, 60%);    /* Verde claro */
  --color-green-dark: hsl(120, 100%, 25%);     /* Verde oscuro */
  --color-green-rgb: 0, 128, 0;                 /* Para rgba() */
  
  --color-green-sage: hsl(145, 35%, 55%);      /* Verde más suave */
  --color-green-sage-rgb: 112, 138, 114;
  
  --color-green-moss: hsl(110, 40%, 35%);      /* Verde musgo */
  --color-green-moss-rgb: 74, 104, 58;
}
```

3. **En `.dark-theme-mode {}`**, agrega las variantes para tema oscuro:

```css
.dark-theme-mode {
  /* [Colores tema oscuro existentes] ... */
  
  /* PALETA VERDE INSTITUCIONAL (tema oscuro) */
  --color-green: hsl(120, 100%, 50%);           /* Más brillante en oscuro */
  --color-green-light: hsl(120, 100%, 70%);
  --color-green-dark: hsl(120, 100%, 35%);
  --color-green-rgb: 0, 200, 0;
  
  --color-green-sage: hsl(145, 40%, 65%);       /* Ajuste para contraste */
  --color-green-sage-rgb: 150, 170, 155;
  
  --color-green-moss: hsl(110, 40%, 45%);
  --color-green-moss-rgb: 100, 130, 90;
}
```

---

### PASO 3: Usar tu paleta nueva en CSS

**Archivo:** `styles/modules/mi_nuevo_modulo.css`

```css
@layer modules {

/* Elemento con paleta verde */
.sustainability-card {
    background: var(--color-green-light);     /* ✅ USA VARIABLE */
    border: 2px solid var(--color-green);
    color: var(--el-dark);
}

.sustainability-card:hover {
    background: var(--color-green);           /* ✅ USA VARIABLE */
    color: var(--el-white);
}

/* Botón verde */
.btn-green {
    background: var(--color-green);           /* ✅ USA VARIABLE */
    color: var(--el-white);
}

.btn-green:hover {
    background: var(--color-green-dark);      /* ✅ USA VARIABLE */
}

/* Alert verde */
.alert-green {
    background: rgba(var(--color-green-rgb), 0.1);    /* ✅ USA VARIABLE */
    border: 1px solid rgba(var(--color-green-rgb), 0.5);
    color: var(--color-green-dark);
}

}
```

---

## 📊 EJEMPLO REAL: CREAR 3 PALETAS NUEVAS

Imaginemos que quieres:
1. **Paleta Roja** (urgencias/crítico)
2. **Paleta Azul** (información/bienvenida)
3. **Paleta Púrpura** (avanzado/premium)

### EN `elite_colors.css`:

```css
:root {
  /* Colores existentes... */
  
  /* ========================================
     PALETA ROJA - Crítico/Urgencias
     ======================================== */
  --color-red: hsl(0, 100%, 50%);              /* Rojo puro */
  --color-red-light: hsl(0, 100%, 70%);
  --color-red-dark: hsl(0, 100%, 30%);
  --color-red-rgb: 255, 0, 0;
  
  /* ========================================
     PALETA AZUL - Información/Bienvenida
     ======================================== */
  --color-blue: hsl(210, 100%, 50%);           /* Azul puro */
  --color-blue-light: hsl(210, 100%, 70%);
  --color-blue-dark: hsl(210, 100%, 30%);
  --color-blue-rgb: 0, 127, 255;
  
  /* ========================================
     PALETA PÚRPURA - Premium/Avanzado
     ======================================== */
  --color-purple: hsl(270, 100%, 50%);         /* Púrpura puro */
  --color-purple-light: hsl(270, 100%, 70%);
  --color-purple-dark: hsl(270, 100%, 30%);
  --color-purple-rgb: 128, 0, 255;
}

.dark-theme-mode {
  /* [Colores tema oscuro existentes] ... */
  
  --color-red: hsl(0, 100%, 60%);
  --color-red-rgb: 255, 100, 100;
  
  --color-blue: hsl(210, 100%, 60%);
  --color-blue-rgb: 100, 180, 255;
  
  --color-purple: hsl(270, 100%, 60%);
  --color-purple-rgb: 180, 100, 255;
}
```

### LUEGO EN TUS MÓDULOS:

```css
/* Uso paleta roja */
.alert-critical {
    background: rgba(var(--color-red-rgb), 0.1);
    border-left: 4px solid var(--color-red);
    color: var(--color-red-dark);
}

/* Uso paleta azul */
.card-info {
    border-top: 3px solid var(--color-blue);
    background: rgba(var(--color-blue-rgb), 0.05);
}

/* Uso paleta púrpura */
.badge-premium {
    background: var(--color-purple);
    color: var(--el-white);
}
```

---

## 🎨 PALETAS PRE-EXISTENTES (YA DISPONIBLES)

No necesitas crear nuevas si usas estas:

### Colores que YA EXISTEN:

```css
/* PRIMARIOS (sigue al tema) */
--color-primary          /* Azul institucional, dinámico */
--color-primary-light
--color-primary-dark
--color-primary-rgb

/* ACENTOS */
--color-accent           /* Naranja/OrangeRed */
--color-accent-light
--color-accent-dark
--color-accent-rgb

/* NEUTRALES */
--color-white            /* Blanco */
--color-black            /* Negro */

/* GRISES (50, 100, 200, 300, 400, 500, 600, 700, 800, 900) */
--color-gray-50          /* Casi blanco */
--color-gray-900         /* Casi negro */
--color-gray-*-rgb       /* Para rgba() */
```

---

## ⚠️ RESTRICCIONES (IMPORTANTE)

### ❌ PROHIBIDO:

```css
/* NUNCA hagas esto */
.mi-elemento {
    color: #FF0000;              /* ❌ HEX hardcode */
    background: rgb(0, 255, 0);  /* ❌ RGB hardcode */
    border: 1px solid hsl(120, 100%, 50%);  /* ❌ HSL hardcode */
}
```

### ✅ SIEMPRE haz esto:

```css
.mi-elemento {
    color: var(--color-red);                    /* ✅ VARIABLE */
    background: var(--color-green);             /* ✅ VARIABLE */
    border: 1px solid var(--el-primary);        /* ✅ VARIABLE */
}

/* Si necesitas transparencia: */
.mi-elemento {
    background: rgba(var(--color-red-rgb), 0.1);  /* ✅ VARIABLE con rgba() */
}
```

---

## 🔄 CÓMO RESPONDE DINÁMICAMENTE

Una vez que defines tu paleta en `elite_colors.css`:

1. **Usuario cambia el tema** (claro → oscuro)
2. **JavaScript aplica clase** `.dark-theme-mode` a `<html>`
3. **Navegador evalúa variables CSS** en ese selector
4. **Todos tus elementos actualizan automáticamente**

Ejemplo:
```javascript
// JavaScript del sistema
document.documentElement.classList.toggle('dark-theme-mode');
// Ahora todos los --color-* tienen valores oscuros
```

Sin que tengas que tocar nada en tu CSS.

---

## 📋 CHECKLIST: CREAR UNA PALETA NUEVA

- [ ] 1. Abre `styles/elite_colors.css`
- [ ] 2. Define tu paleta en `:root {}`
  - [ ] Color base: `--color-mi-color`
  - [ ] Variante light: `--color-mi-color-light`
  - [ ] Variante dark: `--color-mi-color-dark`
  - [ ] RGB: `--color-mi-color-rgb`
- [ ] 3. Define variantes en `.dark-theme-mode {}`
  - [ ] Mismo colores, valores ajustados para contraste
- [ ] 4. Documenta en comentario:
  ```css
  /* ========================================
     MI NUEVA PALETA - Descripción de uso
     ======================================== */
  ```
- [ ] 5. En tu módulo CSS, usa: `var(--color-mi-color)`
- [ ] 6. NUNCA uses HEX/RGB hardcodeado

---

## 🎯 CASOS DE USO REALES

### Paleta para módulo de exámenes

```css
:root {
  --color-exam: hsl(200, 70%, 50%);           /* Azul examen */
  --color-exam-rgb: 51, 170, 221;
  --color-exam-pass: hsl(120, 70%, 50%);      /* Verde aprobado */
  --color-exam-pass-rgb: 51, 200, 100;
  --color-exam-fail: hsl(0, 70%, 50%);        /* Rojo reprobado */
  --color-exam-fail-rgb: 220, 51, 51;
}
```

### Paleta para módulo de asistencia

```css
:root {
  --color-present: hsl(120, 100%, 40%);       /* Presente = verde */
  --color-present-rgb: 0, 128, 0;
  --color-absent: hsl(0, 100%, 40%);          /* Ausente = rojo */
  --color-absent-rgb: 128, 0, 0;
  --color-late: hsl(40, 100%, 40%);           /* Tarde = naranja */
  --color-late-rgb: 180, 100, 0;
}
```

---

## 📝 CONCLUSIÓN

**SÍ, puedes crear todas las paletas que quieras.**

**Pero:**
- Defínelas en `elite_colors.css`
- Usa `hsl()` o `rgb()`/`rgba()` (NO HEX)
- Incluye variantes para tema oscuro
- USA `var()` siempre en CSS

**Resultado:** Tus colores responden dinámicamente, son accesibles, y se adaptan automáticamente a cambios de tema.

---

**Creado por:** Guía de creación de paletas  
**Fecha:** 2026-08-10  
**Audiencia:** Cualquiera que quiera agregar colores nuevos
