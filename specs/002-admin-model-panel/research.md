# Research: Panel administrativo de modelos

## Decisión 1: Usar Filament 5 como panel administrativo

**Decision**: Incorporar `filament/filament` 5.x como la única dependencia nueva de interfaz administrativa y generar un panel `admin` con URL `/admin`.

**Rationale**: El proyecto usa Laravel 12, PHP 8.2 y Tailwind CSS 4; el baseline satisface los requisitos publicados para Filament 5. Filament ofrece recursos CRUD Eloquent, tablas paginadas, búsqueda, filtros, acciones de registro y páginas de detalle, que cubren el alcance sin construir una interfaz administrativa desde cero.

**Alternatives considered**:

- Construir un panel Blade/Livewire propio: descartado porque duplica infraestructura de tablas, formularios, acciones y navegación que Filament ya provee.
- Filament 3: descartado porque la documentación actual identifica Filament 5 como la versión estable vigente y el proyecto ya está en Laravel 12.
- Bootstrap, otro framework administrativo o un paquete de roles completo: descartados por no ser necesarios para esta entrega.

Reference: [Filament installation](https://filamentphp.com/docs/5.x/introduction/installation), [Filament getting started](https://filamentphp.com/docs/5.x/getting-started).

## Decisión 2: Separar cuenta y perfil de modelo

**Decision**: Crear `model_profiles` con relación uno a uno con `users` y mover allí `name`, `whatsapp`, `location` e `is_published`, agregando `review_status`. Mantener en `users` únicamente identidad/autenticación y el nuevo `is_admin`.

**Rationale**: `users` representa credenciales y acceso; `model_profiles` representa los datos administrables del perfil y sus estados de moderación/publicación. Esta separación evita mezclar controles de autenticación con contenido de perfil y permite que la moderación evolucione sin agregar más campos de negocio a `users`.

**Alternatives considered**:

- Mantener todos los campos en `users`: descartado porque no separa cuenta y perfil y hace que cada capacidad de perfil futuro amplíe la tabla de autenticación.
- Mover sólo `whatsapp`, `location` e `is_published`: descartado porque `name` es el nombre público del perfil y debe vivir con el resto de sus datos.
- Crear una tabla de roles/permisos completa: diferida; para el único rol administrativo requerido, `users.is_admin` es suficiente y reduce dependencias.

## Decisión 3: Migración compatible con el registro público

**Decision**: Crear una migración que agregue `users.is_admin`, cree `model_profiles`, copie los campos actuales de cada usuario a un perfil y después actualice el registro público para crear/actualizar el perfil relacionado dentro de la misma transacción.

**Rationale**: Los datos existentes no se pierden y la migración es reproducible en MySQL. Durante el despliegue, el registro Blade debe seguir funcionando con la nueva relación; no se debe mantener una doble escritura indefinida.

**Alternatives considered**:

- Eliminar columnas de `users` en la misma migración sin adaptar el registro: descartado porque rompe el flujo público existente.
- Mantener copias en ambas tablas permanentemente: descartado por riesgo de divergencia.
- Hacer una migración manual en MySQL: descartado por la constitución del proyecto; todo cambio de esquema debe ser una migración Laravel reversible cuando sea práctico.

## Decisión 4: Identificación de administradores

**Decision**: Identificar administradores con `users.is_admin = true` y exigir además una cuenta autenticada y con email verificado para el panel.

**Rationale**: Es explícito, auditable y no requiere una dependencia de roles para un solo permiso. El valor por defecto será `false`; una cuenta se promueve mediante un comando/procedimiento administrativo documentado, no desde el panel de modelos.

**Alternatives considered**:

- Autorizar por dominio de email: descartado porque no representa un permiso persistente y puede autorizar cuentas incorrectas.
- Autorizar por lista fija de emails en configuración: descartado porque no escala ni deja el permiso asociado a la cuenta.
- Paquete de roles/permisos: reservado para una futura necesidad de múltiples capacidades administrativas.

Reference: [Filament security and panel access](https://filamentphp.com/docs/5.x/advanced/security).

## Decisión 5: Autorización en dos niveles

**Decision**: Usar `User::canAccessPanel()` para el acceso al panel y `ModelProfilePolicy` para `viewAny`, `view`, `update` y acciones de moderación/publicación. Las acciones de Filament deben llamar la Policy o un servicio de dominio antes de persistir.

**Rationale**: El acceso al panel evita que usuarios no administradores lleguen a la interfaz; las Policies protegen también requests Livewire, páginas de recurso y acciones directas. Las reglas de publicación se centralizan para impedir publicar perfiles pendientes o rechazados.

**Alternatives considered**:

- Ocultar botones en Filament sin Policy: descartado porque no protege URLs ni requests manipuladas.
- Middleware aislado sin Policy: descartado porque no cubre autorización por registro y acciones de recurso de forma uniforme.

Reference: [Filament security](https://filamentphp.com/docs/5.x/advanced/security), [Filament resource testing](https://filamentphp.com/docs/5.x/testing/testing-resources).

## Decisión 6: Búsqueda, filtros y estados

**Decision**: La tabla de modelos buscará por `model_profiles.name`, `users.email`, `model_profiles.whatsapp` y `model_profiles.location`; filtrará por `email_verified_at`, `review_status` e `is_published`. Las columnas de estados usarán etiquetas diferenciadas para verificación, revisión y publicación.

**Rationale**: Mantiene la consulta alineada con la separación de datos y hace explícita la diferencia entre verificar email, aprobar un perfil y publicarlo.

**Alternatives considered**:

- Un único estado combinado: descartado porque no permite distinguir una modelo aprobada pero temporalmente no publicada.
- Búsqueda sólo por nombre/email: descartado porque el requisito incluye WhatsApp y ubicación.

## Decisión 7: No agregar alcance de contenido

**Decision**: Esta feature no crea tablas, modelos, recursos ni navegación para fotos, videos, planes, favoritos o comentarios.

**Rationale**: Mantiene el panel enfocado en la gestión de cuentas/perfiles y reduce superficie de datos privados y mantenimiento.
