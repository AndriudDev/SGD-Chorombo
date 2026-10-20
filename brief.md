# BRIEF TÉCNICO DEL PROYECTO: Sistema de Gestión Documental Escolar (SGD-Chorombo)

## 1. Contexto y Propósito General
* **Institución Beneficiaria:** Escuela Básica G733 Chorombo Bajo (María Pinto, Chile)[cite: 1, 2].
* **Objetivo:** Desarrollar una plataforma web interna para centralizar, digitalizar y asegurar la trazabilidad de la documentación oficial, administrativa y pedagógica del equipo directivo[cite: 2, 3].
* **Stack Tecnológico Obligatorio:** Laravel + PHP + MySQL[cite: 10].
* **Entorno:** Despliegue en entorno local para entrega y adaptación futura.

---

## 2. Roles y Control de Acceso (RBAC)
El sistema gestiona 5 perfiles diferenciados con permisos estrictos[cite: 3]:
1. **Directora:** Acceso transversal total (lectura, descarga, aprobación y supervisión)[cite: 3, 4].
2. **Administración:** Gestión técnica, respaldos, configuración, bitácora y asignación de usuarios[cite: 3, 4].
3. **Secretaría:** Carga, edición, foliación y emisión de correspondencia oficial (memos, oficios) y citaciones[cite: 3, 4, 5].
4. **Jefatura de UTP:** Acceso a documentos técnico-pedagógicos, reuniones comunales y directrices curriculares[cite: 3, 4, 7].
5. **Convivencia Escolar:** Carga y lectura restringida de actas de acuerdos de apoderados y casos sensibles[cite: 3, 4, 6, 7].

---

## 3. Plan de Desarrollo por Fases e Historias de Usuario (HU)

### Fase 1: Base del Sistema (Seguridad y Control de Acceso)
* **Objetivo:** Configurar autenticación, base de datos de usuarios y permisos RBAC[cite: 3].
* **HU-01 (Gestión de Perfiles y Usuarios):** Panel exclusivo del Administrador para crear, editar y asignar usuarios a los 5 roles obligatorios mediante Middlewares/Policies en Laravel[cite: 3, 4].

### Fase 2: Núcleo de Archivos y Correspondencia (Módulo Operativo Base)
* **Objetivo:** Motor de almacenamiento local (`storage/app/public`) para PDF/Word de hasta 15 MB[cite: 5].
* **HU-02 (Memos y Oficios):** Formulario de registro (Folio, Tipo, Fecha, Destinatario, Asunto)[cite: 4, 5]. Validación en backend para alertar y bloquear folios duplicados en el mismo año escolar[cite: 5].
* **HU-03 (Permisos Administrativos):** Solicitud con fechas, días o medias jornadas[cite: 5]. Manejo de estados (*Pendiente de V°B°, Aprobado, Rechazado*), alertas visuales y contador acumulado de días por funcionario[cite: 5].

### Fase 3: Módulos Especializados y Sensibles (Convivencia y Redes)
* **Objetivo:** Módulos de citaciones, actas confidenciales y reuniones comunales.
* **HU-04 (Citaciones a Apoderados):** Registro de citaciones (RUN/Estudiante, Apoderado, Funcionario, Fecha/Hora, Motivo) con control de estados (*Programada, Asistió, No asistió / Justificada, Reprogramada*)[cite: 6].
* **HU-05 (Acuerdos y Compromisos Sensibles):** Carga de actas categorizadas estrictamente como **Confidencial/Sensible**[cite: 7]. Visibilidad restringida exclusivamente a Convivencia Escolar y Dirección (opcional UTP)[cite: 7]. Búsqueda rápida por RUN o nombre del estudiante[cite: 7].
* **HU-06 (Reuniones Comunales):** Repositorio categorizado (Red UTP, Directores, Convivencia, DAEM/SLEP) con registro de compromisos y plazos institucionales[cite: 7].

### Fase 4: Trazabilidad, Búsqueda Avanzada y Auditoría (Cierre Transversal)
* **Objetivo:** Búsqueda rápida y bitácora de seguridad global.
* **HU-07 (Buscador Avanzado y Filtros Multinivel):** Filtros por tipo de documento, rango de fechas, rol emisor y estado, más barra de búsqueda por texto o asunto[cite: 7, 8].
* **HU-08 (Bitácora de Auditoría y Papelera):** Observers/Listeners en Laravel para registrar en tabla de auditoría: Usuario, Acción (*subió, editó, descargó, eliminó*), ID/Nombre del documento y timestamp[cite: 8]. Eliminación lógica (*Soft Deletes*) accesible solo para el Administrador[cite: 8].