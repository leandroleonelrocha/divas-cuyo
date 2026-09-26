# Contract: Panel privado de perfil

## Scope

Contrato de comportamiento para la modelo autenticada, verificada y propietaria de su `ModelProfile`. La resolución del perfil proviene de la sesión; no se acepta `user_id` ni `model_profile_id` como fuente de ownership.

## Routes

| Method | Path | Purpose | Authorization |
|---|---|---|---|
| GET | `/account/profile` | Mostrar formulario dividido por secciones | `auth` + `verified` + perfil propio |
| PUT/PATCH | `/account/profile` | Guardar datos privados, públicos, disponibilidad y ubicación permitidos | misma autorización + Form Request |
| PUT/PATCH | `/account/profile/publication-type` | Cambiar tipo y aplicar servicios compatibles | misma autorización + `PublicationTypeChangeService` |
| POST | `/account/profile/bios` | Crear o reenviar bio | ownership; inicia `pending` |
| PUT/PATCH | `/account/profile/bios/{bio}` | Corregir una bio propia rechazada y crear una nueva versión pendiente | ownership; nunca aprueba |
| POST | `/account/profile/physical-revisions` | Enviar cambios físicos | ownership; inicia `pending` |

Los nombres finales de rutas pueden seguir las convenciones del proyecto, pero ninguna ruta debe recibir un perfil ajeno como fuente de autorización.

## Form sections

- **Privados**: nombre real, apellido, fecha de nacimiento, datos privados permitidos; mostrar aviso de privacidad.
- **Publicación**: `stage_name`, `public_age`, `show_age`, características públicas y nacionalidad.
- **Disponibilidad**: `available` / `unavailable`.
- **Ubicación**: `province_id`, `locality_id`, referencia aproximada y coordenadas aproximadas si se habilitan.
- **Tipo y servicios**: tipo actual, servicios virtuales y presenciales según compatibilidad; al pasar a virtual, confirmación de desactivación presencial.
- **Presentación**: texto de bio, estado, motivo de rechazo propio cuando corresponda.

## Validation and responses

- Datos inválidos devuelven la vista con errores por campo y no guardan cambios parciales.
- `public_age` se valida contra la edad calculada.
- `locality_id` debe pertenecer a `province_id`.
- Servicios presenciales en tipo virtual son rechazados server-side.
- Cambios físicos y bios muestran estado pendiente y no se reflejan públicamente hasta aprobación.
- Cambios reales de identidad reinician el proceso de validación.
- Éxito devuelve mensaje en español y conserva estado de la sección guardada.
- Los estados visibles del panel son “Pendiente de aprobación”, “Aprobada”, “Rechazada” y “No disponible”; ningún texto visible incluye auditoría, revisores o motivos administrativos fuera del contexto propio autorizado.

## Privacy contract

La respuesta Blade sólo incluye datos privados del usuario autenticado y datos públicos del perfil propio. No incluye documentos, rutas de storage, IDs administrativos innecesarios, revisor interno ni datos de otra modelo.
