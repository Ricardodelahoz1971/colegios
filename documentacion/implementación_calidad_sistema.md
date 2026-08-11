# Libro Blanco de Calidad: Sistema de Gestión Escolar "Elite"

Este documento certifica los estándares de ingeniería y calidad aplicados en el desarrollo del Sistema de Gestión Escolar. Define el compromiso con la excelencia técnica, la seguridad de la información y la experiencia de usuario.

---

## 1. Visión Tecnológica
El Sistema Escolar "Elite" ha sido diseñado bajo una arquitectura modular de alto rendimiento (PHP + SQLite), optimizada para entornos institucionales que requieren **estabilidad absoluta**, **trazabilidad total** y **cero latencia** en comunicaciones críticas.

## 2. Estándar de Calidad: ISO/IEC 25010 (SQuaRE)
El sistema cumple con los pilares fundamentales del estándar internacional de calidad de software:

### 2.1. Adecuación Funcional
- **Completitud**: Cubre la totalidad de los procesos académicos (Notas, Cargas, Estudiantes, Personal, Roles y Áreas).
- **Corrección**: Cada módulo ha sido validado mediante pruebas de estrés.

### 2.2. Mantenibilidad (Arquitectura Modular)
- **Código Limpio**: Implementación de una arquitectura basada en carpetas `logica/`, `vistas/` y `js/` para permitir actualizaciones sin afectar el núcleo del sistema.
- **Acoplamiento**: Bajo acoplamiento entre el backend y el frontend mediante el uso intensivo de AJAX y JSON.

### 2.3. Experiencia de Usuario (Usabilidad)
- **Interfaz Élite**: Diseño estandarizado con CSS global, eliminando estilos redundantes y garantizando una estética institucional coherente ("Frankenstein-Free UI").
- **Accesibilidad**: Navegación intuitiva con jerarquía visual clara.

---

## 3. Seguridad y Confianza: ISO/IEC 27001 (ISMS)
La protección de los datos de la comunidad educativa es nuestra prioridad técnica.

### 3.1. Control de Acceso (Soberanía de Permisos)
- **Modelo de Permisos**: Sistema híbrido de roles y permisos personalizados ("Modelo de Soberanía"), donde la administración tiene control granular sobre cada función del usuario.

### 3.2. Libro de Auditoría (Traceability)
- **Registro de Memoria**: El sistema cuenta con un motor de auditoría (`logs_auditoria`) que registra:
  - **Quién** realizó la acción.
  - **Qué** acción se ejecutó (CREAR, EDITAR, BORRAR, etc.).
  - **A qué** objetivo afectó.
  - **Detalle de cambios** realizados.

### 3.3. Mensajería Institucional Segura
- **Trazabilidad de Comunicación**: Sistema de mensajería con validación de estado (Enviado, Entregado, Leído) que garantiza la transparencia en las notificaciones internas.

---

## 4. Protección de Datos (Ley 1581 de 2012 - Habeas Data)
El sistema incorpora mecanismos para la protección de datos personales de menores y personal académico:
- **No persistencia innecesaria**: Los datos sensibles solo se almacenan bajo estricto control de seguridad.
- **Acceso Restringido**: Solo el personal autorizado con permisos específicos puede visualizar información protegida.

---

## 5. Conclusión: "ISO-Ready"
La plataforma ha sido desarrollada siguiendo las mejores prácticas de la industria, posicionándose como una solución tecnológica **"ISO-Ready"**, preparada para auditorías de certificación de calidad y cumplimiento institucional.

---

## 6. Hoja de Ruta: Brechas y Próximos Pasos para Certificación Oficial
Para alcanzar la certificación oficial y elevar el sistema a estándares de seguridad gubernamentales, se han proyectado los siguientes hitos:

### 6.1. Resiliencia e Infraestructura
- **Backups Automatizados (Off-site)**: Migrar de copias locales a respaldos cifrados en la nube para garantizar la continuidad del negocio ante fallos de hardware.
- **Disponibilidad (uptime)**: Implementar monitoreo de servidor para garantizar un tiempo de actividad del 99.9%.

### 6.2. Blindaje de Datos (Criptografía)
- **SSL/TLS Estricto**: Implementar certificados de seguridad para cifrar todo el tráfico entre los navegadores y el servidor.
- **Cifrado de Base de Datos**: Añadir una capa de encriptación al archivo de SQLite para protegerlo de accesos directos no autorizados.

### 6.3. Inclusión Social (WCAG 2.1)
- **Accesibilidad Nivel AA**: Ajustar la paleta de colores y el etiquetado de componentes para que el sistema sea 100% compatible con lectores de pantalla para usuarios con discapacidad visual.

### 6.4. Validación por Terceros
- **Auditoría Externa**: El hito final consiste en la contratación de una firma certificadora (ej. ICONTEC, SGS) que realice la validación formal de los pilares ISO 25010 y 27001 descritos en este documento.

---

> [!NOTE]
> **Estado del Documento**: v1.1 - Incluye Hoja de Ruta Estratégica.

**Departamento de Ingeniería de Desarrollo**
*Sistema de Gestión Escolar Élite*
