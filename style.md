# STYLE.md - Guía de Estilos y Estándares de Desarrollo
**Proyecto:** Sistema de Gestión Documental Escolar (SGD-Chorombo)  
**Stack Tecnológico:** Laravel, PHP, MySQL, Blade, Tailwind CSS / Bootstrap  

---

## 1. Principios de Diseño UI/UX (Interfaz y Experiencia de Usuario)

* **Enfoque Institucional y Sobrio:** La interfaz debe reflejar formalidad administrativa y gubernamental. Se prioriza la legibilidad, la claridad y el acceso rápido a la información por encima de adornos visuales excesivos.
* **Diseño Responsivo (Mobile-First / Desktop-Optimized):** 
  * Aunque el sistema será utilizado mayoritariamente por equipos directivos en computadores de escritorio y notebooks, los formularios y tablas principales deben adaptarse correctamente a tablets y pantallas medianas.
* **Retroalimentación Visual Inmediata:** 
  * Cada acción crítica (como guardar un oficio, aprobar un permiso o eliminar un registro) debe mostrar notificaciones flotantes de éxito o error (*Toasts* / *Alerts*).
  * Los botones de acción deben reflejar estados de carga (`loading`) para evitar envíos duplicados de formularios.

---

## 2. Convenciones de Código y Arquitectura (Laravel)

### 2.1. Naming Conventions (Nomenclatura)
* **Controladores:** PascalCase en singular seguidos del sufijo `Controller` (Ej: `MemoController`, `PermisoAdminController`, `CitacionController`).
* **Modelos:** PascalCase en singular (Ej: `Memo`, `User`, `PermisoAdministrativo`, `Citacion`, `ActaConvivencia`).
* **Tablas de Base de Datos:** snake_case en plural (Ej: `users`, `memos`, `permisos_administrativos`, `citaciones`, `bitacora_auditoria`).
* **Vistas (Blade):** kebab-case o snake_case agrupadas por carpetas según el módulo (Ej: `resources/views/memos/index.blade.php`, `resources/views/convivencia/create.blade.php`).
* **Rutas:** kebab-case en plural para los recursos (Ej: `/memos`, `/permisos-administrativos`, `/citaciones-apoderados`).

### 2.2. Estándares de Código PHP / Laravel
* **Clean Code y PSR-12:** Todo el código PHP debe adherirse estrictamente a los estándares PSR-12.
* **Controladores Delgados (Thin Controllers):** La lógica de negocio compleja, validaciones pesadas o procesamiento de archivos debe delegarse en *Form Requests* y *Services*, manteniendo los controladores limpios.
* **Form Requests:** Utilizar clases de validación independientes (`php artisan make:request`) para manejar las reglas de entrada y mensajes personalizados de cada formulario (especialmente las validaciones de folios únicos y límites de 15 MB en archivos).
* **Políticas y Gates (RBAC):** Nunca validar roles mediante condicionales sueltos (`if/else`) en las vistas o controladores; utilizar obligatoriamente *Laravel Policies* y *Middlewares* para proteger las rutas de cada uno de los 5 roles del sistema.

---

## 3. Estándares para el Manejo de Archivos y Seguridad

* **Almacenamiento Seguro:** Los archivos adjuntos (PDF, Word, actas escaneadas) deben almacenarse mediante el sistema de archivos de Laravel (`Storage::disk('public')` o un disco privado protegido si contienen información sensible).
* **Control de Tipos y Tamaños:** Validar estrictamente en backend que los archivos subidos pertenezcan a extensiones permitidas (`mimes:pdf,doc,docx`) y no superen el límite estricto de **15 MB**.
* **Protección de Datos Sensibles (Confidencialidad):** Los documentos categorizados bajo la Épica 3 (Actas y Compromisos de Convivencia) deben contar con mecanismos de autorización a nivel de base de datos y consultas que impidan su acceso por roles no autorizados (restringido exclusivamente a Convivencia Escolar y Dirección).

---

## 4. Estándares de Componentes de Interfaz (Frontend)

* **Tablas de Datos (DataTables / Listados):**
  * Deben incluir paginación clara, indicadores de estado con etiquetas de colores (badges) y acciones contextuales alineadas a la derecha (Ver, Editar, Descargar, Eliminar).
* **Formularios:**
  * Uso de etiquetas `<label>` descriptivas, campos obligatorios marcados con asterisco (*), y visualización clara de mensajes de error de validación bajo cada campo de entrada.
* **Modales de Confirmación:**
  * Acciones destructivas (como eliminar un documento o enviarlo a la papelera de auditoría) deben requerir obligatoriamente una ventana modal de confirmación por parte del usuario administrador.