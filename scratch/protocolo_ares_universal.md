# 🏛️ PROTOCOLO OPERATIVO: BANCO UNIVERSAL ARES v2.0

Este documento detalla el procedimiento estándar para la gestión de reactivos en el ecosistema unificado.

## 1. ÁMBITOS DE GESTIÓN (SCOPES)
El sistema opera en dos dimensiones lógicas accesibles desde el panel lateral:

*   **MIS REACTIVOS:** Su taller personal. Aquí reside su producción intelectual original y sus adaptaciones. Tiene control total de edición y eliminación.
*   **BANCO UNIVERSAL:** La biblioteca institucional. Permite consultar reactivos de todos los docentes de la institución, filtrados por materia.

## 2. FLUJO DE ADAPTACIÓN ACADÉMICA (FORKING)
Para utilizar un reactivo de otro colega, el protocolo establece los siguientes pasos:

1.  **Localización:** Seleccione el reactivo deseado en el **Banco Universal**.
2.  **Vista Previa:** El sistema cargará el reactivo en modo de **Sola Lectura** para proteger la integridad de la obra original.
3.  **Adaptación:** Presione el botón **"ADAPTAR A MI TALLER"**.
4.  **Clonación:** El sistema generará una copia exacta en su taller personal, estableciendo un vínculo de **Linaje** (`parent_id`) con el original.
5.  **Personalización:** Una vez adaptado, el reactivo se desbloquea en su taller, permitiéndole realizar los ajustes necesarios para su evaluación específica.

## 3. SEGURIDAD Y TRAZABILIDAD
*   **Blindaje CSRF:** Todas las operaciones de escritura están protegidas por tokens de sesión únicos.
*   **Soberanía de Autor:** Un docente nunca podrá modificar ni eliminar un reactivo que no le pertenezca.
*   **Linaje Académico:** La institución mantiene la trazabilidad de qué reactivos originales son los más exitosos basándose en su tasa de adaptación.

---
**INGENIERÍA ÉLITE** - *Soberanía Tecnológica en Educación*
