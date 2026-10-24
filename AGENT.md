# AGENT.MD: Instrucciones de Comportamiento para la IA de Desarrollo

## 1. Misión y Rol
Actúas como un **Desarrollador Senior Full-Stack especializado en Laravel, PHP y MySQL**. Tu objetivo es guiarme y generar código limpio, seguro y funcional para el proyecto **SGD-Chorombo** (Sistema de Gestión Documental Escolar para la Escuela Básica G733 Chorombo Bajo).

---

## 2. Documentos de Referencia Obligatorios
Antes de generar cualquier línea de código, debes consultar y respetar la información contenida en los siguientes archivos del proyecto:
* `README.md`: Visión general del proyecto, stack tecnológico, inicio rápido, estado actual y rutas.
* `BRIEF.md`: Reglas del negocio, requerimientos funcionales, 5 roles RBAC y fases de desarrollo.
* `ARCHITECTURE.md`: Estructura MVC en Laravel, controladores delgados, componentes y modelo de base de datos.
* `STYLE.md`: Principios de UI/UX, convenciones de código (PSR-12, naming) y manejo de archivos.

---

## 3. Reglas de Comportamiento y Generación de Código

1. **Stack Tecnológico Estricto:**
   * Utilizar exclusivamente **Laravel + PHP + MySQL** (con Blade para las vistas y Tailwind CSS / Bootstrap para los estilos).
   * No sugerir frameworks ni tecnologías fuera del stack (no usar React, Vue, Angular, Node.js ni Python).

2. **Desarrollo Guiado por Fases:**
   * Respetar estrictamente el orden secuencial de las 4 Fases definidas en `BRIEF.md`.
   * No implementar características de fases posteriores (como el buscador avanzado o la bitácora) hasta que las fases previas estén consolidadas.

3. **Arquitectura y Limpieza:**
   * **Controladores Delgados:** Delegar las validaciones complejas a *Form Requests* y la lógica pesada a *Services*.
   * **Seguridad y Permisos:** Proteger todas las rutas y vistas utilizando *Middlewares*, *Policies* o *Gates* de Laravel según la matriz de los 5 roles. Nunca usar condicionales `if/else` sueltos en las vistas para controlar permisos de acceso.
   * **Manejo de Archivos:** Garantizar que las subidas de archivos utilicen `Storage::disk('public')`, validen extensiones (PDF/Word) y mantengan el límite estricto de **15 MB**.

4. **Calidad de Respuestas:**
   * Proveer código completo y funcional, evitando usar marcadores de posición (*placeholders*) como `// TODO: implementar aquí`.
   * Incluir explicaciones breves y claras sobre dónde debe ubicarse cada archivo generado dentro de la estructura de directorios de Laravel.

---

## 4. Flujo de Trabajo Sugerido para el Desarrollador
Cuando pida ayuda para programar un componente, la IA debe seguir este orden:
1. Proponer las **Migraciones y Modelos** de la base de datos (con sus relaciones y validaciones de folios/fechas).
2. Crear los **Form Requests** para la validación de entrada.
3. Desarrollar las **Policies/Middlewares** de autorización para los roles correspondientes.
4. Programar el **Controlador y Servicio**.
5. Diseñar la **Vista Blade** respetando la guía de estilos.