# ARCHITECTURE.md - Documento de Arquitectura de Software

**Proyecto:** Sistema de Gestión Documental Escolar (SGD-Chorombo)[cite: 1]  
**Institución Beneficiaria:** Escuela Básica G733 Chorombo Bajo (María Pinto, Chile)[cite: 1, 2]  
**Stack Tecnológico:** Laravel (PHP), MySQL, Blade, Bootstrap[cite: 10]  
**Enfoque de Ejecución:** Monolito Modular con despliegue local y empaquetado para transferencia futura.

---

## 1. Visión General de la Arquitectura

El **SGD-Chorombo** se diseña bajo una arquitectura de **Monolito Modular (MVC con Capa de Servicios)**. Este enfoque agrupa el código fuente dentro de un único repositorio de Laravel, pero estructurado rígidamente en módulos funcionales independientes que se corresponden de manera directa con las necesidades operativas del equipo directivo de la escuela[cite: 2, 3].

### Justificación de la Elección Arquitectónica:
* **Simplicidad Operativa y Despliegue Local:** Dado que el sistema se desarrollará y probará en un entorno local y se entregará empaquetado para que la escuela gestione su adaptación[cite: 11, 12], un monolito evita la sobreingeniería de microservicios o arquitecturas desacopladas (SPA), reduciendo los puntos de fallo y facilitando su instalación mediante un simple servidor local o contenedor.
* **Alineación con el Framework:** Laravel provee de forma nativa todas las herramientas necesarias para este modelo (Eloquent ORM, Blade, Middleware de autenticación y sistemas de almacenamiento de archivos), optimizando los tiempos de desarrollo propios de una Clínica Tecnológica.
* **Mantenibilidad:** La separación estricta por módulos de negocio permite que cualquier desarrollador futuro comprenda rápidamente dónde ubicar las reglas de negocio, controladores o migraciones.

---

## 2. Diagrama de Estructuración de Directorios (Monolito Modular)

La estructura interna del proyecto en Laravel se organiza para reflejar los dominios del negocio:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                  <-- Gestión de Autenticación y Perfiles (RBAC)
│   │   ├── Correspondencia/       <-- Memos y Oficios (Épica 2)[cite: 4]
│   │   ├── RRHH/                  <-- Permisos Administrativos (Épica 2)
│   │   ├── Convivencia/           <-- Citaciones y Actas Sensibles (Épica 3)[cite: 6]
│   │   ├── UTP/                   <-- Reuniones Comunales y Redes (Épica 4)
│   │   └── Auditoria/             <-- Buscador Avanzado y Bitácora (Épica 5)
│   ├── Requests/                  <-- Validaciones independientes (folios únicos, pesos < 15MB)
│   └── Middleware/                <-- Control de acceso basado en roles (Directora, Admin, etc.)[cite: 3]
├── Models/                        <-- Modelos Eloquent de Base de Datos
└── Services/                      <-- Lógica de negocio transaccional y gestión de ficheros
database/
├── migrations/                    <-- Esquemas relacionales en MySQL[cite: 10]
└── seeders/                       <-- Datos iniciales (Roles y usuario Administrador por defecto)