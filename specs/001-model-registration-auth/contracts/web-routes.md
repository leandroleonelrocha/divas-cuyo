# Web Route Contract: Registro y autenticación

Todas las rutas usan sesiones web, CSRF para formularios mutantes y respuestas/vistas en español. Los nombres son indicativos del contrato funcional; los controladores concretos deben conservarlos al implementar.

| Método y ruta | Acceso | Entrada principal | Resultado esperado |
|---|---|---|---|
| `GET /registro` | Invitada | — | Formulario de registro. |
| `POST /registro` | Invitada | `email`, `password`, `name`, `whatsapp`, `location`, `terms_accepted`, `privacy_accepted` | Validar, crear cuenta no publicada/no verificada, guardar ambas aceptaciones y enviar verificación. |
| `GET /verificar-email/{token}` | Invitada | Token opaco | Verificar solo token vigente y más reciente; marcar cuenta y mostrar resultado. |
| `POST /verificar-email/reenviar` | Invitada | `email` | Respuesta genérica; enviar nuevo enlace solo si aplica e invalidar el anterior. |
| `GET /login` | Invitada | — | Formulario de inicio de sesión. |
| `POST /login` | Invitada | `email`, `password`, `remember` opcional | Autenticar solo cuenta con correo verificado; credenciales/estado inválidos no enumeran la cuenta. |
| `POST /logout` | Autenticada | — | Cerrar sesión y redirigir a login/inicio. |
| `GET /password/forgot` | Invitada | — | Formulario de recuperación. |
| `POST /password/forgot` | Invitada | `email` | Respuesta genérica; solicitar reset nativo si el correo existe. |
| `GET /password/reset/{token}` | Invitada | Token y `email` | Formulario de nueva contraseña si el token sigue vigente. |
| `POST /password/reset` | Invitada | `token`, `email`, `password`, `password_confirmation` | Validar token de 60m, actualizar contraseña y volver inválido el token. |
| `GET /cuenta/{user}` | Autenticada + policy | Usuario objetivo | Mostrar información privada solo si el objetivo es la usuaria autenticada; cualquier otra cuenta es rechazada. |

## Security contract

- Las rutas mutantes requieren protección CSRF.
- Los formularios usan validación de Form Requests y muestran errores sin secretos.
- Respuestas de login, reenvío y recuperación no confirman si un correo existe.
- La cuenta creada comienza siempre con `is_published = false` y no hay ruta pública para cambiar ese estado.
