# Documento de Requerimientos: Gestor Documental Institucional

---

## 1. Información General del Proyecto

| Campo | Detalle |
| :--- | :--- |
| **Institución Educacional:** | Escuela Básica G733 Chorombo Bajo |
| **Comuna:** | María Pinto |
| **Representante:** | Patricia Gajardo Órdenes |
| **Cargo del Representante:** | Directora |
| **Área:** | Educación |
| **Email:** | pgajardo@corpmpinto.cl |
| **Teléfono:** | +56 9 5446 8858 |
| **Contexto Académico:** | Clínica Tecnológica - Instituto Profesional San Sebastián |

---

## 2. Introducción y Contexto

En el contexto actual de la gestión escolar, los equipos directivos enfrentan una alta carga de requerimientos administrativos, normativos y pedagógicos provenientes tanto de la comunidad educativa interna como de entidades externas (DAEM, Servicios Locales de Educación Pública - SLEP, y Superintendencia de Educación). 

La adecuada trazabilidad, resguardo y acceso oportuno a la documentación institucional (memos, oficios, permisos administrativos, acuerdos de convivencia y actas comunales) resulta crítica para garantizar la continuidad operativa, el cumplimiento normativo y la toma de decisiones informada.

---

## 3. Alcance y Perfiles de Acceso (Modelo RBAC)

La solución contempla la parametrización de roles diferenciados bajo un modelo de **Control de Acceso Basado en Roles (RBAC)**, asegurando pertinencia funcional y privacidad:

* **Dirección:** Supervisión transversal, aprobación institucional y acceso total al acervo documental.
* **Administración:** Gestión operativa de permisos administrativos, soporte técnico y mantenimiento de la plataforma.
* **Secretaría:** Registro correlativo, foliación y carga de correspondencia oficial (memos y oficios) y citaciones.
* **Jefatura de UTP:** Centralización de minutas comunales, documentos técnico-pedagógicos y directrices curriculares.
* **Convivencia Escolar:** Registro confidencial de citaciones, entrevistas y acuerdos suscritos con apoderados.

---

## 4. Historias de Usuario (HU)

### Épica 1: Autenticación y Control de Accesos por Rol (RBAC)

#### **HU-01: Gestión de perfiles y permisos diferenciados**
* **Como:** Administrador de la plataforma,
* **Quiero:** Asignar y gestionar roles específicos (Directora, Administrador, Secretaria, Jefe de UTP, Convivencia Escolar),
* **Para:** Asegurar que cada integrante del equipo directivo acceda únicamente a la documentación y acciones pertinentes a su función.

**Criterios de Aceptación:**
* El sistema permite crear usuarios y asociarlos a uno de los 5 roles definidos.
* **Directora:** Acceso total de lectura, descarga, aprobación y supervisión de todos los módulos.
* **Secretaria:** Permisos de carga, edición, foliación/registro y emisión de memos, oficios y citaciones.
* **Convivencia Escolar:** Carga y lectura restringida a actas de acuerdos de apoderados, citaciones y casos de convivencia.
* **Jefe de UTP:** Acceso a documentos pedagógicos, reuniones comunales técnicas y memos/oficios generales.
* **Administrador:** Gestión técnica, respaldo, configuración y asignación de usuarios.

---

### Épica 2: Gestión Documental Institucional (Memos, Oficios y Permisos)

#### **HU-02: Carga, catalogación y foliación de Memos y Oficios**
* **Como:** Secretaria (o miembro autorizado del equipo directivo),
* **Quiero:** Registrar y subir memos u oficios (PDF/Word) indicando número de folio, fecha, destinatario, emisor y materia,
* **Para:** Mantener centralizada y digitalizada la correspondencia oficial interna y externa del establecimiento.

**Criterios de Aceptación:**
* Dado que la Secretaria está en el módulo de correspondencia oficial, cuando completa el formulario ($N^{\circ}$ correlativo/folio, Tipo: Memo/Oficio, Fecha, Destinatario, Asunto) y adjunta el archivo (máx. 15 MB), entonces el documento queda almacenado, indexado y visible para los roles con permisos de lectura.
* El sistema alerta si se intenta duplicar un número de correlativo/folio en el mismo año escolar.

#### **HU-03: Solicitud y registro de Permisos Administrativos**
* **Como:** Integrante del equipo directivo,
* **Quiero:** Cargar o solicitar permisos administrativos adjuntando el formulario o respaldo firmado,
* **Para:** Que la Dirección y Administración lleven el control formal de días utilizados y disponibilidad del personal.

**Criterios de Aceptación:**
* Permite ingresar fecha de inicio, fecha de término, número de días o medias jornadas solicitadas.
* Incluye estado del documento: `Pendiente de V°B°`, `Aprobado`, `Rechazado`.
* La Directora y el Administrador reciben notificación interna o alerta visual sobre solicitudes pendientes.
* Muestra un resumen acumulado de días tomados por funcionario/a durante el año.

---

### Épica 3: Gestión de Convivencia y Apoderados (Citaciones y Acuerdos)

#### **HU-04: Emisión y seguimiento de Citaciones a Apoderados**
* **Como:** Encargado/a de Convivencia Escolar o Secretaria,
* **Quiero:** Generar y registrar citaciones formales a apoderados con fecha, hora, motivo y estudiante asociado,
* **Para:** Llevar una trazabilidad rigurosa frente a requerimientos normativos o seguimiento de casos.

**Criterios de Aceptación:**
* Formulario con campos clave: RUN/Nombre del estudiante, Nombre del apoderado, Funcionario que cita, Fecha/Hora citación, Motivo (Conductual, Pedagógico, Administrativo).
* Posibilidad de adjuntar copia de la citación firmada o colilla de recepción.
* Estado de la citación: `Programada`, `Asistió`, `No asistió / Justificada`, `Reprogramada`.

#### **HU-05: Carga y resguardo confidencial de Acuerdos y Compromisos de Apoderados**
* **Como:** Encargado/a de Convivencia Escolar o Directora,
* **Quiero:** Subir las actas de compromisos y acuerdos firmados en entrevistas con apoderados,
* **Para:** Contar con respaldo legal y pedagógico en caso de seguimiento o fiscalizaciones, protegiendo datos sensibles.

**Criterios de Aceptación:**
* Documentos categorizados como Confidencial/Sensible.
* Acceso de lectura estrictamente restringido a: Convivencia Escolar y Dirección (opcionalmente UTP si el compromiso es pedagógico).
* Búsqueda rápida por nombre o RUN del estudiante.

---

### Épica 4: Archivo de Reuniones Comunales e Interinstitucionales

#### **HU-06: Repositorio de Documentos de Reuniones Comunales (DAEM / SLEP / Redes)**
* **Como:** Jefe/a de UTP o Directora,
* **Quiero:** Cargar minutas, orientaciones técnicas y presentaciones de las reuniones comunales o de microcentro,
* **Para:** Que todo el equipo directivo esté alineado con las directrices comunales y plazos exigidos.

**Criterios de Aceptación:**
* Permite categorizar por: Red de UTP, Reunión de Directores, Mesa Comunal de Convivencia, DAEM/SLEP General.
* Permite registrar compromisos acordados y fecha límite de entrega institucional.
* Todos los roles directivos pueden consultar y descargar este material.

---

### Épica 5: Búsqueda, Auditoría y Trazabilidad

#### **HU-07: Buscador avanzado y filtros multinivel**
* **Como:** Integrante del equipo directivo,
* **Quiero:** Filtrar los documentos por tipo, fecha, remitente, estudiante o palabra clave,
* **Para:** Localizar antecedentes de forma inmediata durante reuniones, auditorías o fiscalizaciones de la Superintendencia.

**Criterios de Aceptación:**
* Filtros disponibles: Tipo de documento (Memo, Oficio, Acta, Citación, etc.), Rango de fechas, Rol emisor, Estado.
* Barra de búsqueda de texto rápido por título o palabras clave del asunto.

#### **HU-08: Registro de actividad y bitácora de auditoría**
* **Como:** Administrador o Directora,
* **Quiero:** Visualizar un historial de quién subió, editó, descargó o eliminó un documento,
* **Para:** Garantizar la integridad y trazabilidad legal de la información del establecimiento.

**Criterios de Aceptación:**
* El sistema guarda automáticamente: Usuario, Acción realizada, ID/Nombre del documento y Marca de tiempo (fecha y hora exacta).
* Los documentos eliminados requieren confirmación y quedan archivados en una "Papelera de reciclaje / Auditoría" accesible solo por el Administrador.

---

## 5. Dependencias y Consideraciones Técnicas

* **Stack de Desarrollo:** Laravel + PHP + MySQL.

---
