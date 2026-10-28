# Revalidación de hallazgos de fase 2

Versión de aplicación revisada: `34b6b8e44ec69917fb4cc965d00bfd8d9f2bd0f0`.

`tests/Feature/Fase2RevalidacionQaTest.php` contiene seis casos sobre el registro de memos con PDF, el listado de correspondencia, el registro de permisos pendientes, el listado de solicitudes propias, el acumulado de media jornada y la protección de solicitudes ajenas.

## Resultados registrados

| Registro | Resultado y alcance |
| --- | --- |
| `Fase2QaTest-run-2-2026-10-25.txt` | Diagnóstico histórico sobre `2b806b360c579f5a1acad1516b6e232756ce76aa`: cinco casos no aprobaron y uno aprobó, con 25 aserciones y código 2. El archivo `Fase2QaTest.php` citado allí era la copia local original, distinta del archivo homónimo incorporado después al repositorio. |
| `Fase2-revision-2026-10-27-run-1.txt` | Seis casos ejecutados y 34 aserciones: cinco aprobaron. HU03-02 recibió HTTP 200, pero falló al buscar el motivo de la solicitud, un campo que la tabla no muestra. La comprobación de ausencia de la solicitud ajena no llegó a ejecutarse. |
| `Fase2-HU03-02-revision-2026-10-27-run-1.txt` | Tras ajustar únicamente HU03-02 para comprobar IDs de la colección y nombres y fechas visibles, el caso aprobó: una prueba, 10 aserciones y código 0. |

Los otros cinco casos conservaron su código y sus resultados de la ejecución inicial; no se ejecutaron nuevamente. En conjunto, las dos ejecuciones de revalidación respaldan la verificación de los cinco hallazgos reportados. No corresponden a una ejecución completa nueva del archivo final ni acreditan todas las funciones de la fase 2.

La carga de PDF utiliza almacenamiento simulado. El caso de acumulado prepara directamente una solicitud aprobada; no valida el flujo de aprobación. Los 22 casos de `tests/Feature/Fase2QaTest.php` no se ejecutaron en esta revalidación.

## Entorno y prueba final

Las ejecuciones se realizaron en Docker con MySQL `testing`. La prueba comprueba el entorno, la base configurada y `SELECT DATABASE()` antes de `RefreshDatabase`. Las configuraciones temporales cambiaron únicamente `DB_HOST` de `127.0.0.1` a `mysql`. Los comandos, resultados y el ajuste de HU03-02 están documentados en las evidencias.

SHA-256 del código final que se incorpora, calculado al preparar su publicación:

`070bf8455c9a9c21f7fb72ec47663acd204e4a46a982fde62819ae11a2a7fe5c`

Este hash identifica el archivo final; no se presenta como un hash registrado durante las ejecuciones históricas.
