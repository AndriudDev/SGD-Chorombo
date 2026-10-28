# Revisión de correcciones de fase 1

Versión de aplicación revisada: `5f37f4a80ca189c7ef49658871ab384700ca6e0e`.

`tests/Feature/Fase1CorreccionesQaTest.php` contiene seis casos sobre la asignación de un único rol y el inicio de sesión con credenciales correctas e incorrectas. Las pruebas de registro público y acceso por rol corresponden a `RegistrationDisabledTest.php` y `RoleAccessTest.php`, ya presentes en el proyecto.

## Resultados registrados

| Registro | Resultado y alcance |
| --- | --- |
| `Fase1-revision-2026-10-26-run-1.txt` | Ocho casos aprobados y 23 aserciones en las dos pruebas existentes. El proceso terminó con código 1 por la opción `--configuration` duplicada; no constituye una ejecución limpia con código 0. |
| `Fase1-H01-H04-2026-10-26-run-1.txt` | Seis casos ejecutados y 48 aserciones: cinco aprobaron y falló la comprobación del mensaje en el HTML posterior al intento de acceso. |
| `Fase1-H04-diagnostico-sesion-2026-10-26-run-1.txt` | Diagnóstico de la continuidad de sesión entre POST y GET. Reprodujo el fallo y permitió identificar que la siguiente solicitud de la prueba no conservaba la cookie de sesión. El archivo no registra el commit; se conserva como diagnóstico auxiliar. |
| `Fase1-H04-cookie-sesion-2026-10-26-run-1.txt` | Tras ajustar la propagación de la cookie recibida, el caso de credenciales incorrectas aprobó: una prueba, 11 aserciones y código 0. |
| `Fase1CorreccionesQaTest-before-H04-cookie-2026-10-26-1.php.txt` | Copia de la prueba anterior al ajuste de H04, referenciada en la evidencia focalizada. |

El ajuste afectó únicamente al caso de credenciales incorrectas. Los otros cinco casos conservaron su código y sus resultados de la ejecución anterior; no se ejecutaron nuevamente. Los registros respaldan la revisión de los cuatro hallazgos reportados, sin equivaler a una nueva ejecución completa del archivo final ni a la validación de toda la fase.

## Entorno y prueba final

Las ejecuciones se realizaron en Docker con MySQL `testing`. Se verificó el entorno y la base activa antes del refresco de la base. Las configuraciones temporales adaptaron `DB_HOST` de `127.0.0.1` a `mysql`. Los comandos y las salidas están conservados en los registros.

SHA-256 del código final que se incorpora, calculado al preparar su publicación:

`814ce17f486effa28e78b7503e3ca3bc5cd5b238fbd5cf7ccabf84bc90791884`

Este hash identifica el archivo final; no se presenta como un hash registrado durante las ejecuciones históricas.
