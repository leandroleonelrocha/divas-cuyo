# Quickstart: Validación de detalles ampliados del perfil

## Prerequisites

- PHP 8.2, Composer, Node/npm y MySQL configurados.
- `.env` válido y base de datos de pruebas disponible.
- Dependencias instaladas.

## Setup

```bash
composer install
php artisan migrate:fresh --seed
npm install
npm run build
```

## Automated validation

Ejecutar el conjunto completo:

```bash
php artisan test
```

Ejecutar grupos de la feature durante el desarrollo:

```bash
php artisan test tests/Feature/Profile
php artisan test tests/Feature/Account
php artisan test tests/Feature/Admin
php artisan test tests/Unit/Services
```

## Manual end-to-end scenarios

1. Crear una cuenta, verificar email y abrir `/account/profile`; confirmar que el perfil propio carga y que un `model_profile_id` ajeno no tiene efecto.
2. Completar datos privados y comprobar que nombre real, fecha de nacimiento y teléfono no aparecen en una salida pública ni en el perfil de otra modelo.
3. Definir fecha de nacimiento y probar edades públicas válidas, fuera de rango y `show_age = false`.
4. Seleccionar provincia y localidad válidas de los catálogos; probar una localidad de otra provincia y confirmar rechazo.
5. Guardar ubicación aproximada y cambiar provincia; confirmar que fotos, identidad, bio, servicios y publicación permanecen.
6. Seleccionar tipo virtual e intentar asociar un servicio presencial manipulando la request; confirmar rechazo.
7. Cambiar a encounters, conservar servicios virtuales y seleccionar uno presencial; volver a virtual, confirmar la acción y comprobar desactivación de servicios presenciales.
8. Crear una bio, verificar `pending`, aprobarla desde Filament y comprobar `current_bio_id`; enviar otra y verificar que la aprobada sigue pública durante la revisión.
9. Modificar un dato físico, comprobar revisión `pending`, rechazar con motivo y aprobar una nueva versión; verificar que sólo la aprobada llega al perfil público.
10. Modificar nombre real o fecha de nacimiento después de identidad aprobada; confirmar reinicio de validación, conservación de documentos y bloqueo de publicación según las reglas existentes.
11. Abrir el perfil en 360 px, tablet y desktop; verificar ausencia de overflow, estados vacíos, errores y mensajes exitosos.

## Regression checklist

- Registro, login, logout y verificación de email.
- `/account`, `/account/photos` e identidad privada.
- Filament `/admin/model-profiles`, moderación de perfil, identidad y fotos.
- `is_published`, `review_status` e `identity_status` no cambian por disponibilidad o ubicación.
- Fotos, documentos y rutas privadas existentes continúan funcionando.

Los campos y relaciones detallados están en [data-model.md](./data-model.md); las rutas y acciones esperadas están en [contracts/account-profile.md](./contracts/account-profile.md) y [contracts/admin-profile.md](./contracts/admin-profile.md).
