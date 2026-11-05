# SGD-Chorombo

**Sistema de Gestión Documental Escolar.** Plataforma web interna para centralizar, digitalizar y asegurar la trazabilidad de la documentación oficial, administrativa y pedagógica del equipo directivo de la Escuela Básica G733 Chorombo Bajo (María Pinto, Chile). Proyecto de la asignatura Práctica (7mo Trimestre, Instituto Profesional San Sebastián).

    Framework: Laravel 13 (PHP 8.5)
    Base de datos: MySQL 8.4
    Frontend: Blade + Bootstrap 5 (CDN)
    Entorno: Docker (Laravel Sail) — PHP 8.5 + MySQL 8.4 + Mailpit
    Arquitectura: Monolito Modular (MVC con Capa de Servicios)

## Control de Acceso (RBAC)

El sistema gestiona 6 perfiles con permisos estrictos mediante middlewares y policies de Laravel:

| Perfil | Permisos |
|---|---|
| **Dirección** | Acceso transversal total: lectura, descarga, aprobación, supervisión y administración de usuarios |
| **Inspectoría General** | Creación y administración de cuentas; acceso a otros módulos según matriz de permisos pendiente |
| **Administración** | Gestión técnica: respaldos, configuración, bitácora y asignación de usuarios |
| **Secretaría de Dirección** | Carga, edición, foliación y emisión de correspondencia oficial (memos, oficios) y citaciones |
| **Coordinación Académica / Jefatura Técnica** | Acceso a documentos técnico-pedagógicos, reuniones comunales y directrices curriculares |
| **Convivencia Escolar** | Carga y lectura restringida de actas de acuerdos de apoderados y casos sensibles |

Slugs internos (usados en rutas y middleware): `directora`, `inspectoria`, `administracion`, `secretaria`, `utp`, `convivencia`. En la interfaz siempre se muestra el nombre del cargo.

### Matriz resumen: crear vs. visualizar

| Módulo | Quién crea y quién ve |
|---|---|
| **Memos y oficios** | **Crean y ven:** todos los usuarios autenticados (sin restricción de rol). **Editan/eliminan:** solo el autor o Administración. |
| **Citaciones** | **Crean:** Convivencia, Secretaría de Dirección y Dirección. **Ven:** Administración, Dirección, Secretaría de Dirección, UTP y Convivencia. Inspectoría: sin acceso (403). |
| **Actas confidenciales** | **Crean:** Convivencia y Dirección. **Ven:** Convivencia, Dirección y UTP (UTP solo ámbito pedagógico). Secretaría de Dirección, Administración e Inspectoría: sin acceso (403). |
| **Reuniones comunales** | **Crean:** UTP y Dirección. **Ven:** Administración, Dirección, Secretaría de Dirección, UTP y Convivencia. Inspectoría: sin acceso (403). |
| **Permisos administrativos** | **Crean:** los 6 roles — Dirección, Administración e Inspectoría para cualquier funcionario; el resto solo su propia solicitud. **Ven:** Dirección, Administración e Inspectoría ven las de todos; el resto solo las propias. **Aprueban/rechazan/editan/eliminan:** Dirección, Administración e Inspectoría. |
| **Usuarios** | **Crean/editan/eliminan:** Administración, Dirección e Inspectoría General. El resto: sin acceso (403). |

## Estado Actual

### Fase 1 — Base del Sistema (Seguridad y Control de Acceso) ✅

- **HU-01 (Gestión de Perfiles y Usuarios):** Panel de usuarios habilitado para Administración, Dirección e Inspectoría General para crear, editar y asignar cuentas a uno de los 6 roles mediante Middleware `CheckRole` en Laravel.
- **Autenticación:** Login con Bootstrap 5, sesiones, protección CSRF. La creación de usuarios es exclusiva de Administración, Dirección e Inspectoría General (`/admin/users`), que asigna un único rol al nuevo usuario; no hay registro público.
- **RBAC funcional:** Middleware `role:administracion|directora|inspectoria` bloquea acceso a usuarios sin el rol adecuado (verificado: usuario Convivencia recibe 403 en `/admin/users`).

### Fase 2 — Núcleo de Archivos y Correspondencia ✅

Implementada, migrada y con evidencias de QA en [`tests/Evidence/`](tests/Evidence/):

- **HU-02 (Memos y Oficios):** Registro con folio asignado automáticamente por el sistema (serie + año, p. ej. `MEMO-2026-0001`), Tipo (`memo`/`oficio`), Fecha, Destinatario y Asunto. Adjunto opcional PDF/Word o imagen (JPG/JPEG/PNG/WEBP) de hasta 15 MB en `storage/app/public` (disco `public`), con descarga por ruta dedicada. Unicidad de folio por serie (tipo) y año validada en backend (`CorrespondenciaService::validarFolioUnico`); el folio se regenera si cambia el tipo o el año de la fecha al editar. Solo el autor o el rol Administración pueden editar/eliminar un memo.
- **HU-03 (Permisos Administrativos):** Solicitud con funcionario, fechas, jornada completa o media, motivo y respaldo documental opcional (PDF/Word/imagen, hasta 15 MB, descarga por ruta dedicada). Estados `pendiente` / `aprobado` / `rechazado` con flujo de aprobación (`/aprobar`, `/rechazar`) restringido a Dirección, Administración e Inspectoría. Secretaría, UTP y Convivencia solo ven sus propias solicitudes; Dirección, Administración e Inspectoría ven las de todo el equipo con días usados y disponibles por funcionario (cupo anual provisional de 10 días, `PermisoAdministrativo::CUPO_ANUAL`). Badge de solicitudes pendientes solo para perfiles autorizados; las solicitudes pendientes o rechazadas no descuentan días.

**Funciona hoy:**
- Login de usuarios (cuentas creadas solo por Administración, Dirección e Inspectoría General, sin registro público)
- Panel de administración de usuarios con CRUD completo y asignación de un único rol mediante botones de opción
- CRUD de memos/oficios con filtros por tipo y búsqueda, folio automático por serie+año y descarga del adjunto (PDF/Word/imagen)
- Solicitudes de permisos con estados, respaldo adjunto descargable, aprobación/rechazo y contador de días usados y disponibles
- Citaciones a apoderados con seguimiento de estados, filtros y adjunto (HU-04)
- Actas confidenciales con acceso restringido por rol y ámbito (HU-05)
- Repositorio de reuniones comunales con compromisos y fechas límite (HU-06)
- Sidebar y tarjetas del dashboard adaptados a los permisos de cada rol
- Middleware `CheckRole` protege todas las rutas administrativas y de gestión de permisos
- Seed con 6 roles (incluye Inspectoría General) + usuario de prueba por rol
- Contenedores Docker listos para desarrollo (`docker compose up -d`)

### Fase 3 — Módulos Especializados (Convivencia y Redes) ✅

Implementada, migrada y con evidencia de QA en [`tests/Evidence/`](tests/Evidence):

- **HU-04 (Citaciones a Apoderados):** Registro con RUN/Nombre del estudiante, apoderado, funcionario que cita, fecha/hora y motivo (conductual, pedagógico, administrativo). Adjunto opcional —copia firmada o colilla— de hasta 15 MB en `storage/app/public/citaciones`. Estados de seguimiento `programada` / `asistio` / `no_asistio` / `reprogramada` gestionados con `PATCH /citaciones/{id}/estado`. Emisión (crear, editar, eliminar y actualizar estado) solo para Convivencia, Secretaría de Dirección y Dirección; consulta y descarga para Administración, Dirección, Secretaría de Dirección, UTP y Convivencia (Inspectoría: sin acceso), con filtros por estado, motivo y búsqueda por estudiante/RUN/apoderado.
- **HU-05 (Actas Confidenciales):** Actas de acuerdos con apoderados categorizadas `confidencial` / `sensible`, con ámbito (conductual, pedagógico, administrativo), archivo firmado obligatorio y búsqueda por nombre o RUN del estudiante. Acceso estricto: Convivencia y Dirección gestionan; UTP solo consulta y descarga actas de ámbito pedagógico; Secretaría de Dirección, Administración e Inspectoría reciben 403.
- **HU-06 (Reuniones Comunales):** Repositorio categorizado (Red de UTP, Reunión de Directores, Mesa Comunal de Convivencia, DAEM/SLEP) con minuta u orientación adjunta y compromisos registrados con fecha límite de entrega institucional. La carga de reuniones y compromisos es de UTP y Dirección; Administración, Dirección, Secretaría de Dirección, UTP y Convivencia consultan y descargan (Inspectoría: sin acceso).

### Próximas fases

| Fase | Objetivo |
|---|---|
| **Fase 4** | Búsqueda avanzada, papelera y auditoría (HU-07 y HU-08) |

### Puntos por confirmar

- **Edición/eliminación de memos (HU-02):** los requerimientos no definen quién edita memos; especifican solo "según autorización" y dejan el acceso concreto de cada módulo **pendiente de confirmación** (HU-01). La regla actual —*solo el autor o Administración pueden editar/eliminar*— es una decisión de implementación, no un requisito validado.
- **Discrepancia con HU-08:** esta historia exige *"restringir la eliminación de documentos a Dirección e Inspectoría"*, pero hoy el autor **sí puede eliminar** su propio memo/oficio. Corresponde a Fase 4 (papelera y auditoría); deberá resolverse al implementarla.

Las historias de usuario actualizadas están en [`requerimientos.md`](requerimientos.md); el brief técnico original en [`brief.md`](brief.md).

## Arquitectura

Monolito Modular con separación estricta por módulos de negocio:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              <-- Autenticación y Perfiles (RBAC)
│   │   ├── Correspondencia/   <-- Memos y Oficios
│   │   ├── RRHH/              <-- Permisos Administrativos
│   │   ├── Convivencia/       <-- Citaciones y Actas Sensibles
│   │   ├── UTP/               <-- Reuniones Comunales y Redes
│   │   └── Auditoria/         <-- Fase 4 (planificado): Buscador Avanzado y Bitácora
│   ├── Requests/              <-- Validaciones independientes
│   └── Middleware/            <-- Control de acceso basado en roles
├── Models/                    <-- Modelos Eloquent
└── Services/                  <-- Lógica de negocio y gestión de ficheros
database/
├── migrations/                <-- Esquemas relacionales MySQL
└── seeders/                   <-- Datos iniciales (6 roles + usuarios demo)
```

Las decisiones arquitectónicas se explican en [`ARCHITECTURE.md`](ARCHITECTURE.md). Los principios de UI/UX, convenciones de código (PSR-12, naming) y manejo de archivos se detallan en [`style.md`](style.md).

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Laravel 13 (PHP 8.5) |
| Base de datos | MySQL 8.4 |
| Frontend | Blade + Bootstrap 5 (CDN) |
| Contenedores | Docker Compose (Sail) |
| Assets | Vite |

## Requisitos previos

- **Docker Desktop** (Windows/macOS/Linux)
- **Git**

(Opcional para desarrollo local sin Docker: PHP 8.5, Composer, MySQL 8.4, Node.js 24+)

## Inicio rápido

```bash
# 1. Clonar el repositorio
git clone <repo-url>
cd SGD-Chorombo

# 2. Iniciar contenedores (primera vez: construye la imagen, ~5 min)
docker compose up -d

# 3. Ejecutar migraciones
docker compose exec -T laravel.test php artisan migrate --force

# 4. Sembrar datos iniciales (6 roles + usuarios de prueba)
docker compose exec -T laravel.test php artisan db:seed --force

# 5. Abrir en el navegador
start http://localhost
```

### Usuarios por defecto

El seeder crea un usuario de prueba por cada rol (contraseña: `password` para todos):

| Rol | Email |
|---|---|
| Dirección | `directora@sgd-chorombo.cl` |
| Inspectoría General | `inspectoria@sgd-chorombo.cl` |
| Administración | `admin@sgd-chorombo.cl` |
| Secretaría de Dirección | `secretaria@sgd-chorombo.cl` |
| Coordinación Académica (UTP) | `utp@sgd-chorombo.cl` |
| Convivencia Escolar | `convivencia@sgd-chorombo.cl` |

El seeder es idempotente: puedes ejecutar `db:seed` varias veces sin duplicar datos.

## Comandos útiles

### Contenedores

```bash
docker compose up -d          # Iniciar
docker compose down           # Detener
docker compose logs -f        # Ver logs en vivo
docker compose ps             # Estado de servicios
docker compose restart        # Reiniciar
docker compose down && docker compose up -d # Reiniciar
```

### Laravel (dentro del contenedor)

```bash
# Ejecutar un comando artisan
docker compose exec -T laravel.test php artisan <comando>

# Migraciones
docker compose exec -T laravel.test php artisan migrate --force
docker compose exec -T laravel.test php artisan db:seed --force

# Ver rutas disponibles
docker compose exec -T laravel.test php artisan routes:list

# Consola interactiva (Tinker)
docker compose exec laravel.test php artisan tinker
```

### Acceso a MySQL

```bash
docker compose exec -T mysql mysql -usail -ppassword sgd_chorombo -e "SELECT * FROM users;"
```

### Mailpit (correos en desarrollo)

- Dashboard: http://localhost:8025
- SMTP: localhost:1025

## Rutas

| Método | Ruta | Acceso |
|---|---|---|
| GET/POST | `/login` | Público (guest) |
| POST | `/logout` | Autenticado |
| GET | `/dashboard` | Autenticado |
| GET/POST/PUT/DELETE | `/admin/users[/{id}]` | `role:administracion\|directora\|inspectoria` |
| GET | `/correspondencia/memos` · `/memos/{memo}` · `/memos/{memo}/download` | Autenticado |
| GET | `/correspondencia/memos/create` · `/memos/{memo}/edit` | Autenticado (edición solo autor o Administración) |
| POST/PUT/DELETE | `/correspondencia/memos[/{memo}]` | Autenticado (modificación solo autor o Administración) |
| GET/POST | `/permisos-administrativos[/create]` | `administracion\|directora\|secretaria\|utp\|convivencia` |
| GET/PUT/DELETE/POST | `/permisos-administrativos/{id}/edit` · `/{id}` · `/aprobar` · `/rechazar` | `role:administracion\|directora` |
| GET | `/citaciones` · `/{id}` · `/{id}/download` | `role:administracion\|directora\|secretaria\|utp\|convivencia` (lectura) |
| GET/POST/PUT/DELETE/PATCH | `/citaciones/create` · `/{id}/edit` · `/{id}` · `/{id}/estado` | `role:convivencia\|secretaria\|directora` (emisión) |
| GET | `/actas-confidenciales` · `/{id}/download` | `role:convivencia\|directora\|utp` (UTP: solo ámbito pedagógico) |
| GET/POST/PUT/DELETE | `/actas-confidenciales/create` · `/{id}/edit` · `/{id}` | `role:convivencia\|directora` |
| GET | `/reuniones-comunales` · `/{id}` · `/{id}/download` | `role:administracion\|directora\|secretaria\|utp\|convivencia` (lectura) |
| GET/POST/PUT/DELETE | `/reuniones-comunales/create` · `/{id}` · `/{id}/compromisos[...]` | `role:utp\|directora` (gestión) |

## Desarrollo

### Estructura de migraciones

Las migraciones siguen el formato `YYYY_MM_DD_HHMMSS_descripcion.php`.

Las tablas siguen convención snake_case en plural:

- `users` — Usuarios del sistema
- `roles` — Roles/Perfiles
- `role_user` — Pivote usuarios ↔ roles
- `memos` — Memos y oficios con folio, tipo, fechas y adjunto
- `permisos_administrativos` — Solicitudes de permiso con estado, aprobador y respaldo adjunto
- `citaciones` — Citaciones a apoderados con motivo, estado de seguimiento y adjunto
- `actas_confidenciales` — Actas de acuerdos con apoderados (confidencial/sensible)
- `reuniones_comunales` — Reuniones comunales categorizadas con documento adjunto
- `compromisos_reuniones` — Compromisos de cada reunión con fecha límite

### Pruebas

El proyecto usa PHPUnit 12 (`php artisan test`). Suites actuales:

- `RoleAccessTest` (7 casos) / `RegistrationDisabledTest` (3 casos) — RBAC y ausencia de registro público (Fase 1)
- `RoleSeederTest` (2 casos) — catálogo de 6 cargos y cuenta demo de Inspectoría (Fase 1)
- `Fase1CorreccionesQaTest` (6 casos) — un solo rol por usuario y flujos de login (Fase 1)
- `Fase2QaTest` (22 casos) / `Fase2RevalidacionQaTest` (6 casos) — permisos, memos y reglas de propiedad (Fase 2)
- `Fase3QaTest` (10 casos) — citaciones, actas confidenciales y reuniones comunales (Fase 3)

La suite completa (`php artisan test`) suma 57 tests / 258 aserciones, todos verdes. Las corridas y hashes de las revisiones de QA quedan registrados en [`tests/Evidence/`](tests/Evidence/).

### Convenciones de código

- **Controladores:** PascalCase singular + `Controller` (ej: `MemoController`)
- **Modelos:** PascalCase singular (ej: `Memo`, `User`, `Citacion`)
- **Vistas (Blade):** kebab-case agrupadas por módulo
- **Rutas:** kebab-case plural (ej: `/memos`, `/permisos-administrativos`)
- **Form Requests:** Validaciones independientes para cada formulario
- **Middlewares:** Control de acceso basado en roles, nunca condicionales `if/else` en vistas

Incluye principios de UI/UX, convenciones de código (PSR-12, naming) y manejo de archivos. Ver detalles completos en [`style.md`](style.md).

## Licencia

MIT — Ver [LICENSE](LICENSE).