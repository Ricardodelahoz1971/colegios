# Especificación de Diseño: Gestión Multijornada y Roles Especializados

Este documento detalla la arquitectura propuesta para la futura ampliación del sistema, permitiendo la asignación y restricción de coordinadores, docentes y estudiantes por jornadas específicas (Mañana, Tarde, Única, Nocturna).

---

## 🏛️ 1. Estructura de Base de Datos Propuesta

Para soportar de manera nativa múltiples jornadas sin duplicar lógica, se propone el siguiente esquema relacional:

```mermaid
erDiagram
    JORNADAS ||--o{ CURSOS : "pertenece"
    JORNADAS ||--o{ USUARIOS : "asociado_a"
    USUARIOS ||--o{ CARGA_ACADEMICA : "dicta"
    CURSOS ||--o{ CARGA_ACADEMICA : "recibe"

    JORNADAS {
        int id PK
        varchar nombre "Ej. Mañana, Tarde, Única"
        time hora_inicio
        time hora_fin
        int estado "1: Activa, 0: Inactiva"
    }

    CURSOS {
        int id PK
        varchar nombre_curso
        int nivel_id
        int tutor_id FK
        int jornada_id FK
    }

    USUARIOS {
        int id PK
        varchar usuario
        varchar nombre
        int rol_id FK
        int jornada_id FK "NULL indica cobertura institucional global"
    }
```

---

## 🔒 2. Lógica de Permisos e Inferencia (Reglas de Negocio)

### A. Visibilidad del Coordinador por Jornada
Cuando un usuario tiene `rol_id = 2` (Coordinador) y su `jornada_id` es no nulo (ej. `jornada_id = 1` - Jornada Mañana):
1.  **Filtro en Cursos**: Las consolas de *Cursos* y *Carga Académica* (`Protocolo Atlas`) solo listarán aquellos cursos donde `cursos.jornada_id = 1`.
2.  **Seguridad a Nivel de Controlador (API)**: Cualquier acción de edición (`guardar_curso`, `asignar_carga`, `eliminar_carga`) validará en el backend que el curso afectado pertenezca a la misma jornada del coordinador autenticado:
    ```php
    if ($_SESSION['jornada_id'] !== null && $curso['jornada_id'] !== $_SESSION['jornada_id']) {
        header('HTTP/1.1 403 Forbidden');
        die(json_encode(['success' => false, 'message' => 'Acceso denegado a esta jornada']));
    }
    ```

### B. Control de Carga de Docentes
*   Un docente puede dictar clases en múltiples jornadas. Por lo tanto, en la tabla de `usuarios` la jornada del docente sirve como jornada de contratación principal, pero la tabla de vinculación `carga_academica` permite asociarlo a cursos de cualquier jornada.
*   El cálculo de horas del docente se mantendrá global en el Power Header de Atlas, pero segmentado por jornada en los reportes administrativos.
