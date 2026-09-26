# Contract: Administración de perfiles

## Scope

Contrato de lectura y acciones para administradores verificados dentro de `ModelProfileResource`. La autorización existente de Filament se conserva.

## Sections

1. **Datos privados administrativos**: nombre real, fecha de nacimiento, edad calculada, teléfono privado y datos administrativos permitidos.
2. **Información pública aprobada**: nombre artístico, edad pública/show age, datos físicos aprobados, disponibilidad, provincia/localidad y referencia aproximada.
3. **Tipo y servicios**: tipo actual, asociaciones válidas y acciones de cambio.
4. **Historial de tipo**: fecha, tipo anterior, nuevo tipo, origen, actor y motivo.
5. **Biografías**: actual, pendientes y rechazadas; acciones de aprobar/rechazar con auditoría.
6. **Revisiones físicas**: snapshot pendiente/aprobado/rechazado; aprobar copia a perfil público, rechazar exige motivo.
7. **Identidad**: sólo se reutiliza el flujo existente; esta feature no edita documentos.

## Rules

- Sólo un administrador verificado puede consultar datos privados o ejecutar moderación.
- Aprobar bio o revisión física no cambia `identity_status` ni publica automáticamente el perfil.
- Un perfil virtual no puede guardar servicios presenciales.
- El cambio de tipo registra historial y no crea un perfil nuevo.
- Las acciones destructivas o eliminación de historial no se ofrecen en la UI administrativa común.
- Las tablas muestran estados en español, badges, fechas legibles y empty states.

## Audit fields

Toda revisión conserva `reviewed_at`, `reviewed_by` y motivo de rechazo cuando corresponda. Todo cambio de tipo conserva `changed_at`, `changed_by_user_id`, `source` y `reason`.
