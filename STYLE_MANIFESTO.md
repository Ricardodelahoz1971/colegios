# 🎨 STYLE_MANIFESTO.md: ESTÁNDAR VITRINA 06 (ELITE)

Este documento rige la identidad visual absoluta del sistema. Cualquier desviación es considerada una infracción de auditoría.

## 1. 📐 MÉTRICAS Y GEOMETRÍA FLUIDA
- **Interactividad**: Todo control (input, botón, select) debe tener una altura mínima de **44px**.
- **Radios de Prestigio**: 
    - Controles e inputs: **12px**.
    - Paneles, tarjetas y contenedores maestros: **24px**.
- **Escalabilidad**: Prohibido el uso de píxeles (`px`) fijos para fuentes o layouts. Uso obligatorio de `rem`, `%` y la función `clamp()` para adaptabilidad soberana.
- **Rendimiento Visual**: Toda imagen debe declarar explícitamente `width` y `height` para evitar saltos de layout (CLS).

## 2. 🎨 PALETA Y LÓGICA DE COLOR (CERO HEX)
- Queda prohibido el uso de valores HEX (#). Uso exclusivo de variables CSS.
- **Primario**: `var(--el-primary)` (OrangeRed Institucional).
- **Glassmorphism 2.0**: Uso de `rgba(var(--el-bg-rgb), 0.7)` con `backdrop-filter: blur(12px)`. Se debe incluir un **Edge Glow** (borde superior de 1px) y opcionalmente `backdrop-noise`.
- **Física de Sombras**: Las sombras deben tener un matiz del color primario: `box-shadow: 0 8px 30px rgba(var(--el-primary-rgb), 0.12)`.

## 3. ⌨️ CSS Y ARQUITECTURA SOBERANA
- **Metodología**: BEM-Elite obligatorio (`.component`, `.component__element`, `.component--modifier`).
- **Cascada Controlada**: Uso mandatorio de `@layer` para organizar el CSS en: `reset`, `base`, `components`, `utilities`.
- **Propiedades Lógicas**: Prohibido `left`, `right`, `margin-left`. Uso de `inset-inline-start`, `margin-inline-start`, etc.
- **Tipografía**: Uso exclusivo de `var(--el-font-institutional)`.
- **Transiciones**: Efecto "Seda" con `cubic-bezier(0.4, 0, 0.2, 1)`.

## 4. 🚫 VETOS CRÍTICOS
- **Cero Estilos Inline**: El atributo `style` en HTML es motivo de purga.
- **Cero !important (Regla de Oro)**: Queda terminantemente prohibido el uso de `!important` en cualquier código nuevo. Los archivos legacy se mantendrán por estabilidad, pero la purga debe ser natural. Solo permitido en clases utilitarias de estado debidamente justificadas.
- **Blindaje de Terceros**: Librerías externas deben ser intervenidas para adoptar este ADN.
- **Cero Hardcoding**: Prohibido cualquier valor fijo que no esté tokenizado.

---
**CERTIFICACIÓN**: Documento integrado con Skills de Auditoría y Rendimiento. Pipod.
