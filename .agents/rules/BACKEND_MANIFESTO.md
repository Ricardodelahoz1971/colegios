# 🛡️ BACKEND_MANIFESTO.md: ARQUITECTURA Y SEGURIDAD NÚCLEO

Reglas de servidor para la integridad de datos y procesos.

## 1. 🐘 PHP Y ESTÁNDARES SOBERANOS
- **Estándares PSR**: Cumplimiento mandatorio de **PSR-12** para consistencia y legibilidad.
- **Tipado Estricto**: Declaración obligatoria de `declare(strict_types=1);` en cada archivo.
- **Acceso a Datos (PDO Supremacy)**: Uso exclusivo de `prepare()` / `execute()`. Prohibido concatenar variables o usar `query()` con datos dinámicos.
- **Lógica Temporal**: Uso obligatorio de la clase `DateTime` para manipulación de tiempos.

## 2. 🧱 ARQUITECTURA E INTEGRIDAD (AI-READY)
- **Higiene de Respuesta**: Endpoints deben devolver JSON estructurado (`status`, `data`, `message`).
- **Metadatos Semánticos**: Documentación de esquemas SQL vía `COMMENT` para facilitar el entendimiento por parte de la IA (RAG).
- **Manejo de Errores**: Supresión de errores técnicos descriptivos en UI; solo logs genéricos. Uso de `try/catch` globales.
- **Higiene de Sesin**: Invocación de `session_write_close()` en procesos asíncronos para evitar bloqueos SPA.

## 3. 🔐 SEGURIDAD SUPREMA (BLINDAJE OWASP)
- **Protección CSRF**: Uso obligatorio de tokens de integridad en cada petición de escritura (`POST/PUT/DELETE`).
- **Blindaje de Cookies**: Configuración de `HttpOnly`, `Secure` y `SameSite` para todas las sesiones.
- **Autenticación**: Validación de permisos por módulo en cada endpoint. Contraseñas siempre con `password_hash`.
- **Higiene de Entrada/Salida**: Saneamiento de toda variable renderizada (XSS) y filtrado de superglobales.

---
**CERTIFICACIÓN**: Documento integrado con Skills de Seguridad e Integridad de Datos. Pipod.
