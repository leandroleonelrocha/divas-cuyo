# Implementation Plan: Panel privado de la modelo y post-login

**Branch**: `004-model-private-panel` | **Date**: 2026-09-16 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `/specs/004-model-private-panel/spec.md`

**Note**: This template is filled in by the `$speckit-plan` command; its definition describes the execution workflow.

## Summary

Crear un punto de entrada privado para modelos en `/account` y resolver el destino post-login en un único componente de redirección. Se reutilizarán la autenticación, `User`, `ModelProfile`, los estados existentes y la sección privada de identidad. El panel se renderizará con Blade y CSS existente; Filament seguirá reservado para administradores.

## Technical Context

<!--
  ACTION REQUIRED: Replace the content in this section with the technical details
  for the project. The structure here is presented in advisory capacity to guide
  the iteration process.
-->

**Language/Version**: PHP 8.2+, Laravel 12

**Primary Dependencies**: Blade, Laravel routing/authentication, Eloquent, Form Requests/Policies existentes, Vite y `resources/css/styles.css`; Filament 5 sólo para `/admin`

**Storage**: MySQL existente; no se requieren tablas ni migraciones nuevas

**Testing**: PHPUnit/Pest mediante `php artisan test`, pruebas HTTP de feature y revisión de vistas/cache

**Target Platform**: Aplicación web Laravel servida por el entorno PHP/MySQL existente

**Project Type**: Aplicación web server-rendered

**Performance Goals**: Renderizar el panel con una consulta acotada del usuario autenticado y su perfil, sin N+1 ni consultas de otras cuentas

**Constraints**: Mantener login, verificación de email, documentos privados y `/admin`; no duplicar estados ni lógica de autorización; no agregar framework CSS ni funcionalidades futuras

**Scale/Scope**: Una ruta canónica `/account`, una vista Blade de dashboard, redirección post-login y cobertura de regresión para features 001–003

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Gate | Resultado | Justificación |
|---|---|---|
| Laravel conventions and simplicity | PASS | Se reutilizan controladores, servicios, Eloquent y Blade existentes; no se agrega dependencia. |
| Explicit and safe persistence | PASS | No hay cambios de schema; el panel lee relaciones existentes con eager loading acotado. |
| Validated and authorized boundaries | PASS | `/account` se resuelve desde el usuario autenticado y se aplica Policy/Gate antes de mostrar datos. |
| Privacy and moderated publication | PASS | No se muestran documentos ni `storage_path`; los estados se presentan separados. |
| Tested and maintainable delivery | PASS | Se agregan pruebas de login, ownership, estados, rutas y regresión. |

## Project Structure

### Documentation (this feature)

```text
specs/004-model-private-panel/
├── plan.md              # This file ($speckit-plan command output)
├── research.md          # Phase 0 output ($speckit-plan command)
├── data-model.md        # Phase 1 output ($speckit-plan command)
├── quickstart.md        # Phase 1 output ($speckit-plan command)
├── contracts/           # Phase 1 output ($speckit-plan command)
└── tasks.md             # Phase 2 output ($speckit-tasks command - NOT created by $speckit-plan)
```

### Source Code (repository root)
<!--
  ACTION REQUIRED: Replace the placeholder tree below with the concrete layout
  for this feature. Delete unused options and expand the chosen structure with
  real paths (e.g., apps/admin, packages/something). The delivered plan must
  not include Option labels.
-->

```text
app/Http/Controllers/Auth/LoginController.php       # destino post-login
app/Http/Controllers/AccountController.php          # entrada privada /account
app/Policies/UserPolicy.php                         # ownership de cuenta
app/Models/User.php                                  # usuario autenticado y relación
app/Models/ModelProfile.php                          # perfil y estados
resources/views/account/dashboard.blade.php         # nueva vista privada
resources/css/styles.css                             # estilos reutilizados/extensiones acotadas
routes/web.php                                       # ruta /account
tests/Feature/Account/ModelAccountPanelTest.php     # acceso y contenido
tests/Feature/Auth/LoginTest.php                     # regresión post-login
```

**Structure Decision**: Aplicación Laravel única con autenticación pública, vistas Blade server-rendered y panel Filament independiente. La nueva ruta `/account` no recibe un ID de usuario: obtiene la cuenta y el `ModelProfile` desde `auth()->user()`.

## Post-login resolution

La redirección se resolverá en `LoginController::store()` mediante un redirector dedicado, preferentemente `AuthenticatedUserRedirector`, invocado después de autenticar y regenerar la sesión. Ese componente tendrá una única decisión:

1. si el usuario es administrador verificado, devuelve la URL de `/admin`;
2. si tiene `ModelProfile` y email verificado, devuelve `/account`;
3. si está verificado pero no tiene `ModelProfile`, devuelve `/account/incomplete-profile`;
4. el usuario no verificado conserva el bloqueo previo y nunca llega al redirector de sesión válida.

Las modelos verificadas siempre aterrizan en `/account`; no se respetan destinos `intended` alternativos. Filament mantiene su propio flujo de autenticación y autorización para `/admin`; no se duplicará esa lógica dentro del dashboard de modelo.

## Account panel design

- `GET /account` será la ruta canónica y resolverá `ModelProfile` desde el usuario autenticado.
- `GET /account/incomplete-profile` será una pantalla interna controlada para cuentas verificadas sin `ModelProfile`; no mostrará datos de otra cuenta ni funcionará como dashboard de modelo.
- El controlador será delgado: cargará el usuario actual y su perfil, aplicará autorización y renderizará la vista.
- La vista mostrará header privado, resumen de datos, cuatro tarjetas de estados y acciones rápidas.
- La acción de identidad enlazará a `identity.show` con el usuario autenticado; no mostrará contenido privado de documentos ni rutas físicas.
- Los accesos a fotos, videos y otros datos serán placeholders no operativos sólo si se decide mostrarlos; no tendrán persistencia ni endpoints nuevos en esta feature.
- Los estilos reutilizarán variables y patrones de `resources/css/styles.css` y la guía visual disponible en `design.md`; no se agregará Tailwind, Bootstrap ni otra dependencia CSS.

## Authorization and data loading

- El acceso HTTP usará `auth` y `UserPolicy::view` sobre el usuario autenticado, sin aceptar `user_id` o `model_profile_id` desde la URL o formulario.
- El perfil se obtendrá con `$request->user()->modelProfile()`; un perfil inexistente tendrá respuesta controlada.
- La consulta será una única carga de usuario/perfil, con sólo las columnas y relaciones necesarias; no se cargarán documentos salvo el enlace hacia la pantalla existente.
- `password`, tokens, `is_admin`, `storage_path` y contenido de documentos no se incluirán en la vista.
- La autorización administrativa continuará dependiendo del mecanismo existente de Filament; `/account` no concederá acceso a `/admin`.

## Compatibility and rollout

No se requiere migración. Se agregarán `/account` y `/account/incomplete-profile` como entradas canónicas y se conservarán los accesos actuales a `/cuenta/{user}` mientras se migra la navegación. El cambio post-login se probará con modelo verificada con perfil, cuenta verificada sin perfil, administrador verificado y modelo no verificada.

## Testing strategy

- HTTP tests para redirecciones post-login, bloqueo de email no verificado, guest y acceso cruzado.
- Tests de vista/feature para datos básicos, cuatro estados separados y enlace a identidad.
- Regresión de registro, login, logout, verificación, recuperación, panel Filament y features 001–003.
- Verificación de que ninguna respuesta del dashboard contiene `storage_path` o datos de documentos.
- Validación de rutas, Blade cache y estilos sin overflow horizontal mediante revisión responsive.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| None | N/A | Se mantiene la aplicación única y no se agregan capas o dependencias nuevas. |
