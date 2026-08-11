# 📱 ESTRATEGIA DE DESPLIEGUE MÓVIL (ANDROID NATIVO)

Este documento detalla la arquitectura para la creación de la Aplicación Android del sistema, manteniendo la soberanía de datos por colegio y la estética Élite.

---

## 🏗️ 1. ARQUITECTURA MULTI-TENANT (MULTICOLÉGIO)

Para evitar crear 10 apps diferentes, usaremos un modelo de **App Única con Selección de Instancia**:

1.  **Pantalla de Bienvenida**: El usuario ingresa la URL de su colegio (ej: `san-jorge.ngrok.io` o `colegioelite.com`).
2.  **Persistencia de Configuración**: La App guarda esa URL localmente en el teléfono.
3.  **Conexión Dinámica**: Todas las peticiones futuras (Login, Mensajes, Notas) se enviarán a esa URL específica.
4.  **Selección de BD**: El servidor PHP recibe la petición y, basándose en el dominio o un ID de colegio, conecta con el archivo `.db` (SQLite) correspondiente.

---

## 🔗 2. PRUEBAS CON NGROK (ENTORNO DE DESARROLLO)

Para probar la App en un celular real mientras el servidor está en `localhost`:

1.  **Levantar el túnel**: `ngrok http 80` (o el puerto de XAMPP).
2.  **Obtener URL**: Ngrok generará una dirección tipo `https://1234-abcd.ngrok-free.app`.
3.  **Configurar App**: Se ingresa esa URL en la App Android.
4.  **Resultado**: El celular podrá hacer consultas a la base de datos de tu PC desde cualquier red (4G/WiFi).

---

## 🛠️ 3. FASE 1: CONSTRUCCIÓN DE LA API REST (PHP)

La App no entiende de HTML, solo entiende **JSON**. Debemos crear una capa de API:

*   **Endpoint de Autenticación**: `api/login.php` -> Devuelve los datos del usuario y un Token de seguridad.
*   **Endpoint de Mensajería**: `api/mensajes.php` -> Devuelve la lista de chats.
*   **Endpoint de Ares**: `api/examen_android.php` -> Entrega el examen optimizado para vista móvil.

---

## 🎨 4. DISEÑO Y ESTÉTICA (MOBILE ELITE UI)

Usaremos un framework moderno (React Native o Flutter) para asegurar:
*   **Smooth Scrolling**: Navegación fluida a 60fps.
*   **Notificaciones Push**: Avisos instantáneos en la barra de tareas de Android.
*   **Biometría**: Posibilidad de entrar con Huella Digital o Rostro (FaceID).

---

## 📈 5. CRONOGRAMA DE PODER

1.  **Semana 1**: Creación de Endpoints base (Login y Perfil).
2.  **Semana 2**: Conexión de Mensajería y Notificaciones.
3.  **Semana 3**: Implementación de Horarios (Khronos Mobile).
4.  **Semana 4**: Módulo Ares Mobile (Exámenes con bloqueo de pantalla).

---
*Documento estratégico para la expansión móvil del ecosistema escolar.*
