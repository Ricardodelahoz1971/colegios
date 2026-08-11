# 🏛️ PROPUESTAS DE REACTIVOS INTERACTIVOS (ARES CANVAS)
## Módulo de Evaluación Gráfica de Alta Fidelidad - Vitrina 06

Este documento detalla las tipologías de interacción del lienzo vectorial de **ARES Canvas Engine**, diseñadas bajo el principio de **Dualidad Operativa**: rendimiento óptimo en plataformas digitales (diseño adaptativo a móvil) y compatibilidad de renderizado para exámenes físicos impresos.

---

## 🕹️ 1. Tipologías de Interacción Gráfica

### 🗺️ A. Reactivo de Zona Activa / Hotspot (Ubicación Geográfica / Clic Directo)
* **Digital (Móvil / Web):** El alumno ve una imagen limpia. Al presionar directamente sobre una región de la imagen (coordenadas $X, Y$), el sistema evalúa si el toque ocurrió dentro de la zona invisible (Hotspot) preconfigurada.
* **Físico (Impreso en Papel):** La zona caliente se renderiza visualmente como un borde punteado sutil con una etiqueta de letra (ej: **[ A ]**). La pregunta se transforma a: *"Indique la letra que corresponde a la ubicación de..."* y el alumno marca o escribe la letra correspondiente.

### 🫁 B. Reactivo de Opción Múltiple Incrustada (Diagrama de Anatomía / Procesos)
* **Digital (Móvil / Web):** Botones circulares con las opciones ($A, B, C, D$) flotan sobre zonas específicas de una imagen. El alumno toca la opción directamente en el gráfico.
* **Físico (Impreso en Papel):** Se imprimen los círculos de opción múltiple ($A, B, C, D$) sobre la imagen en la posición exacta configurada. El alumno rellena con lápiz el círculo directamente en la hoja del examen.

### 🔀 C. Reactivo de Unión por Líneas (Matching Vectorial)
* **Digital (Móvil / Web):** El alumno arrastra el dedo o puntero desde un nodo emisor (izquierda) hacia un nodo receptor (derecha), dibujándose una línea vectorial interactiva.
* **Físico (Impreso en Papel):** Los nodos se imprimen en columnas paralelas con círculos de anclaje. El alumno traza la línea a mano alzada con su lápiz conectando ambos puntos.

### 📥 D. Arrastrar y Soltar en Huecos / Dropzone (Completar Conceptos)
* **Digital (Móvil / Web):** El docente define cajas de texto o imágenes arrastrables y casillas receptoras ("huecos") sobre el lienzo. El estudiante arrastra las cajas, las cuales se encajan mediante un efecto de atracción imán (*snap*) al estar cerca del hueco.
* **Físico (Impreso en Papel):** Las casillas receptoras se imprimen como líneas en blanco subrayadas con un número identificador (ej: `____(1)____`). Al final del gráfico, se imprime un banco con las palabras u opciones arrastrables numeradas para que el alumno las escriba a mano.

---

## 📱 2. Lineamientos de Diseño Móvil (Mobile-First)

Para asegurar que los exámenes digitales se puedan responder con precisión en pantallas táctiles de teléfonos inteligentes:
1. **Métrica 44px de Toque**: Todo nodo interactivo (botones de opción, zonas táctiles, cajas arrastrables) debe tener un área de contacto mínima de $44 \times 44$ píxeles para evitar falsos toques con los dedos.
2. **Escalado Responsivo del Lienzo**: El canvas vectorial se escala proporcionalmente manteniendo la relación de aspecto original de las coordenadas. Las cajas de colisión y posiciones de los nodos se recalculan de forma porcentual sobre el ancho de pantalla del móvil.
3. **Bloqueo de Desplazamiento (Scroll Lock)**: Al iniciar un arrastre (Drag) en móvil, se previene temporalmente el scroll vertical nativo de la pantalla para permitir el movimiento preciso del nodo.

---

## 🖨️ 3. Adaptación Automática para Impresión (Print CSS Engine)

El motor de impresión traduce las hojas de estilo del canvas a través de capas CSS dedicadas a impresión (`@media print`):
* **Eliminación de Elementos Interactivos Inútiles**: Se ocultan los botones de limpiar lienzo, previsualizar JSON, herramientas de dibujo, y barras de scroll.
* **Conversión de Contraste**: Las zonas translúcidas de colores se convierten automáticamente a escala de grises y contornos de alta definición (blanco y negro) para garantizar la legibilidad en impresiones láser económicas.
* **Optimización de Fondos**: El motor fuerza la visibilidad de las imágenes de fondo del Canvas usando propiedades CSS de impresión estándar (`-webkit-print-color-adjust: exact`).


### ? E. Reactivo de Secuenciaci�n / Ordenamiento (Timeline Interactivo)
* **Digital (M�vil / Web):** El alumno debe arrastrar bloques interactivos (eventos, pasos, conceptos) y encajarlos en ranuras secuenciales predefinidas [ 1 ], [ 2 ], [ 3 ]... en una l�nea de tiempo o lista ordenada.
* **F�sico (Impreso en Papel):** Los bloques se imprimen con letras, y abajo se presenta una l�nea de tiempo con espacios vac�os para que el estudiante escriba la letra que corresponde a la secuencia correcta.
