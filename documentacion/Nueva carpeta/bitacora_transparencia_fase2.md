# 🏛️ MANUAL DE ESPECIFICACIONES TÉCNICAS: BITÁCORA DE TRANSPARENCIA ACADÉMICA (FASE 2)
### Proyecto Ares - Sistema Escolar Élite (Vitrina 06 Standard)
**Autor del Concepto:** Ingeniero Ricardo  
**Arquitectura y Seguridad:** Asistente Antigravity (Google DeepMind Team)

---

## 1. INTRODUCCIÓN Y FILOSOFÍA
La **Bitácora de Transparencia Académica** es un sistema de blindaje ético y legal diseñado para erradicar las alteraciones discrecionales de calificaciones en el entorno escolar. A diferencia de las plataformas tradicionales (donde las modificaciones de notas se realizan de manera silenciosa u opaca), Ares implementa el principio de **Inmutabilidad y Justificación Académica**. 

Cualquier cambio sobre una nota previamente registrada se tratará como un "evento forense pedagógico", forzando al docente a proveer justificación y dejando un rastro permanente imposible de alterar, garantizando la paz mental de las familias y la soberanía ética de la institución.

---

## 2. ARQUITECTURA DE DATOS (ESQUEMA SQL)
Para garantizar la inmutabilidad de los registros, se introduce la tabla transaccional `ares_auditoria_calificaciones`. Esta tabla operará bajo un modelo puramente acumulativo (*Append-Only*); los registros nunca se actualizan ni se eliminan, solo se insertan.

```sql
-- 🛡️ TABLA DE AUDITORÍA PEDAGÓGICA INMUTABLE
CREATE TABLE ares_auditoria_calificaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actividad_id INTEGER NOT NULL,
    estudiante_id INTEGER NOT NULL,
    docente_id INTEGER NOT NULL,
    nota_anterior DECIMAL(3, 1) NOT NULL,
    nota_nueva DECIMAL(3, 1) NOT NULL,
    motivo_codigo VARCHAR(50) NOT NULL, -- Código de motivo estandarizado
    justificacion TEXT NOT NULL,         -- Argumentación pedagógica del docente
    ip_origen VARCHAR(45) NOT NULL,      -- Trazabilidad de red (IPv4/IPv6)
    agente_usuario TEXT NOT NULL,       -- Navegador/Dispositivo del docente
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actividad_id) REFERENCES ares_actividades(id),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
    FOREIGN KEY (docente_id) REFERENCES usuarios(id)
);

-- Índices de consulta rápida para auditorías institucionales
CREATE INDEX idx_auditoria_estudiante ON ares_auditoria_calificaciones(estudiante_id);
CREATE INDEX idx_auditoria_actividad ON ares_auditoria_calificaciones(actividad_id);
```

---

## 3. PROTOCOLO DEL MOTIVO Y JUSTIFICACIÓN
Para evitar la justificación arbitraria, el sistema estandariza las causas permitidas mediante la columna `motivo_codigo`. 

### Catálogo de Códigos Autorizados:
| Código | Causa Estandarizada | Tipo de Evidencia Exigida |
| :--- | :--- | :--- |
| `ERR_DIGIT` | **Error de digitación o cálculo.** | Ninguna adicional (corrección rápida). |
| `REC_SPEC` | **Actividad de recuperación especial.** | Sustentación física o digital cargada. |
| `ESF_ADD` | **Esfuerzo adicional / Desempeño.** | Bitácora de comportamiento u observación diaria. |
| `REV_RUB` | **Ajuste global por revisión de rúbrica.** | Modificación general de la actividad. |
| `SOP_MED` | **Exclusiva por incapacidad médica.** | Certificado de enfermería o coordinación. |

---

## 4. FLUJO DE EXPERIENCIA DE USUARIO (UX - VITRINA 06)
Cuando el motor *Ares* detecte que el valor de un slider o una nota directa ha sido modificado con respecto a `nota_original` y se presione el botón **"Registrar Nota"**, se suspenderá la petición y se desplegará el panel de transparencia:

### Paso 1: Interrupción del Guardado (Ares Guard)
El modal del *Focus Mode* cambiará temporalmente a un estado de **"Validación de Transparencia"** con un fondo ámbar translúcido y la advertencia:
> ⚠️ **ATENCIÓN DOCENTE:** Está a punto de modificar una calificación existente. Por políticas de la institución y ley de transparencia, este cambio será auditado.

### Paso 2: Formulario de Justificación (Vitrina 06)
Se renderizará una fila interactiva (altura 44px, radio de borde 12px):
1. **Selector de Motivo**: Lista desplegable con los motivos autorizados.
2. **Caja de Texto**: Un área de justificación (`textarea`) de al menos 100 caracteres exigidos por validación frontend.
3. **Botón Confirmar Edición**: Con el texto **"AUTORIZAR Y APLICAR CAMBIO"**.

```
+--------------------------------------------------------------+
|                   VALIDACIÓN DE TRANSPARENCIA                |
+--------------------------------------------------------------+
|  Estudiante: Luciana Gómez                                   |
|  Calificación: 3.5  ===>  4.5                                 |
|                                                              |
|  [ Motivo del Cambio  ▼ ]                                    |
|  * Seleccione: Exposición de recuperación especial           |
|                                                              |
|  [ Justificación Pedagógica ]                                |
|  "El estudiante realizó la sustentación presencial de la     |
|   rúbrica en horario de nivelación..."                       |
|                                                              |
|                  [ APLICAR Y FIRMAR CAMBIO ]                 |
+--------------------------------------------------------------+
```

---

## 5. PANEL DE AUDITORÍA RECTORAL (MONITOR TRANSPARENCIA)
Se define una vista exclusiva para Directores de Curso, Coordinadores y Rectores llamada **"Sábana de Transparencia Académica"**. 

### Funcionalidades del Monitor:
- **Alertas de Picos**: Filtro automático que resalta modificaciones donde la diferencia de notas sea mayor a 1.5 puntos.
- **Rastreo de Reincidentes**: Panel que agrupa los cambios por docente para detectar si un profesor en particular realiza un volumen inusual de ediciones en comparación con el promedio.
- **Exportación Forense**: Botón para generar un reporte PDF firmado digitalmente con el historial del estudiante ante reclamos ministeriales.

---

## 6. CERTIFICACIÓN DE TRANSPARENCIA
Con la implementación de este manual, el sistema **Ares** deja de ser un gestor escolar tradicional para convertirse en un **mecanismo de garantía institucional**, blindando a los docentes contra acusaciones de sobornos o favoritismos, y blindando al colegio contra demandas y disputas de calificaciones.
