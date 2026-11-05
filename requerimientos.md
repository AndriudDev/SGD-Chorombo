# Historias de Usuario Actualizadas
**Versión de trabajo para implementación**  
*Base: ocho historias originales y acuerdos de la reunión con Dirección, incorporando la aclaración de que se suben fotografías de actas y documentos cuando no es posible transcribirlos.*

---

## Objetivo y reglas comunes

- **Objetivo principal:** Reducir tiempos de registro, búsqueda, consulta y recuperación documental; prioridad operativa: expedientes, protocolos y suspensiones de Convivencia Escolar.
- **Usuarios previstos:** Seis personas (cinco del equipo de gestión y la secretaria de Dirección). Los cargos comunicados incluyen Dirección, Inspectoría General y Coordinación Académica/Jefatura Técnica.
- **Acceso del equipo:** El equipo de gestión requiere acceso amplio, sujeto a excepciones expresas: UTP no accede a documentos confidenciales/reservados. Los permisos precisos de cada perfil aún requieren matriz validada.
- **Soporte de formatos:** Soportar documentos digitales y también fotografías/imágenes de actas manuscritas, resúmenes de actas y otros documentos que no se transcriben por falta de tiempo. La lista exacta de extensiones y tamaños queda por precisar.
- **Correlativos:** Los documentos y memos deben contar idealmente con correlativo automático desde el 2 de enero; reglas de serie, reinicio y coexistencia con archivos históricos por confirmar.
- **Búsquedas:** Búsqueda por fecha, tema y/o referencia de archivo, combinable cuando corresponda, siempre respetando permisos.
- **Conservación:** Se mencionó un máximo de cinco años y recuperación de eliminados el mayor tiempo posible. Debe conciliarse antes de fijar reglas de eliminación definitiva.

---

## Lectura rápida de cambios frente a la versión inicial

| Historia | Cambio o precisión principal |
| :--- | :--- |
| **HU-01** | Creación de usuarios: Directora e Inspector General; revisión de perfiles reales. |
| **HU-02** | Correlativos automáticos y carga de documentos / fotografías. |
| **HU-03** | Inspectoría General y saldo de días disponibles. |
| **HU-04** | Mantener citaciones; considerar su vínculo con expedientes de Convivencia. |
| **HU-05** | Sin acceso UTP; Dirección numera y marca como reservado. |
| **HU-06** | Prioridad menor; permitir imagen/fotografía de acta manuscrita. |
| **HU-07** | Filtros por fecha, tema y referencia de archivo. |
| **HU-08** | Eliminación por Directora/Inspectoría; papelera y retención por definir. |

---

## Especificación de Historias de Usuario

### HU-01 · Usuarios, cargos y permisos
> **Historia de usuario:** Como Directora o Inspector General, necesito crear y administrar cuentas y asignar accesos según el cargo, para que las seis personas autorizadas utilicen el gestor con permisos adecuados.

#### Requisitos para implementación
- Permitir crear cuentas y gestionar accesos a Dirección e Inspectoría General.
- Representar los cargos/funciones efectivamente usados: Dirección, Inspectoría General, Coordinación Académica o Jefatura Técnica, Convivencia Escolar y Secretaría de Dirección; validar perfiles adicionales si corresponden.
- Distinguir permisos de consulta, carga, edición, descarga, eliminación y administración según el área y el nivel de reserva.
- Inspectoría General gestiona documentación de contratos, horarios y permisos administrativos; Coordinación Académica, documentación académica; Convivencia Escolar, expedientes, protocolos y suspensiones.
- Restringir expresamente los documentos confidenciales/reservados según **HU-05**.

#### Criterios de aceptación verificables
- Solo una cuenta autorizada para administrar usuarios puede crear usuarios y asignar perfiles.
- Cada perfil muestra y permite únicamente acciones autorizadas; una petición directa sin permiso también debe bloquearse.
- UTP no puede visualizar ni descargar documentos reservados.

> ⚠️ **Por confirmar antes de implementar:** Confirmar lista definitiva de seis personas y correspondencia cargo $\leftrightarrow$ rol (las funciones originales diferían). Definir acceso concreto de Secretaría, Convivencia y administración técnica a cada módulo.

---

### HU-02 · Memos, oficios y documentación oficial
> **Historia de usuario:** Como Secretaría o persona autorizada, necesito registrar correspondencia y archivos con numeración automática para localizarlos rápidamente sin foliación manual.

#### Requisitos para implementación
- Conservar tipo memo/oficio, fecha, destinatario, emisor y asunto o materia de la historia original.
- Generar idealmente folio o correlativo único y secuencial por el sistema desde el 2 de enero, conforme a reglas acordadas de numeración.
- Permitir adjuntar documentos digitales de texto y fotografías/imágenes de actas, resúmenes u otros documentos no transcritos.
- Guardar los archivos asociados a su registro y permitir búsquedas y consultas por perfiles autorizados.
- Impedir colisiones del correlativo según la serie y el período que se definan.

#### Criterios de aceptación verificables
- Al crear dos documentos consecutivos dentro de la misma serie, cada uno recibe folio diferente y ordenado.
- Los registros y archivos permitidos se guardan, consultan y descargan según autorización.
- La búsqueda recupera un documento por fecha, tema o referencia sin mostrar documentos prohibidos.

> ⚠️ **Por confirmar antes de implementar:** Precisar si la numeración comienza cada 2 de enero y se reinicia anualmente; si existe una serie por tipo. Confirmar formatos y tamaño máximo: la ficha original proponía PDF/Word hasta 15 MB, pero la reunión agregó fotografías.

---

### HU-03 · Permisos administrativos y saldo disponible
> **Historia de usuario:** Como Inspectoría General, necesito registrar y controlar permisos administrativos y visualizar los días que le quedan a cada funcionario, para gestionar solicitudes con rapidez.

#### Requisitos para implementación
- Registrar funcionario, fecha de inicio y término, días completos o medias jornadas, y respaldo documental cuando corresponda.
- Conservar estados *Pendiente de V°B°*, *Aprobado* y *Rechazado* de la historia original.
- Mostrar por funcionario días usados y días disponibles/restantes en el período correspondiente.
- Mantener alerta o indicador de solicitudes pendientes para los perfiles autorizados.
- Inspectoría General es responsable operativa de contratos, horarios y permisos administrativos; Dirección accede según la matriz aprobada.

#### Criterios de aceptación verificables
- Una solicitud aprobada actualiza el cálculo de días usados y restantes conforme a la política definida.
- Una solicitud pendiente o rechazada no descuenta días salvo regla expresamente definida.
- Las personas con permiso pueden consultar estado y saldo; otros perfiles no pueden aprobar o alterar solicitudes.

> ⚠️ **Por confirmar antes de implementar:** Definir cupo inicial anual, reglas de media jornada, recálculo al editar/rechazar y quién autoriza cada solicitud. Determinar si contratos y horarios requieren módulos separados o solo clasificación documental.

---

### HU-04 · Citaciones a apoderados
> **Historia de usuario:** Como personal autorizado de Secretaría o Convivencia Escolar, necesito registrar y seguir citaciones para mantener antecedentes organizados y localizar cada caso.

#### Requisitos para implementación
- Mantener campos originales: estudiante y RUN, apoderado, funcionario que cita, fecha, hora y motivo (*Conductual/Pedagógico/Administrativo*).
- Permitir adjuntar el respaldo de citación, colilla firmada o fotografía del documento, según formatos autorizados.
- Mantener estados *Programada*, *Asistió*, *No asistió/Justificada* y *Reprogramada*, sujetos a precisión del flujo.
- Permitir asociar o referenciar la citación con antecedentes de Convivencia Escolar si se define un expediente común.

#### Criterios de aceptación verificables
- Un usuario autorizado registra y localiza la citación; otro sin autorización no puede modificarla.
- El estado y los adjuntos se reflejan correctamente al consultar el registro.
- La consulta respeta los límites de acceso documental.

> ⚠️ **Por confirmar antes de implementar:** Definir si Convivencia tendrá expediente integral que agrupe citaciones, protocolos y suspensiones. Precisar reglas de reprogramación, historial y estados de asistencia.

---

### HU-05 · Documentos confidenciales y reservados
> **Historia de usuario:** Como Directora, necesito clasificar y numerar documentación reservada, para proteger actas y antecedentes sensibles y controlar quién puede consultarlos.

#### Requisitos para implementación
- Permitir adjuntar actas/acuerdos y otros antecedentes de carácter confidencial o sensible.
- Dirección asigna numeración y clasificación de reserva a los archivos correspondientes.
- UTP/Coordinación Académica no tiene acceso a los documentos confidenciales o reservados, incluso si su contenido es pedagógico.
- Aplicar permisos tanto en listados y búsquedas como en descargas y consultas directas.
- Asegurar que los archivos no se expongan por enlaces públicos que eviten la autorización del sistema.

#### Criterios de aceptación verificables
- Un perfil no autorizado no puede encontrar, abrir ni descargar documentos reservados mediante ninguna ruta.
- Dirección registra y consulta la clasificación y numeración reservada.
- Las consultas por nombre/RUN, si se habilitan, no revelan documentos a perfiles no autorizados.

> ⚠️ **Por confirmar antes de implementar:** Precisar quiénes, además de Dirección, pueden acceder a reservados; la indicación explícita es que UTP no puede. Definir numeración reservada, política de conservación y auditoría de acceso.

---

### HU-06 · Reuniones comunales y actas
> **Historia de usuario:** Como Directora, necesito cargar fotografías de actas manuscritas y otros documentos de reuniones comunales para conservarlos sin necesidad de transcribirlos.

#### Requisitos para implementación
- Permitir registrar reunión, fecha, tema y, cuando exista, fotografía o imagen del acta manuscrita.
- Conservar las categorías originales si el equipo confirma su utilidad: *Red de UTP*, *Reunión de Directores*, *Mesa Comunal de Convivencia* y *DAEM/SLEP General*.
- Mantener búsqueda y descarga por usuarios autorizados.
- Registrar compromisos y plazos cuando se documenten; no presuponer que todas las reuniones requieren seguimiento complejo.
- Considerar el módulo de prioridad operativa menor, según la directora: reuniones con otros directores y poca incidencia diaria.

#### Criterios de aceptación verificables
- La Directora puede incorporar una imagen de acta y recuperarla posteriormente.
- La reunión se localiza por fecha, tema o referencia, respetando permisos.
- Compromisos y fechas, cuando se registran, quedan asociados a la reunión correcta.

> ⚠️ **Por confirmar antes de implementar:** Precisar si solo Dirección puede cargar/editar o si UTP también participa en este módulo. Confirmar si se mantienen obligatoriamente las cuatro categorías y el seguimiento de compromisos.

---

### HU-07 · Búsqueda y filtros documentales
> **Historia de usuario:** Como integrante autorizado del equipo de gestión, necesito encontrar documentos por fecha, tema o referencia para ahorrar tiempo durante la gestión cotidiana.

#### Requisitos para implementación
- Incorporar filtros por fecha (o rango), tema/asunto y referencia/folio/identificador de archivo.
- Permitir utilizar uno o varios filtros de forma combinada.
- Conservar las búsquedas originales por título, palabras clave, tipo, emisor o estado cuando sean aplicables al módulo.
- Aplicar siempre los permisos: la búsqueda no revela documentación confidencial o reservada a perfiles excluidos.

#### Criterios de aceptación verificables
- Los resultados corresponden a los filtros aplicados y muestran la referencia que permite identificar el archivo.
- La combinación de filtros restringe los resultados de forma coherente.
- Un perfil sin acceso no recibe coincidencias, nombres ni referencias de documentos reservados.

> ⚠️ **Por confirmar antes de implementar:** Definir qué significa exactamente “referencia de archivo” (folio, código interno, nombre o todos). Precisar si la búsqueda será global o separada por módulo.

---

### HU-08 · Auditoría, eliminación y papelera
> **Historia de usuario:** Como Directora o Inspector General, necesito controlar eliminaciones y recuperar documentos borrados por error, manteniendo la trazabilidad de la información.

#### Requisitos para implementación
- Restringir la eliminación directa de documentos a Dirección e Inspectoría General.
- Solicitar confirmación antes de eliminar y mantener una papelera que permita recuperación autorizada.
- Registrar cargas, ediciones, descargas y eliminaciones con usuario, documento y fecha/hora, conforme a la historia original.
- Mantener documentos eliminados recuperables el mayor tiempo posible dentro de la política de conservación que se acuerde.
- Considerar la indicación de máximo cinco años de conservación documental, sin fijar borrado automático hasta resolver su alcance.

#### Criterios de aceptación verificables
- Perfiles distintos de Dirección e Inspectoría no pueden eliminar documentos ni directamente mediante URL.
- Un documento enviado a papelera deja de aparecer como activo y puede recuperarse por usuario autorizado.
- La auditoría identifica quién realizó la acción y cuándo.

> ⚠️ **Por confirmar antes de implementar:** Aclarar contradicción entre “máximo cinco años” y “papelera el mayor tiempo posible”: inicio del cómputo, excepciones y borrado definitivo. Definir quién puede restaurar, vaciar papelera y consultar la auditoría.

---

## Prioridad funcional: Convivencia Escolar

La directora señaló como necesidad prioritaria sistematizar expedientes, protocolos y suspensiones, de modo que el área sea más operativa y reduzca tiempos. Esta necesidad no está completamente descrita en las ocho historias originales.

**Decisión pendiente para el equipo:** Definir si se amplían `HU-04` / `HU-05` o se incorpora una historia adicional específica para el expediente de Convivencia Escolar, con flujo, campos, permisos y criterios de aceptación propios. No debe asumirse su alcance sin validación.

---

## Validaciones necesarias antes de implementar

1. **Matriz de permisos:** Confirmar matriz definitiva de seis usuarios, perfiles y acceso a cada área; resolver acceso amplio del equipo frente a documentos reservados.
2. **Correlativos:** Precisar correlativos automáticos: fecha de inicio (2 de enero), reinicios, series y administración de documentos históricos.
3. **Formatos:** Confirmar formatos digitales admitidos, fotografías de actas y tamaños máximos; mantener la carga simple para evitar transcripciones.
4. **Permisos administrativos:** Definir cupo, descuentos y reglas de días disponibles de permisos administrativos.
5. **Convivencia Escolar:** Definir flujo mínimo de expedientes, protocolos y suspensiones de Convivencia Escolar, como prioridad de la escuela.
6. **Conservación:** Resolver conservación de hasta cinco años y recuperación en papelera “el mayor tiempo posible”.
7. **Baja prioridad:** Acordar qué funcionalidades de reuniones comunales se mantienen por su baja prioridad operativa.

---

> **Control de cambios:** Versión editable de requisitos para compartir con el desarrollador. No constituye autorización para interpretar libremente las decisiones que figuran pendientes.