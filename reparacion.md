# 🛠️ PLAN DE REPARACIÓN: UNIFICACIÓN DE ARQUITECTURA ÉLITE

Este documento detalla la hoja de ruta técnica para resolver las 69 violaciones de integridad detectadas y consolidar el sistema bajo el estándar Vitrina 06.

---

## 📍 OBJETIVO: CERO REDUNDANCIA
Eliminar la guerra de especificidad entre `components.css` y `ui_kit.css`, asegurando que cada componente tenga un único origen de verdad.

---

## ⚖️ MATRIZ DE SOBERANÍA (ORÍGENES DE VERDAD)
Para eliminar la redundancia, cada estilo tendrá un único hogar oficial:
*   **Tokens y Variables** (`--el-*`): `elite_themes.css`
*   **Componentes Maestros** (`.btn-elite`, `.card-elite`, etc): `ui_kit.css`
*   **Utilidades de Precisión**: `utilities.css`
*   **Lógica de Módulo**: `modules/[nombre].css` (Ej: `login.css`, `sidebar.css`)
*   **Estado de components.css**: MARCADO PARA DEPURACIÓN (LEGACY).

---

## 🏗️ FASES DE EJECUCIÓN

### FASE 1: SOBERANÍA DE CONTROLES (MÓDULO LOGIN)
*   **Selectores Críticos**: `.btn-elite`, `.input-elite`, `.input-icon-elite`.
*   **Procedimiento**:
    1.  Validar y unificar estilos en `ui_kit.css` (Métrica 44px, Radio 12px).
    2.  Eliminar definiciones duplicadas en `components.css` y `login.css`.
*   **Meta**: Reducir redundancias 1 a 6 del reporte de auditoría.

### FASE 2: UNIFICACIÓN DE NAVEGACIÓN Y ESTRUCTURA
*   **Selectores Críticos**: `.menu-item-elite`, `.sidebar-container`, `.submenu-elite`, `.arrow-icon`.
*   **Procedimiento**:
    1.  Centralizar la lógica de menús en `ui_kit.css`.
    2.  Purgar el bloque de navegación de `components.css` y `sidebar.css`.
*   **Meta**: Reducir redundancias 20 a 25 del reporte de auditoría.

### FASE 3: SANEAMIENTO DE UTILIDADES Y PALETAS
*   **Selectores Críticos**: `.fs-nano`, `.palette-*`, `.live-sim-*`.
*   **Procedimiento**:
    1.  Mover utilidades tipográficas a `utilities.css`.
    2.  Mover paletas de demostración a `elite_showroom.css`.
    3.  Eliminar duplicados en `components.css`.
*   **Meta**: Reducir redundancias 8 a 19 y 37 a 46 del reporte de auditoría.

### FASE 4: ALINEACIÓN DE LA CASCADA (CLEANUP FINAL)
*   **Archivos**: `index.php`, `dashboard.php`.
*   **Procedimiento**: Reordenar la carga de CSS para que la cascada funcione por herencia natural y no por `!important`.
*   **Meta**: Eliminar la necesidad de sobre-especificación en módulos específicos.

---

## 🛡️ PROTOCOLO DE CIERRE POR PASO
1.  Aplicación del cambio.
2.  **Ejecución de `antigravity_auditor.php`**.
3.  Presentación del reporte con disminución de violaciones.

---
**CERTIFICACIÓN**: Este plan se ejecuta bajo la supervisión directa del Ingeniero Ricardo y bajo las leyes del archivo `esttilobuenos122.md`.
