# 🧪 Lista de Chequeo de Pruebas: Diseñador de Plantillas Premium de Matrícula

Este checklist tiene como objetivo verificar la funcionalidad del sistema de plantillas personalizables e interactivas para el expediente de matrícula de los alumnos.

## 📌 Módulo A: Diseñador de Plantillas Premium (Frontend)
- [x] **Paso A.1:** Entrar a *Diseñador de Plantillas* ([formatos_matricula.php](file:///c:/xampp/htdocs/sistema_escolar/php/vistas/formatos_matricula.php)).
- [x] **Paso A.2:** Validar que los botones para insertar bloques avanzados (Cabeceras, Fichas, Tablas de Notas) se expongan en la barra lateral.
- [x] **Paso A.3:** Probar la inyección de bloques en el lienzo Quill de forma atómica (`contenteditable="false"`).

## 📌 Módulo B: Configuración Interactiva de Bloques
- [x] **Paso B.1:** Hacer clic en un bloque de notas insertado y validar la apertura del modal de configuración.
- [x] **Paso B.2:** Seleccionar columnas (ej: Materia, Nota Final) y aplicar filtros (ej: "Solo materias perdidas"), verificando que se actualicen las propiedades `data-*` del bloque en el HTML del editor.

## 📌 Módulo C: Persistencia y API (Ajax)
- [x] **Paso C.1:** Guardar la plantilla con bloques avanzados y comprobar en [formatos_ajax.php](file:///c:/xampp/htdocs/sistema_escolar/php/logica/formatos_ajax.php) que se guarde el marcado HTML con las opciones.

## 📌 Módulo D: Impresión y Renderizado Dinámico (Backend)
- [x] **Paso D.1:** Ir a imprimir la matrícula de un estudiante ([imprimir_matricula.php](file:///c:/xampp/htdocs/sistema_escolar/imprimir_matricula.php)).
- [x] **Paso D.2:** Validar que el servidor procese los bloques, consulte la base de datos de calificaciones del estudiante, y renderice la tabla dinámica filtrando según lo configurado en la plantilla.
