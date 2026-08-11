# Plan de Implementación: Sistema de Auditoría "Ojo de lince"

Este plan dota al sistema de una memoria institucional completa. Cada paso que se dé en la plataforma quedará registrado con autor, fecha y detalles técnicos.

## Decisiones de Diseño Tomadas
- Se registrará absolutamente TODO: Login, Logout, Ediciones, Eliminaciones, Cambios de Estética y Mensajería Masiva.
- Solo los usuarios con el permiso `auditoria` (Administrador/Director) podrán ver la bitácora.
- Integración global vía `db.php`.

## Pasos para la Ejecución Futura

### 1. Núcleo de Auditoría
- Vincular `logica/auditoria.php` en `db.php`.

### 2. Integración de Eventos
- **Login/Logout**: Registrar éxito de entrada y salida.
- **Identidad**: Registrar cambios en branding, logos y temas.
- **Académico**: Registrar nuevas matrículas y cada edición de expediente de alumno.
- **Comunicación**: Registrar transmisiones masivas.

### 3. Interfaz de Monitorización
- Refinar `vistas/auditoria.php` con filtros de búsqueda avanzada y badges de colores institucionalizados.
