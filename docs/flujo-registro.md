# Registro y bienvenida obligatoria

El registro pide email, contraseña y confirmación. Crea una cuenta sin datos de perfil y envía la notificación de verificación existente. No crea un perfil ni registra aceptación de políticas todavía.

El login mantiene el rechazo de cuentas sin correo verificado. Después de verificar, las cuentas de modelos se dirigen al panel si su inscripción está `active` y completa, a `/account/incomplete-profile` si les faltan datos o a `/account/pending-review` si esperan revisión.

## Datos requeridos

Se trasladaron los requisitos del registro anterior: nombre público, WhatsApp, provincia, modalidad de publicación y aceptación de términos y privacidad. La localidad sigue siendo opcional y debe pertenecer a la provincia. Las selecciones nuevas deben estar activas; retirar posteriormente un elemento del catálogo no invalida inscripciones ya completas.

`EnrollmentRules` comparte las reglas entre el formulario y `ProfileCompletionService`, que comprueba datos persistidos. Además de los datos, se consulta `users.profile_completion_status`: el estado `profile_incomplete` obliga a repetir la carga incluso si los campos ya están completos. La aprobación, identidad verificada, fotos y publicación son independientes de la completitud de inscripción. Los datos privados adicionales del editor de perfil conservan sus validaciones existentes.

Los usuarios existentes conservan sus datos y estados. Quienes ya cumplen los requisitos acceden normalmente; los restantes completan los faltantes sobre el mismo perfil. No se crean perfiles duplicados ni se aprueban automáticamente. La transacción incluye cuenta, perfil, modalidad e historial y aceptación de políticas; una segunda solicitud no duplica el alta.

## Protección de rutas

`EnsureProfileIsComplete` se aplica después de `auth` y `verified` a todas las rutas del panel y de identidad, incluidos archivos y acciones de escritura. Las cuentas incompletas reciben redirección a la bienvenida; las peticiones JSON reciben 403 con `redirect_url`. La bienvenida, su guardado, el cierre de sesión y la verificación quedan fuera del bloqueo. Las rutas públicas y los permisos administrativos conservan su funcionamiento.

## Instalación y comprobación

- Ejecutar `php artisan migrate` al desplegar. La migración `2026_10_19_000001` permite `NULL` en nombre, WhatsApp y ubicación de la cuenta; no elimina datos existentes. Ya aplicada en la base local de desarrollo.
- Ejecutar `npm run build` para actualizar los estilos.
- Conservar la configuración y el worker de correo existentes cuando corresponda; no se modificó el mecanismo de verificación.
- Comprobar registro → correo → verificación → login → bienvenida → guardar → panel.
- Probar también URLs directas y peticiones de escritura con una cuenta incompleta.

Para revertir la migración primero deben completarse o retirarse las cuentas con datos nulos: el rollback rechaza restaurar columnas obligatorias si eso dejaría registros incompatibles.

Las pruebas de autenticación, bienvenida, cuenta, identidad, perfil y seguridad usan cuentas con inscripción completa cuando ese es el requisito de la funcionalidad evaluada. `ProfileCompletionTest` comprueba el bloqueo, catálogos, preservación de estados, transacciones, reintentos y acceso de administradores.

## Estado de inscripción administrable

- Las cuentas nuevas nacen con `profile_incomplete`. Las anteriores conservan `active` al migrar, pero siguen sujetas a la validación de los datos obligatorios.
- En Administración → Perfiles, la acción **Solicitar completar perfil** establece `profile_incomplete`. Está disponible tanto en el listado como en el detalle. La siguiente petición al panel queda bloqueada y redirigida a la bienvenida, incluso con una sesión ya abierta.
- Un envío válido guarda `pending_review` y redirige a una pantalla exclusiva **Tu perfil está en revisión**. Se mantiene la sesión abierta, pero se bloquean las funciones del panel y el reenvío del formulario, incluso por URL directa o JSON. Sólo se permite consultar el estado y cerrar sesión dentro del área de cuenta; las rutas públicas siguen disponibles.
- El administrador puede usar **Finalizar revisión de inscripción** para pasar de `pending_review` a `active`, si los datos siguen completos.
- Estas acciones sólo están autorizadas para administradores verificados, no aceptan cambios de estado enviados por la cuenta y no modifican los estados de publicación, identidad o moderación del perfil.

Migración: `2026_10_19_000002_add_profile_completion_status_to_users.php`.

No se incorporó CAPTCHA en este cambio. La aprobación administrativa de la inscripción (`active`) es necesaria para habilitar el panel. Las cuentas `pending_review` mantienen su bloqueo aunque les falten datos; el administrador debe devolverlas a `profile_incomplete` si necesita correcciones.
