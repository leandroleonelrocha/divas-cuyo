# Research: Registro y autenticación de modelos

## Decisiones

### Autenticación y sesiones

- **Decision**: Usar el guard `web` de sesión, `Auth::attempt`, regeneración de sesión al iniciar sesión e invalidación/regeneración al cerrar sesión.
- **Rationale**: Ya es el guard configurado por defecto en `config/auth.php` y es el camino nativo de Laravel para la aplicación web existente.
- **Alternatives considered**: Agregar Sanctum, JWT u OAuth. Se descartan porque no existe un cliente API en alcance y agregarían dependencias, superficie y configuración sin valor para este slice.

### Verificación de correo

- **Decision**: Implementar `MustVerifyEmail` en `User` para conservar la semántica nativa de Laravel, pero usar un `EmailVerificationService`, una notificación propia y una tabla de token rotatorio. Cada reenvío reemplaza el registro anterior; el token se almacena hasheado, vence a las 24 horas y se elimina al verificarse.
- **Rationale**: El middleware `verified` y `email_verified_at` son capacidades nativas, pero la notificación estándar de verificación no modela la regla de que solo el último enlace enviado sea válido. La tabla propia permite invalidación explícita y evita guardar el token en claro.
- **Alternatives considered**: Usar únicamente `Illuminate\Auth\Notifications\VerifyEmail`. Se descarta porque un enlace firmado estándar no ofrece una invalidación persistida por reenvío con la precisión exigida. Crear un paquete externo de verificación se descarta por la constitución y por falta de necesidad técnica.

### Recuperación de contraseña

- **Decision**: Usar el broker nativo `Password`, la tabla existente `password_reset_tokens`, el trait/contrato `CanResetPassword` y `config/auth.php` con `expire = 60` minutos y throttle de 60 segundos.
- **Rationale**: El proyecto ya tiene la tabla y la configuración exactas para la duración requerida; el broker reemplaza el token asociado al correo y la validación de expiración es nativa.
- **Alternatives considered**: Crear una tabla y flujo de recuperación propio. Se descarta porque duplicaría una capacidad existente y aumentaría el riesgo de manejo incorrecto de secretos.

### Aceptación de términos y privacidad

- **Decision**: Persistir dos registros por cuenta en `policy_acceptances`, con tipo (`terms`/`privacy`), versión y `accepted_at`; las versiones vigentes se leen de `config/policies.php`.
- **Rationale**: Mantiene un registro auditable de ambas aceptaciones sin mezclar datos de consentimiento con los atributos básicos de `users`, y permite cambiar la versión futura sin reescribir el flujo.
- **Alternatives considered**: Dos booleanos y dos fechas en `users`. Se descarta porque no conserva bien la versión aceptada ni escala a futuras políticas.

### Aislamiento de información privada

- **Decision**: Proteger el acceso con middleware `auth` y `UserPolicy::view`, y exponer únicamente una vista de la cuenta autenticada; no habrá ruta de perfil público ni actualización de datos en esta entrega.
- **Rationale**: Cumple el principio de mínimo privilegio y evita que el identificador de otra modelo se convierta en una vía de lectura privada.
- **Alternatives considered**: Filtrar datos sensibles solo en la vista o confiar únicamente en el middleware. Se descartan porque no sustituyen una autorización centralizada y comprobable.

### Interfaz y correo

- **Decision**: Server-rendered Blade en español; usar las notificaciones de Laravel y el mailer configurado (`log` en desarrollo, SMTP u otro en despliegue). Las pruebas inspeccionarán notificaciones/mails con fakes.
- **Rationale**: Reutiliza el único frontend existente y no requiere dependencia externa ni proveedor específico en desarrollo.
- **Alternatives considered**: SPA o proveedor de correo acoplado. Se descartan porque están fuera del alcance y de las convenciones actuales.

## Incertidumbres resueltas

- No quedan decisiones `NEEDS CLARIFICATION` en el Technical Context.
- La expiración de recuperación es 60 minutos, alineada con la configuración existente y la especificación.
- La expiración de verificación es 24 horas, con un único token vigente por usuario.
