# Plan de Implementación: Mensajería Institucional (Stand-by)

Este documento detalla la hoja de ruta técnica para la integración de un sistema de comunicaciones internas tipo "WhatsApp" dentro del sistema escolar, optimizado para el entorno actual (PHP/SQLite).

## 🛡️ Pilares del Sistema
1.  **Seguridad & Jerarquía**: Control de quién puede mensajear a quién basado en los roles actuales.
2.  **Fluidez Táctica**: Sensación de tiempo real sin necesidad de servidores adicionales (WhatsApp-Style).
3.  **Trazabilidad**: Registro exacto de despachos, recepciones y lecturas.

---

## 🏛️ Propuesta de Arquitectura de Datos (SQLite)

### Tabla: `mensajes`
| Campo | Tipo | Descripción |
| :--- | :--- | :--- |
| `id` | INTEGER PRIMARY KEY | Identificador único del despacho. |
| `remitente_id` | INTEGER | ID del usuario que envía. |
| `destinatario_id` | INTEGER | ID del usuario (o '0' para broadcast). |
| `asunto` | TEXT | Título breve de la misión. |
| `contenido` | TEXT | Cuerpo del mensaje. |
| `prioridad` | INTEGER | 1: Normal, 2: Importante, 3: 🚨 Urgente. |
| `leido` | INTEGER | 0: No leído, 1: Leído. |
| `fecha_envio` | DATETIME | Timestamp automático del servidor. |

---

## ⚙️ Estrategia Técnica: Polling AJAX (Ruta Recomendada)

Para evitar la complejidad de WebSockets en XAMPP, el sistema utilizará un **"Cartero Digital"** basado en AJAX:

1.  **Script de Monitoreo (JS)**: Una función ligera en el Dashboard que consulta cada 10 segundos al archivo `check_mensajes.php`.
2.  **Carga Dinámica**: Si hay mensajes nuevos, el sistema actualiza la burbuja de notificación en el Sidebar y el área de chat sin recargar la página.
3.  **Desglose Especial**: Los mensajes de prioridad "Urgente" dispararán un SweetAlert automático al destinatario.

---

## 🏛️ Matriz de Comunicaciones Autorizadas
*   **ADMIN**: Puede enviar mensajes globales (Broadcast) y directos a cualquier usuario.
*   **PROFESOR**: Puede contactar a sus alumnos, otros profesores y al Administrador.
*   **ESTUDIANTE**: Comunicación restringida solo a sus profesores y soporte técnico.

---

## 🚀 Próximos Pasos (Fase de Activación)
1.  Creación de la tabla `mensajes`.
2.  Desarrollo de la vista `vistas/mensajeria.php` (Bandeja de entrada).
3.  Implementación del módulo `php/procesar_mensaje.php`.
4.  Activación del monitor de notificaciones en tiempo real.

---
> [!IMPORTANT]
> **Estado Actual**: RESERVA ESTRATÉGICA (Stand-by).
> No se requiere infraestructura de servidor adicional para este despliegue.
