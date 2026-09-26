# Contract: Model Photos

## Scope

Contrato observable para la gestión privada de fotografías en Blade y la revisión administrativa
en Filament. No implementa el perfil público completo ni videos.

## Model interface

| Method | Path | Actor | Behavior |
|---|---|---|---|
| GET | /account/photos | Modelo autenticada y verificada con ModelProfile | Muestra galería propia, estados, principal, errores y acciones permitidas |
| POST | /account/photos | Modelo autenticada y verificada con ModelProfile | Carga una imagen válida y crea una nueva versión pending |
| POST | /account/photos/{photo}/replace | Propietaria autorizada | Guarda nueva versión pending sin retirar la versión approved actual |
| PATCH | /account/photos/order | Propietaria autorizada | Reordena exactamente sus fotos y persiste position |
| POST | /account/photos/{photo}/primary | Propietaria autorizada | Selecciona sólo una foto approved como principal |
| DELETE | /account/photos/{photo} | Propietaria autorizada | Elimina la foto lógica, variantes permitidas y compacta posiciones |
| GET | /account/photos/{photo}/file/{variant} | Propietaria autorizada o admin autorizado | Sirve una variante privada mediante Laravel, sin exponer storage_path |
| GET | /account/photos/{photo}/thumbnail/{variant} | Propietaria autorizada o admin autorizado | Sirve una variante autorizada, nunca una URL pública permanente |

### Model interface rules

- No se acepta model_profile_id desde URL, query string, body ni hidden input.
- El perfil se resuelve desde auth()->user()->modelProfile.
- El máximo es cinco ítems lógicos por perfil; no es obligatorio completar cinco.
- Upload permite sólo JPEG, PNG y WebP, hasta 5 MB, dimensiones 800x800 a 5000x5000.
- Las variantes se generan server-side y se almacenan en el disk model_photos.
- La respuesta no incluye rutas absolutas, paths físicos, reviewed_by ni tokens.
- pending y rejected nunca se exponen públicamente.
- Sólo current_version approved puede ser candidato a consumo público futuro.

## Photo lifecycle

- Upload inicial: crea model_photos + model_photo_versions pending.
- Replace: crea nueva versión pending; no sobrescribe la versión física existente.
- Approve: pending → approved y mueve current_version_id dentro de una transacción.
- Reject: pending → rejected con rejection_reason obligatorio.
- Primary: sólo current_version approved; una sola por ModelProfile.
- Delete: elimina el ítem lógico o sus versiones permitidas, borra variantes de forma controlada
  y normaliza posiciones.

## Administrative interface

Filament agrega una sección de fotografías en el detalle de ModelProfile, sin CRUD libre
para cambiar paths ni estados.

| Action | Preconditions | Result |
|---|---|---|
| View | admin verificado y autorizado | Ve imagen privada, metadata y contexto de reemplazo |
| Approve | versión pending y variantes completas | pending → approved, audit fields y current_version actualizado |
| Reject | versión pending y motivo no vacío | pending → rejected, audit fields y motivo guardado |

Cuando existe reemplazo de una versión approved, el administrador puede comparar la versión
pending con la versión aprobada actual antes de resolverla. La moderación no modifica
email_verified_at, identity_status, review_status ni is_published.

## Error behavior

- Invitado → login.
- Usuario no verificado o sin ModelProfile → bloqueo existente.
- Usuario autenticado de otro perfil → 403/404 según la Policy, sin revelar existencia innecesaria.
- Archivo inválido, sobredimensionado o fuera de dimensión → error de validación sin persistencia.
- Límite de cinco alcanzado → error de dominio sin crear registros.
- Transición inválida → error de dominio sin mutar estado.
- Fallo de procesamiento → se eliminan sólo archivos temporales creados para ese intento y se
  conserva la versión vigente anterior.

