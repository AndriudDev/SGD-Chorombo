# SGD-Chorombo

**Sistema de Gestión Documental Escolar.** Plataforma web interna para centralizar, digitalizar y asegurar la trazabilidad de la documentación oficial, administrativa y pedagógica del equipo directivo de la Escuela Básica G733 Chorombo Bajo (María Pinto, Chile). Proyecto de la asignatura Práctica (7mo Trimestre, Instituto Profesional San Sebastián).

    Framework: Laravel 13 (PHP 8.5)
    Base de datos: MySQL 8.4
    Frontend: Blade + Bootstrap 5 (CDN)
    Entorno: Docker (Laravel Sail) — PHP 8.5 + MySQL 8.4 + Mailpit
    Arquitectura: Monolito Modular (MVC con Capa de Servicios)

## Control de Acceso (RBAC)

El sistema gestiona 5 perfiles con permisos estrictos mediante middlewares y policies de Laravel:

| Perfil | Permisos |
|---|---|
| **Directora** | Acceso transversal total: lectura, descarga, aprobación y supervisión |
| **Administración** | Gestión técnica: respaldos, configuración, bitácora y asignación de usuarios |
| **Secretaría** | Carga, edición, foliación y emisión de correspondencia oficial (memos, oficios) y citaciones |
| **Jefatura UTP** | Acceso a documentos técnico-pedagógicos, reuniones comunales y directrices curriculares |
| **Convivencia Escolar** | Carga y lectura restringida de actas de acuerdos de apoderados y casos sensibles |

## Estado Actual

### Fase 1 — Base del Sistema (Seguridad y Control de Acceso) ✅

La Fase 1 está implementada, migrada y verificada:

- **HU-01 (Gestión de Perfiles y Usuarios):** Panel exclusivo del Administrador para crear, editar y asignar usuarios a los 5 roles obligatorios mediante Middleware `CheckRole` en Laravel.
- **Autenticación:** Login con Bootstrap 5, sesiones, protección CSRF. La creación de usuarios es exclusiva del rol Administración (`/admin/users`), que asigna el rol del nuevo usuario; no hay registro público.
- **RBAC funcional:** Middleware `role:administracion` bloquea acceso a usuarios sin el rol adecuado (verificado: usuario Secretaria recibe 403 en `/admin/users`).

**Funciona hoy:**
- Login de usuarios (cuentas creadas solo por Administración, sin registro público)
- Panel de administración de usuarios con CRUD completo
- Asignación de roles mediante checkboxes
- Middleware `CheckRole` protege todas las rutas administrativas
- Seed con 5 roles + usuario admin por defecto
- Contenedores Docker listos para desarrollo (`docker compose up -d`)

### Próximas fases

| Fase | Objetivo |
|---|---|
| **Fase 2** | Núcleo de Archivos y Correspondencia — Memos, Oficios, Permisos Administrativos |
| **Fase 3** | Módulos Especializados — Citaciones, Actas Confidenciales, Reuniones Comunales |
| **Fase 4** | Trazabilidad, Búsqueda Avanzada y Auditoría |

El detalle funcional completo está en [`brief.md`](brief.md).

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
│   │   └── Auditoria/         <-- Buscador Avanzado y Bitácora
│   ├── Requests/              <-- Validaciones independientes
│   └── Middleware/            <-- Control de acceso basado en roles
├── Models/                    <-- Modelos Eloquent
└── Services/                  <-- Lógica de negocio y gestión de ficheros
database/
├── migrations/                <-- Esquemas relacionales MySQL
└── seeders/                   <-- Datos iniciales (roles + admin)
```

Las decisiones arquitectónicas se explican en [`ARCHITECTURE.md`](ARCHITECTURE.md). Los principios de UI/UX, convenciones de código (PSR-12, naming) y manejo de archivos se detallan en [`style.md`](style.md).

## Stack

| Capa | Tecnología |
|---|---|
| Framework | Laravel 13 (PHP 8.5) |
| Base de datos | MySQL 8.4 |
| Frontend | Blade + Bootstrap 5 (CDN) |
| Contenedores | Docker Compose (Sail) |
| Correo (dev) | Mailpit |
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

# 4. Sembrar datos iniciales (roles + admin)
docker compose exec -T laravel.test php artisan db:seed --force

# 5. Abrir en el navegador
start http://localhost
```

### Usuarios por defecto

El seeder crea un usuario de prueba por cada rol (contraseña: `password` para todos):

| Rol | Email |
|---|---|
| Directora | `directora@sgd-chorombo.cl` |
| Administración | `admin@sgd-chorombo.cl` |
| Secretaría | `secretaria@sgd-chorombo.cl` |
| UTP | `utp@sgd-chorombo.cl` |
| Convivencia | `convivencia@sgd-chorombo.cl` |

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

# Test de tiempos
docker compose logs -f laravel.test 

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
| GET | `/login` | Público (guest) |
| POST | `/login` | Público (guest) |
| POST | `/logout` | Autenticado |
| GET | `/admin` | Admin |
| GET | `/admin/users` | Admin |
| GET | `/admin/users/create` | Admin |
| POST | `/admin/users` | Admin |
| GET | `/admin/users/{id}/edit` | Admin |
| PUT | `/admin/users/{id}` | Admin |
| DELETE | `/admin/users/{id}` | Admin |

## Desarrollo

### Estructura de migraciones

Las migraciones siguen el formato `YYYY_MM_DD_HHMMSS_descripcion.php`.

Las tablas siguen convención snake_case en plural:

- `users` — Usuarios del sistema
- `roles` — Roles/Perfiles
- `role_user` — Pivote usuarios ↔ roles
- `permissions` — (futuro) Permisos específicos

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