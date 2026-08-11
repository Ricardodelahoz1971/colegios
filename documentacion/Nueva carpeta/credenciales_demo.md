# 🔑 Credenciales del Ecosistema Sintético (V2)

Este documento es una hoja de trucos para acceder rápidamente a los diferentes perfiles del sistema de pruebas. Todas las cuentas comparten la misma contraseña maestra para facilitar la validación.

> [!IMPORTANT]
> **Contraseña Universal para todas las cuentas:** `1234`

## 👔 Personal Administrativo y Docente

| ID | Nombre Completo | Usuario (Login) | Rol Asignado | Especificación de Acceso |
|:---:|---|:---:|---|---|
| 1 | Director General | **`admin`** | Administrador | **Control de Sistemas** (Acceso Ares total) |
| 101 | Rector Superior | **`rector`** | Rector | **Máxima Autoridad** (Configuración completa, sin Ares) |
| 100 | Pedro Perez | **`CPP`** | Coordinador | **Gestión Académica** (Jornada y Académico, sin Branding) |
| 3 | Jorge Pérez | **`LJP`** | Docente (Matemáticas) | Catedrático (Silo de Notas de Matemáticas en 1A) |
| 4 | Manuel Abello | **`LMA`** | Docente (Español) | Tutor (Acceso Total a Sábana de Notas en 1A) |
| 5 | Erick Martínez | **`LEM`** | Docente (Historia) | Catedrático |
| 6 | Ana Arias | **`LAA`** | Docente (Ciencias) | Catedrático |
| 7 | Ricardo Rojas | **`LRR`** | Docente (Inglés) | Catedrático |
| 8 | Carlos Ramírez | **`LCR`** | Docente (Ed. Física) | Catedrático |

---

## 👨‍🎓 Alumnos Matriculados (Muestra)
Se generaron 90 estudiantes en total (15 por curso). El patrón de usuario siempre es la letra **E** + **Iniciales** + **ID numérico**.
A continuación, una muestra rápida para pruebas de vista de estudiante (si el rol está habilitado):

| ID | Estudiante (Aleatorio) | Usuario (Login) | Curso |
|:---:|---|:---:|:---:|
| 9 | Andrés Martínez | **`EAM9`** | 1A |
| 26 | Valentina Sánchez | **`EVS26`** | 1B |
| 45 | Santiago Rodríguez | **`ESR45`** | 2A |
| ... | *y 87 más...* | | |

> [!TIP]
> **Estrategia de Pruebas Cruzadas**
> Inicie sesión en modo incógnito con un Docente (ej: `LJP`) y en su ventana normal con el Coordinador (`CMG`). Esto le permitirá ver en tiempo real cómo cambia la interfaz, la barra de navegación y los permisos de la Sábana de Notas dependiendo de los privilegios.
