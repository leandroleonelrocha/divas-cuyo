# Contract: Panel privado de la modelo y post-login

## Scope

Contrato observable para el login y la superficie privada `/account`. No reemplaza el panel Filament ni modifica el contrato de documentos privados.

## Post-login

| Actor | Precondición | Destino |
|---|---|---|
| Administradora | `is_admin = true` y email verificado | `/admin` |
| Modelo | `ModelProfile` existente y email verificado | `/account` |
| Cuenta verificada sin perfil | Email verificado y sin `ModelProfile` | `/account/incomplete-profile` |
| Modelo no verificada | Email no verificado | Bloqueo actual, sin panel |

El orden administrativo tiene prioridad si una cuenta cumple más de una condición. Una modelo verificada siempre aterriza en `/account`, sin respetar destinos `intended` alternativos. Los destinos son internos y conocidos; no se permite redirección externa controlada por el cliente.

La decisión se centraliza en `AuthenticatedUserRedirector`, invocado por el login público después de autenticar. Filament conserva su propio acceso y autorización; las modelos no se envían a `/admin`.

## Account access

| Operación | Actor | Resultado |
|---|---|---|
| Ver panel | Modelo autenticada y verificada | Muestra sólo sus datos y perfil |
| Ver panel | Invitado | Redirección al login |
| Ver panel ajeno | Otra modelo | Rechazo sin datos de la cuenta objetivo |
| Ver panel | Admin | Usa `/admin` como superficie principal; no obtiene una cuenta ajena por `/account` |
| Completar perfil | Cuenta verificada sin `ModelProfile` | Pantalla interna controlada en `/account/incomplete-profile` |

`/account` no recibe `user_id` ni `model_profile_id` por URL, query o request. El usuario y el perfil se resuelven exclusivamente desde la sesión autenticada; cualquier parámetro externo no modifica la cuenta presentada.

## Panel content

El panel muestra nombre público, email, WhatsApp, ubicación, acceso a identidad y cuatro estados separados: email, identidad, revisión y publicación. Puede incluir accesos visuales no operativos para futuras fotos, videos y otros datos, siempre identificados como no disponibles.

El motivo de rechazo de identidad se muestra sólo a la modelo propietaria cuando existe. No se muestran documentos, `storage_path`, nombres físicos, contraseñas, tokens ni datos internos del administrador.

## Existing identity integration

El enlace a identidad debe dirigir a la sección privada existente. Las validaciones, reemplazos, descargas, políticas y transiciones documentales mantienen su contrato actual.

## Error behavior

- Cuenta sin `ModelProfile`: respuesta controlada, sin stack trace ni acceso alternativo.
- Cuenta sin `ModelProfile`: redirección a `/account/incomplete-profile`, con mensaje general y sin datos de otra cuenta.
- Sesión expirada: vuelta al login.
- Perfil no encontrado o fallo controlado de carga: mensaje general sin información sensible.
- Parámetros de cuenta no reconocidos: no cambian el usuario ni el perfil presentado.
