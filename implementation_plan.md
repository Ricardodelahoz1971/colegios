# Plan de Implementación: Salto de Página y Cabeceras Repetitivas

## 🏛️ Objetivo
Permitir que la cabecera (logo, título, lema y metadatos) se repita al inicio de cada página física impresa en el formato de matrícula, y dar soporte a un bloque de salto de página físico configurable en el diseñador.

## 🛠️ Cambios Propuestos

### 1. [PHP] [imprimir_matricula.php](file:///c:/xampp/htdocs/sistema_escolar/imprimir_matricula.php)
*   **Segmentación de Páginas**: Modificar el renderizado para segmentar el HTML en bloques divididos por el elemento de salto de página (`salto_pagina`).
*   **Repetición de Cabecera**: Inyectar de forma dinámica al inicio de cada bloque segmentado de página los elementos correspondientes a la cabecera (`logo`, `titulo_colegio`, `lema_colegio` y `metadatos`) respetando su posición vertical superior.
*   **Soporte de Bloque**: Agregar soporte en el motor de renderizado PHP para el bloque `salto_pagina`.

### 2. [CSS] [imprimir_matricula.css](file:///c:/xampp/htdocs/sistema_escolar/styles/modules/imprimir_matricula.css)
*   Añadir estilos para la clase `.page-break` asegurando `page-break-before: always;` y `break-before: page;`.
*   Ajustar el comportamiento de impresión para que cada página se comporte como un contenedor independiente.

### 3. [JS] [formatos_matricula_builder.js](file:///c:/xampp/htdocs/sistema_escolar/js/modules/formatos_matricula_builder.js)
*   Añadir soporte en `insertarBloqueEnCanvas` para el tipo de bloque `salto_pagina`.
*   Mostrar una representación gráfica visual en el lienzo para que el usuario sepa dónde ocurrirá el corte.

### 4. [PHP] [formatos_matricula.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/formatos_matricula.php)
*   Añadir la tarjeta interactiva en el panel izquierdo (Catálogo) para arrastrar/insertar el bloque de "Salto de Página".

## 🔬 Plan de Verificación
*   Visualizar plantillas con más de 1 página física en la vista previa.
*   Comprobar que la cabecera se replique arriba de cada nueva página.
*   Validar la salida impresa con PDF de Windows.
