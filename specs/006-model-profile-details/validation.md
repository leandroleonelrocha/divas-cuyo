# Validación de cierre

Fecha: 2026-09-25

## Automatizada

- `php artisan test tests/Feature/Auth tests/Feature/Account tests/Feature/Identity tests/Feature/ModelPhotos tests/Feature/Profile tests/Feature/Admin tests/Feature/Security`: 188 tests passing, 921 assertions.
- `php artisan test`: 195 tests passing, 949 assertions.
- Pint: passing.
- Las pruebas cubren ownership, campos legacy, identidad privada, fotos, ubicación, disponibilidad, tipos, servicios, bios, revisiones físicas y Filament.

## Compatibilidad

- Se conservaron `users.name`, `users.whatsapp`, `users.location`, `users.is_published` y los campos legacy equivalentes de `model_profiles`.
- La migración de compatibilidad sólo copia valores deterministas a `stage_name` y `approximate_location_text`; no infiere datos privados ni elimina columnas.
- No se incorpora un importador JSON ni un proveedor de mapas.

## Validación visual pendiente

Revisar manualmente `/account/profile` en 360 px, tablet y desktop; `/account`, `/cuenta/{user}` y `/admin/model-profiles/{record}`. Confirmar overflow horizontal, foco visible, labels, estados vacíos, mensajes de éxito/error, controles de tipo/servicios, bios pending/rejected/approved, disponibilidad, ubicación y separación de datos privados/públicos.
