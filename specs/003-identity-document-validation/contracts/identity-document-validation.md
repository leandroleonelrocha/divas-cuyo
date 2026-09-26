# Contract: Validación de identidad y documentos privados

## Scope

Contrato funcional para la sección privada de modelos y la revisión interna en Filament. No reemplaza las rutas públicas existentes ni convierte documentos sensibles en recursos públicos.

## Public authenticated model flow

| Operation | Actor | Preconditions | Result |
|---|---|---|---|
| View status | Modelo propietaria, autenticada | Perfil propio | Devuelve estado de email, identidad y motivo visible si corresponde; no devuelve `storage_path`. |
| Upload document | Modelo propietaria, autenticada y email verificado | Estado `incomplete` o `rejected`; tipo permitido; archivo válido <= 5 MB | Guarda/reemplaza el documento vigente en almacenamiento privado. |
| Submit review | Modelo propietaria, autenticada y email verificado | Los tres tipos obligatorios están presentes y válidos; estado `incomplete` o `rejected` | Cambia identidad a `pending`. |
| Replace document | Modelo propietaria, autenticada | Estado `incomplete` o `rejected` | El nuevo archivo reemplaza al anterior; el anterior se elimina de forma segura. |
| View rejection reason | Modelo propietaria, autenticada | Estado `rejected` | Muestra sólo el motivo de negocio, sin identidad interna del revisor. |

Una persona no autenticada, otra modelo o una modelo con estado `pending`/`approved` recibe rechazo y no obtiene metadatos ni archivo.

## Private file serving

- La visualización/descarga usa una solicitud de Laravel autorizada por `ModelDocumentPolicy`.
- La respuesta entrega el contenido sólo después de comprobar actor, ownership o permiso administrativo y existencia del documento.
- No se emite URL pública permanente, `asset()`, enlace directo a `public/` ni enlace a `storage/app/public`.
- `storage_path` y el nombre físico aleatorio no aparecen en HTML, JSON público, exports ni logs.
- La respuesta usa un nombre de descarga sanitizado y headers privados sin cache: `Cache-Control`, `Pragma` y `X-Content-Type-Options: nosniff`.

## Filament review flow

| Operation | Actor | Preconditions | Result |
|---|---|---|---|
| View documents | Admin verificado (`is_admin = true`) | Documento asociado a perfil existente | Visualiza/descarga mediante autorización server-side. |
| Approve identity | Admin verificado | Estado `pending`; conjunto obligatorio completo; documentos aptos | `identity_status = approved`, auditoría registrada, motivo limpiado; publicación sin cambios. |
| Reject identity | Admin verificado | Estado `pending`; motivo no vacío | `identity_status = rejected`, auditoría y motivo registrados; modelo puede corregir. |

Administradores no verificados, usuarios comunes y cuentas no autenticadas reciben rechazo. La UI puede ocultar acciones no elegibles, pero la Policy y el servicio deben revalidar cada operación.

## Existing moderation integration

| Existing action | Additional identity condition |
|---|---|
| Approve profile | `identity_status = approved` |
| Publish profile | `identity_status = approved` and `review_status = approved` |
| Reject/unpublish profile | Keep existing rules; identity remains a separate state |

Identity approval never changes profile review or publication.

## Observable states and messages

La pantalla pública de identidad puede exponer el estado de verificación del email, `identity_status` y el motivo de rechazo visible para la modelo. `review_status` e `is_published` siguen siendo estados separados del perfil y no cambian por cargar documentos o resolver la identidad.

Las acciones bloqueadas devuelven mensajes claros sin rutas físicas, IDs internos, stack traces ni datos del administrador, por ejemplo:

- `No se puede aprobar el perfil hasta que la identidad esté validada.`
- `No se puede publicar el perfil hasta que la identidad y el perfil estén aprobados.`
- `La documentación no puede modificarse en el estado actual.`

## Error contract

Los errores deben indicar una causa accionable sin revelar rutas físicas, MIME interno innecesario ni datos del administrador:

- archivo no permitido o mayor a 5 MB;
- documento obligatorio faltante;
- estado actual no permite la operación;
- acceso no autorizado;
- documento inexistente o perfil eliminado;
- almacenamiento temporalmente no disponible.

## Data exposure contract

Nunca se incluyen en contratos públicos: `storage_path`, nombre físico, contraseña, tokens, `remember_token`, datos de otra modelo, identidad del administrador ni contenido de documentos ajenos.
