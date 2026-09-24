# Data Model: Registro y autenticación de modelos

## User / Cuenta de modelo

Tabla existente: `users`, ampliada mediante migration.

| Campo | Tipo/constricción | Regla |
|---|---|---|
| `id` | bigint, PK | Identificador interno. |
| `name` | string, requerido | Nombre público inicial; conservar la convención existente. |
| `email` | string, requerido, único | Normalizar/validar como correo; identifica la cuenta para auth y recuperación. |
| `email_verified_at` | timestamp nullable | Nulo hasta verificar un token vigente. |
| `password` | string, requerido, oculto | Guardar mediante el cast `hashed`; mínimo de entrada: 8 caracteres. |
| `whatsapp` | string, requerido | Dato privado; no se publica. |
| `location` | string, requerido | Ubicación declarada; no se publica. |
| `is_published` | boolean, requerido, default `false` | El registro nunca activa publicación. No existe flujo para cambiarlo en esta entrega. |
| `remember_token` | string nullable | Soporte de sesión Laravel existente. |
| `created_at`, `updated_at` | timestamps | Auditoría estándar. |

Relaciones: `User hasMany PolicyAcceptance`, `User hasOne EmailVerificationToken`.

## PolicyAcceptance / Aceptación de políticas

Tabla nueva: `policy_acceptances`.

| Campo | Tipo/constricción | Regla |
|---|---|---|
| `id` | bigint, PK | Identificador interno. |
| `user_id` | FK a `users`, cascade delete | Propietaria de la aceptación. |
| `policy` | string, valores de dominio `terms`/`privacy` | Una fila obligatoria por política al registrarse. |
| `version` | string, requerido | Versión vigente tomada de configuración. |
| `accepted_at` | timestamp, requerido | Momento de aceptación explícita. |
| `created_at`, `updated_at` | timestamps | Auditoría. |

Restricción: índice único `(user_id, policy)` para impedir duplicar la aceptación vigente durante el registro.

## EmailVerificationToken / Enlace de verificación

Tabla nueva: `email_verification_tokens`.

| Campo | Tipo/constricción | Regla |
|---|---|---|
| `id` | bigint, PK | Identificador interno. |
| `user_id` | FK a `users`, único, cascade delete | Un único token vigente por cuenta. |
| `token_hash` | string, único | Hash del token opaco enviado por correo; nunca guardar el token crudo. |
| `expires_at` | timestamp, requerido | Emisión + 24 horas. |
| `created_at` | timestamp | Momento de emisión. |

Lifecycle: registro crea token; reenvío reemplaza la fila anterior; verificación compara el hash y vigencia, marca `email_verified_at` y elimina la fila; token vencido o sustituido no autoriza.

## Password reset token / Instrucción de recuperación

Tabla existente: `password_reset_tokens`, administrada por el broker nativo.

Reglas: una entrada por correo, token válido por 60 minutos según `config/auth.php`, throttle de nuevas solicitudes de 60 segundos y eliminación/inutilización al completar el reset.

## State transitions

```text
Cuenta nueva
  └─ registro válido + ambas políticas aceptadas
       └─ email_verified_at = null, is_published = false
            ├─ verificación válida dentro de 24h → verificada → login permitido
            ├─ reenvío → token anterior inválido → nuevo token de 24h
            └─ token vencido/inválido → sigue no verificada → pedir reenvío

Cuenta verificada
  ├─ credenciales válidas → sesión autenticada
  ├─ logout → sesión invalidada
  └─ reset válido dentro de 60m → nueva contraseña
```

## Data exposure rules

- Nunca serializar `password`, `remember_token`, tokens crudos ni hashes de token.
- La vista de cuenta devuelve solo el `User` autenticado y sus aceptaciones necesarias; WhatsApp, ubicación y correo no aparecen en rutas públicas.
- No existe endpoint de listado, búsqueda o detalle público para cuentas creadas por esta funcionalidad.
