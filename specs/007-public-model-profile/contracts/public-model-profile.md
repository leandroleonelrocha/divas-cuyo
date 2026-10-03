# Contract: Perfil e imágenes públicas

## Rutas y autorización

| Método | Ruta | Nombre | Respuesta |
| --- | --- | --- | --- |
| GET | /modelos/{slug} | public.models.show | 200 actual visible; 302 alias visible; 404 resto |
| GET | /modelos/fotos/{publicToken} | public.models.photos.show | 200 WebP autorizado; 404 resto |

Registrar ambas fuera de auth/verified/guest. Formato slug acotado a 160 caracteres ASCII y no sólo numérico; publicToken exige UUID. Resolver explícitamente, sin alterar binding global de ModelProfile. No ruta /modelos de listado. Rutas públicas GET no escriben datos ni requieren formulario/CSRF; futuras entradas mutables conservan Form Requests existentes.

La autoridad `PublicModelProfileVisibility` aplica email verificado, identidad approved, review approved, is_published y principal vigente propia approved con derivado público marcado y legible. Ni sesión administrativa ni disponibilidad alteran la decisión. Gate público acepta User nullable y delega en esa autoridad sin duplicar condiciones; denegación se traduce a 404, nunca 403/login.

## Resolución de slug

- Buscar registro de reserva; ausente, tombstone, perfil sin stage_name/slug actual o no visible → 404.
- Comprobar visibilidad completa antes de crear cualquier Location.
- Slug actual → página 200 con ViewModel público.
- Histórico → 302 con Location generado por ruta interna al slug actual; no aceptar destinos externos ni arrastrar query strings arbitrarios.
- A→B→C: A y B redirigen directamente a C en una sola respuesta. C→A reutiliza A sin bucles.
- Sin reservas ajenas reutilizables, sin endpoint de búsqueda o historial. /modelos/{id} devuelve 404.

Todas las respuestas de estas rutas, incluyendo 302 y 404, usan `Cache-Control: no-store, private`. No ETag/304 que eluda nueva autorización. Alias de un perfil despublicado devuelve exactamente el mismo contenido genérico que slug inexistente, sin Location ni nombre.

## Contrato de representación

Único parámetro de dominio entregado a Blade: PublicModelProfileViewModel. El esquema público está en [data-model.md](../data-model.md). No se pasa profile/user ni relaciones Eloquent. Texto escapado por Blade; bio sin HTML ejecutable, con saltos de línea. Alt de fotos deriva sólo del nombre artístico público. No datos privados ni administrativos en HTML, atributos, comentarios, JSON, metadata, errores o enlaces.

Título `{stage_name} | Divas Cuyo`, H1 con nombre artístico y descripción corta usando nombre, modalidad y ubicación pública disponibles. No derivar edad real ni incluir edad pública oculta. Servicios virtuales/in_person según modalidad actual, activos y asociados; omitidos si tipo ausente/desconocido. Datos físicos sólo canónicos; bio current approved del perfil. Ubicación sólo texto, sin coordenadas, links a mapas ni scripts de geolocalización.

## Contrato de imágenes

- publicToken identifica una versión, no es un permiso. Resolver foto/perfil actuales y aplicar la misma autoridad que la página.
- Requerir current_version_id igual a esa versión, ownership consistente, status approved, public_watermarked_at no nulo, public_path seguro y stream legible. Si falta principal apta, también se deniegan imágenes secundarias.
- Sólo public.webp; no parámetro de variante ni acceso a original/processed/thumbnail/históricos. URL no expone path, nombre original, ID de perfil, foto o versión.
- 200: `Content-Type: image/webp`, `Content-Disposition: inline; filename="photo.webp"`, `X-Content-Type-Options: nosniff`, `Cache-Control: no-store, private`.
- Token desconocido, versión retirada, pendiente/rechazada, foto ajena o ausente, recurso ilegible o perfil oculto: 404 genérico sin detalles. Abrir el stream antes de iniciar respuesta y cerrarlo siempre. No redirigir a storage ni a variante privada.
- Cada nueva solicitud revalida estado actual; no permite caché pública persistente. No se garantiza revocar copias descargadas o bytes de solicitudes ya autorizadas.

## Diseño observable

Cabecera con marca y navegación existente aplicable; sin enlaces a funcionalidades todavía inexistentes. Principal destacada e información en columnas a 1440 px; adaptación a 768 px; contenido apilado a 360 px. Galería ordenada incluye principal, usa dimensiones consistentes y object-fit cover, no requiere lightbox. Principal carga eager, secundarias lazy. No deformación ni overflow.

Características en lista descriptiva; secciones sin contenido se omiten. Badge verificado, disponibilidad y tipo no dependen sólo del color. Foco visible, controles nativos/teclado, contraste 4,5:1 texto normal y 3:1 grande. 404 diseñado común sin indicios de existencia. No contacto, mapa ni widgets externos.

## Casos contractuales mínimos

| Área | Prueba de aceptación |
| --- | --- |
| Elegibilidad | 200 con cinco condiciones; cada condición ausente → 404; unavailable → 200 |
| Slugs | colisión actual/histórica/concurrente, normalización, vuelta a propio, alias oculto sin Location |
| Fotos | vigente marcada visible; original/pending/rejected/anterior denegados; falta principal 404 |
| Reemplazos | pending/rejected conserva anterior; aprobación retira URL vieja |
| Privacidad | valores centinela privados ausentes en toda respuesta y metadata; sin IDs/paths/coordenadas |
| Servicios | virtual excluye presencial inconsistente; inactive desaparece en próxima solicitud |
| Regresión | cuenta, auth, admin, identidad, fotos, perfil, moderaciones y catálogos |

Los intentos ordinarios a slugs inexistentes no necesitan logs por persona. Fallos de preparación/lectura requieren diagnóstico interno con código de evento e identificador técnico sólo en logs restringidos, nunca path, documentos ni contenido privado.
