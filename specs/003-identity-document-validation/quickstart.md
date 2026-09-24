# Quickstart: Validación de identidad con documentación privada

## Prerequisites

- PHP 8.2+ y dependencias Composer instaladas.
- MySQL configurado y funcionando.
- Migraciones existentes aplicadas.
- Filament 5 y panel `/admin` ya instalados.
- Cuenta de modelo con perfil y email verificado.
- Cuenta administradora verificada con `is_admin = true`.
- Disco privado configurado fuera de `public/` y `storage/app/public`.
- `upload_max_filesize` y `post_max_size` del entorno iguales o superiores al límite de 5 MB por archivo.

## Setup

Aplicar las migraciones mediante Laravel:

```bash
php artisan migrate
php artisan config:clear
```

No crear carpetas públicas ni enlaces simbólicos para documentos de identidad. Confirmar que el disco privado no sea accesible mediante una URL directa.

## Complete flow

```text
registro público -> email verificado -> perfil completado
  -> carga de DNI frente, DNI dorso y selfie
  -> identity_status = pending
  -> admin verificado aprueba o rechaza identidad
  -> admin aprueba el perfil cuando identity_status = approved
  -> admin publica sólo cuando identidad y perfil están aprobados
```

La aprobación de identidad no aprueba ni publica automáticamente el perfil. Las rutas privadas son `identity.show`, `identity.documents.store`, `identity.submit` e `identity.documents.download`; esta última sirve el archivo sólo después de verificar Policy y ownership o capability administrativa.

## Manual validation

1. Ingresar como modelo verificada y abrir la sección privada de identidad; confirmar que el estado inicial es `incomplete` y que se solicitan DNI frente, DNI dorso y selfie.
2. Intentar subir un archivo con extensión permitida pero contenido inválido; confirmar que se rechaza y no queda un documento guardado.
3. Intentar subir un archivo mayor a 5 MB; confirmar que se rechaza antes de completar la carga.
4. Subir JPEG, PNG, WebP o PDF válidos de hasta 5 MB para los tres tipos; confirmar que cada documento se guarda con un nombre físico aleatorio y que la interfaz no muestra `storage_path`.
5. Intentar enviar sin uno de los tres documentos; confirmar que el sistema indica el faltante y conserva `incomplete`.
6. Enviar el conjunto completo; confirmar `identity_status = pending`.
7. Mientras está `pending`, intentar reemplazar o eliminar un documento; confirmar que la operación es rechazada.
8. Ingresar como administrador verificado al detalle de la modelo; confirmar que email, identidad, revisión y publicación aparecen como estados separados y que los documentos se visualizan sólo mediante la pantalla protegida.
9. Aprobar identidad; confirmar auditoría, limpieza del motivo anterior, `identity_status = approved`, `review_status` sin cambios e `is_published` sin cambios.
10. Repetir con una validación pendiente y rechazar sin motivo; confirmar que se exige el motivo. Rechazar con motivo y verificar que la modelo puede verlo sin datos internos del administrador.
11. Como modelo, reemplazar un documento rechazado; confirmar que el archivo anterior se elimina de forma segura, sólo queda el vigente y el estado permite reenviar a `pending` cuando el conjunto está completo.
12. Intentar acceder como invitado, usuario común, otra modelo, administrador no verificado o `is_admin = false`; confirmar `401`/redirección o `403` según el flujo, sin archivo, metadatos sensibles ni ruta privada.
13. Intentar aprobar un perfil con identidad distinta de `approved`; confirmar rechazo server-side.
14. Intentar publicar con identidad no aprobada o perfil no aprobado; confirmar rechazo y `is_published = false`. Con ambas aprobaciones, publicar/despublicar debe seguir siendo una acción separada.
15. Probar ownership alterando `model_profile_id` en la solicitud; confirmar que se ignora/rechaza y nunca permite cargar sobre otro perfil.
16. Eliminar o invalidar un documento mientras se consulta; confirmar respuesta controlada sin stack trace ni acción parcial.

El reemplazo sólo está permitido en `incomplete` o `rejected`. Se guarda primero el archivo nuevo con nombre físico aleatorio, se actualiza el documento vigente y luego se elimina el archivo anterior. Si falla la persistencia, el documento anterior permanece disponible. Sólo existe un registro vigente por tipo.

## Automated validation

```bash
php artisan test
vendor/bin/pint --test
php artisan route:list
php artisan view:cache
php artisan optimize:clear
```

La suite debe cubrir upload válido, MIME real, límite de 5 MB, ownership, acceso público/cross-model, admin autorizado/no autorizado, almacenamiento privado, reemplazo, documentos faltantes, las cuatro transiciones, motivos de rechazo, bloqueo de aprobación/publicación y regresión de features 001/002.

## Security checklist

- No existe documento de identidad dentro de `public/` ni `storage/app/public`.
- No se usa `asset()` para documentos.
- Ninguna respuesta pública contiene `storage_path` o nombres físicos.
- Los logs no contienen contenido, rutas privadas, nombres de DNI ni motivos con datos innecesarios.
- Las Policies se aplican en servidor antes de leer o servir el archivo.
- La aprobación de identidad no verifica email, no aprueba el perfil y no publica.
- La descarga responde como contenido privado, sin cache (`no-store`), con `nosniff` y sin revelar la ruta física.
- La autorización administrativa inicial requiere `users.is_admin = true` y email verificado; la modelo sólo opera sobre su propio perfil.

## Future debt

- La retención inicial conserva sólo el documento vigente; cualquier excepción legal deberá formalizar una política configurable.
- `is_admin` podrá migrarse a permisos específicos sin cambiar los contratos de ownership ni de acceso a archivos.
- Si falla el almacenamiento durante una carga o reemplazo, la operación debe fallar sin cambiar el registro vigente; la limpieza posterior debe ejecutarse sobre el disco privado y nunca desde `public/`.
- OCR, reconocimiento facial, biometría, validación externa, auditoría histórica avanzada y módulos de contenido permanecen fuera de alcance.
