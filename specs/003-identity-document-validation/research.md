# Research: Validación de identidad con documentación privada

## Decision: persistir `identity_status` en `model_profiles`

**Rationale**: La validación es un estado de negocio propio del perfil, no una simple consecuencia de la existencia de archivos. Persistirlo permite representar `incomplete` sin documentos, conservar una decisión administrativa y aplicar las precondiciones de aprobación/publicación sin consultas ambiguas.

**Alternatives considered**:

- Derivar el estado desde `model_documents`: rechazado porque no representa de forma segura una resolución agregada, complica concurrencia y no distingue claramente un conjunto completo de documentos.
- Crear una tabla adicional de validaciones: diferido porque agrega una relación y una entidad para un único estado por perfil sin valor necesario en esta entrega.

## Decision: disco privado de Laravel fuera de rutas públicas

**Rationale**: Los documentos son información sensible. Un disco privado permite que sólo servicios autorizados lean el archivo y evita URLs permanentes, `asset()` y exposición desde `public/` o `storage/app/public`.

**Alternatives considered**:

- `storage/app/public`: rechazado porque el enlace simbólico público permitiría acceso directo sin Policy.
- almacenamiento en `public/`: rechazado por exposición directa, indexación accidental y ausencia de control por ownership.
- proveedor externo de archivos: diferido; no es necesario para la primera entrega y agregaría credenciales, dependencia y contrato operativo.

## Decision: un documento vigente por tipo y eliminación segura al reemplazar

**Rationale**: La política acordada prioriza minimización de datos. La base de datos mantendrá sólo los metadatos del archivo vigente de cada tipo y el archivo anterior se eliminará de forma segura después de confirmar que el nuevo archivo fue persistido.

**Alternatives considered**:

- conservar todas las versiones: rechazado por mayor exposición de DNI/selfies y fuera de la retención inicial.
- cuarentena temporal: diferido como configuración futura si negocio/legal requiere recuperación.

## Decision: formatos y límite

**Rationale**: JPEG, PNG y WebP cubren fotos/escaneos habituales; PDF cubre documentos digitalizados. El límite de 5 MB por archivo controla consumo y superficie de ataque.

**Alternatives considered**:

- confiar en extensión: rechazado porque el nombre no prueba el contenido real.
- aceptar cualquier MIME del navegador: rechazado porque el cliente puede manipularlo.

## Decision: autorización administrativa inicial

**Rationale**: Se reutiliza `users.is_admin = true` junto con email verificado, que es el mecanismo existente. Las Policies deben exponer una capability interna para que luego pueda sustituirse por roles/permisos específicos.

**Alternatives considered**:

- nuevo flag de revisor: rechazado por duplicar administración y agregar un mecanismo de roles fuera de alcance.
- permitir a cualquier usuario autenticado: rechazado por violar ownership y privacidad.

## Decision: integración de publicación

**Rationale**: `ModelProfileModerationService` seguirá siendo la única autoridad de las transiciones de revisión/publicación. Se añadirá una precondición de `identity_status = approved` al aprobar/publicar, evitando duplicación en componentes Filament y endpoints públicos.

**Alternatives considered**:

- controlar sólo visibilidad en la UI: rechazado porque no protege llamadas directas ni concurrencia.
- publicar automáticamente al aprobar identidad: rechazado porque identidad, revisión de perfil y publicación son estados independientes.

## Resolved implementation assumptions

- La validación MIME real usa el mecanismo de inspección de contenido de Laravel/PHP y no sólo la extensión.
- El nombre físico se genera aleatoriamente y no contiene nombre, DNI ni datos de la modelo.
- Las respuestas de archivo se construyen tras autorización y no entregan `storage_path` al navegador como dato de dominio.
- Los límites concretos de `upload_max_filesize`/`post_max_size` del entorno deben ser iguales o superiores a 5 MB y verificarse en el quickstart antes del despliegue.
- La eliminación segura significa borrar el objeto del disco privado y eliminar sus metadatos en una operación coordinada; no se intentará sobrescritura forense del medio.

## Final implementation verification

- El disco `identity_private` usa `storage/app/private/identity`, no tiene URL pública ni visibilidad pública y no participa del enlace simbólico `public/storage`.
- Los paths aceptados para servir o eliminar archivos siguen el patrón generado por la aplicación (`profiles/{id}/{random}.{extension}`); paths externos o con traversal se rechazan.
- La autorización se concentra en la capability `User::isVerifiedAdmin()` para administración y en ownership del `ModelProfile` para la modelo, dejando un punto de migración a permisos específicos.
- La suite final valida regresión de registro, email, panel Filament, carga/reemplazo, revisión, publicación, privacidad y estados independientes.
