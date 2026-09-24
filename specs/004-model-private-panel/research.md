# Research: Panel privado de la modelo y post-login

## Decision: resolver el destino post-login en un redirector dedicado

**Decision**: Mantener `LoginController` como coordinador y delegar la decisión de destino a un `AuthenticatedUserRedirector` o mecanismo equivalente pequeño y testeable.

**Rationale**:

- centraliza la prioridad entre administrador y modelo;
- evita duplicar reglas entre el login público y el acceso a Filament;
- permite conservar el bloqueo previo de email no verificado antes de resolver destinos;
- facilita probar cada combinación de actor sin acoplarla a la vista.

**Order**:

1. administrador con email verificado → `/admin`;
2. modelo con email verificado y `ModelProfile` → `/account`;
3. cuenta verificada sin perfil → `/account/incomplete-profile`;
4. email no verificado → se conserva el bloqueo actual.

Las modelos verificadas con `ModelProfile` siempre irán a `/account`, aunque hayan llegado al login desde otra ruta. El redirector no aceptará URLs externas ni destinos `intended` alternativos del cliente.

La pantalla `/account/incomplete-profile` será una superficie interna mínima para informar que falta completar el perfil. No intentará crear un perfil automáticamente ni seleccionará otro perfil.

## Decision: `/account` sin identificador de cuenta

**Decision**: La ruta canónica será `GET /account`, sin `user_id` ni `model_profile_id` en la URL. El perfil se resolverá desde la sesión autenticada. Las cuentas verificadas sin perfil usarán `GET /account/incomplete-profile`.

**Rationale**: elimina la posibilidad de seleccionar otra cuenta desde el cliente y hace explícito que el panel es de la persona autenticada. Los accesos existentes con identificador podrán conservarse durante la transición, pero no serán la entrada principal.

## Decision: Blade separado de Filament

**Decision**: El panel privado se renderizará con Blade y el CSS existente. Filament continuará siendo exclusivo del panel administrativo.

**Rationale**:

- respeta la separación de audiencias y permisos;
- reutiliza el frontend público y sus convenciones visuales;
- evita incorporar componentes administrativos a la experiencia de la modelo;
- no agrega dependencias ni duplica el panel Filament.

## Decision: reutilizar estados y sección de identidad existentes

**Decision**: El dashboard leerá `email_verified_at`, `identity_status`, `review_status` e `is_published` desde `User`/`ModelProfile` y enlazará a la sección privada de identidad existente.

**Rationale**: evita crear estados combinados o una segunda fuente de verdad. La validación de identidad, aprobación del perfil y publicación seguirán siendo procesos independientes.

## Decision: autorización por ownership

**Decision**: La autorización se aplicará al usuario autenticado y su propio `ModelProfile`, reutilizando `UserPolicy` y el middleware de autenticación.

**Rationale**: el controlador no necesita aceptar IDs de cuenta y no puede cargar datos de otra modelo. Documentos privados sólo se alcanzan mediante la ruta de identidad ya autorizada.

## Decision: carga mínima y UX extensible

**Decision**: El panel cargará sólo la cuenta autenticada y su perfil, y mostrará un resumen con header privado, datos básicos, tarjetas de estados y acciones rápidas.

**Rationale**: reduce consultas y evita N+1 mientras deja una estructura estable para futuras áreas. Fotos, videos y otros datos no tendrán persistencia ni acciones reales en esta entrega.

## Existing visual sources

La implementación deberá tomar como referencia `resources/css/styles.css` y la guía visual disponible en `design.md`. No se detecta un `docs/design.md` en la raíz actual, por lo que no se inventarán tokens alternativos: se reutilizarán las variables y patrones existentes.
