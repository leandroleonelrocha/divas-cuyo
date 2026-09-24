# Guía de diseño — Divas Cuyo

Este documento describe exclusivamente los estilos existentes en `resources/css/styles.css`. Su objetivo es servir como referencia para nuevas pantallas Blade sin introducir valores, componentes o clases que no estén definidos en el proyecto.

## Principios de uso

- Reutilizar las clases existentes según el componente; no crear clases CSS nuevas para resolver variantes visuales.
- Usar las variables de `:root` cuando corresponda, en lugar de repetir colores.
- Mantener la fuente definida por `--font-sans` y la escala de tamaños documentada aquí.
- Los valores no documentados como estados o variantes no tienen una implementación CSS existente.

## Tokens visuales

### Colores

| Uso | Variable/clase | Valor |
|---|---|---|
| Primario rojo | `--color-red` | `#ff1712` |
| Rojo oscuro / hover | `--color-red-dark` | `#d9252f` |
| Texto principal / tinta | `--color-ink` | `#1d1e20` |
| Texto secundario / muted | `--color-muted` | `#77778f` |
| Fondo principal | `--color-paper` | `#f5f5f5` |
| Fondo suave de formularios | `--color-sand` | `#eef0f7` |
| Blanco | `--color-white` | `#fff` |
| Barra superior | uso directo en `.site-top` | `#3b3b3b` |
| Borde de navegación | uso directo en `.site-nav` | `#d8d8dc` |
| Fondo del botón Telegram | uso directo en `.telegram` | `#aaa8a9` |
| Fondo placeholder de perfil | uso directo en `.profile-card` | `#c8c7c7` |
| Degradado de perfil | `.profile-card::after` | de transparente a `rgba(0, 0, 0, .5)` |
| Degradado del hero | `.reference-hero` | `rgba(32, 8, 0, .28)` y `rgba(80, 5, 0, .2)` |
| Etiquetas de perfil | `.profile-location`, `.profile-name` | `rgba(255, 23, 18, .92)` |

La paleta funcional existente es: rojo para acciones, títulos destacados y estados activos; rojo oscuro para hover de acciones; tinta para texto base; muted para navegación y enlaces secundarios; papel como fondo global; sand como superficie de formulario; blanco para texto sobre rojo y superficies de campos.

### Tipografía

- Familia: `"DM Sans", "Helvetica Neue", Arial, sans-serif`, mediante `--font-sans`.
- `body`: `line-height: 1.4`, color `--color-ink`, fondo `--color-paper`.
- No hay una escala tipográfica global definida; los tamaños son locales a cada componente.
- Pesos usados: `700` para navegación, subtítulos, etiquetas y nombres; `800` para logo y títulos principales.

| Elemento | Tamaño | Peso | Otros valores |
|---|---:|---:|---|
| `.site-logo` | `clamp(27px, 3vw, 43px)`; `28px` en <=600px | `800` | `letter-spacing: -0.08em`, `line-height: 1` |
| `.site-links` | `15px`; `12px` en <=900px | `700` | |
| `.telegram` | `19px` | heredado | |
| `.publish` en <=900px | `11px` | heredado | |
| `.profiles-title` | `clamp(25px, 3vw, 33px)` | `800` | centrado |
| `.sponsor-title` | `22px` | `700` | centrado |
| `.profile-location` | `10px` | `700` | |
| `.profile-name` | `19px`; `17px` en <=600px | `700` | centrado |
| `.contact-copy h2` | `clamp(35px, 4vw, 46px)` | heredado | `line-height: 1` |
| `.contact-copy h3` | `22px` | heredado | |
| `.contact-form label` | `15px` | heredado | |

## Espaciado, radios y sombras

### Escala de espaciados observada

No existe una variable de spacing. Los valores actualmente usados son `5px`, `6px`, `7px`, `8px`, `9px`, `10px`, `12px`, `13px`, `14px`, `15px`, `16px`, `20px`, `24px`, `25px`, `28px`, `32px`, `34px`, `35px`, `36px`, `40px`, `46px`, `48px`, `56px`, `68px`, `70px`, `74px`, `92px`, `96px`, `110px` y `112px`.

Usos principales:

- Navegación: `gap: 32px`, `padding: 24px 34px`; en <=900px `padding: 20px`, y en <=600px `gap: 12px`.
- Enlaces: `gap: clamp(16px, 3vw, 48px)`; en <=900px `gap: 14px`.
- Grilla de perfiles: `gap: 14px`; en <=600px `gap: 10px`.
- Sección de perfiles: `padding: 74px 0 96px`; en <=900px `48px 16px 68px`.
- Sección de contacto: `gap: 70px`, `padding-bottom: 110px`; en <=900px `padding: 0 16px 70px`; en <=600px `gap: 35px`.
- Formulario: `padding: 40px`; en <=600px `padding: 28px 20px`.
- Campos: `padding: 15px`, `margin-bottom: 20px`; labels `margin-bottom: 7px`.

### Border-radius

- `.telegram`: `50%` (círculo de `40px`).
- `.publish`, `.contact-form button`: `5px`.
- `.profile-location`: `4px`.
- `.profile-name`: `8px`.
- `.contact-form`: `22px`.
- `.contact-form input`, `.contact-form textarea`: `10px`.

No hay sombras (`box-shadow`) definidas. Los perfiles usan un degradado superpuesto para dar contraste al texto, no una sombra de caja.

## Componentes y clases a reutilizar

### Barra superior y navegación

- `.site-top`: barra superior de `56px`, fondo `#3b3b3b`; pasa a `28px` en <=600px.
- `.site-nav`: contenedor flex centrado, `min-height: 112px`, `max-width: 1340px`, borde inferior de `1px solid #d8d8dc`; en <=900px `min-height: 92px` y padding reducido.
- `.site-logo`: marca en rojo oscuro, peso 800 y tamaño fluido.
- `.site-links`: grupo de enlaces muted, peso 700.
- `.site-links a`: transición de color/opacidad de `180ms ease`.
- `.telegram`: acción circular de `40px`, fondo gris y texto blanco.
- `.publish`: acción principal roja con texto blanco.

Para una navegación Blade, reutilizar `.site-nav`, `.site-logo`, `.site-links`, `.telegram` y `.publish` según corresponda. El primer enlace de `.site-links` se marca activo automáticamente con rojo, subrayado y `text-underline-offset: 8px`; no existe una clase genérica para marcar otros enlaces como activos.

### Botones y variantes

`.publish` y `.contact-form button` comparten la misma base: `padding: 15px 25px`, texto blanco forzado con `!important`, fondo `--color-red`, radio `5px` y transición de fondo/transformación.

En hover, ambos pasan a `--color-red-dark` y se elevan con `transform: translateY(-2px)`. En <=900px, `.publish` usa `padding: 12px 13px` y `font-size: 11px`.

No hay clases de botón secundario, outline, danger, icon-only genérico o loading. Tampoco hay reglas específicas `:active` o `:disabled`.

### Hero

- `.reference-hero`: hero con imagen remota como fondo, `center / cover`, overlay lineal y altura `min-height: clamp(230px, 32vw, 352px)`.
- En <=600px, la altura mínima es `190px`.

Reutilizar `.reference-hero` para el hero existente. No hay clases para variantes de hero, overlay configurable o contenido interno.

### Sección de perfiles

- `.profiles-section`: contenedor de `max-width: 1220px`, centrado, con espaciado vertical.
- `.profiles-title`: título rojo, centrado, peso 800.
- `.profile-grid`: grid de seis columnas (`repeat(6, 1fr)`) con separación de `14px`.
- `.profile-card`: tarjeta relativa, con overflow oculto, proporción `aspect-ratio: .68`, fondo `#c8c7c7` y aislamiento de capas.
- `.profile-card img`: imagen a tamaño completo, `object-fit: cover`; en hover escala a `1.06` durante `300ms ease`.
- `.profile-location`: etiqueta de ubicación en rojo translúcido, arriba a la izquierda.
- `.profile-name`: nombre en rojo translúcido, abajo y a lo ancho de la tarjeta, centrado.

En <=900px, `.profile-grid` pasa a tres columnas. En <=600px, pasa a dos columnas y las etiquetas ajustan posiciones/tamaño. Para una tarjeta Blade, reutilizar exactamente `.profile-card`, `.profile-card img`, `.profile-location` y `.profile-name`.

### Contacto y formulario

- `.contact-section`: contenedor de `max-width: 1220px`, grid de dos columnas, separación de `70px` y padding inferior.
- `.contact-copy`: contenido alineado verticalmente al centro.
- `.contact-copy h2`, `.contact-copy h3`: títulos rojos.
- `.contact-copy a`: enlace muted, subrayado y con `text-underline-offset: 4px`.
- `.contact-form`: superficie `--color-sand`, padding `40px` y radio `22px`.
- `.contact-form label`: label block de `15px`, con margen inferior `7px`.
- `.contact-form input`, `.contact-form textarea`: controles block de ancho completo, fondo blanco, texto ink, borde transparente de `1px`, radio `10px`, padding `15px` y margen inferior `20px`.
- `.contact-form textarea`: altura mínima de `115px`, resize vertical.
- `.contact-form button`: botón de envío, con la variante de acción roja compartida con `.publish`.

En <=600px, `.contact-section` pasa a una columna y el formulario reduce su padding a `28px 20px`. No hay estilos específicos para `.contact-form select`.

## Estados e interacción

### Hover

- `.site-links a:hover`: texto rojo, sin decoración.
- `.publish:hover`, `.contact-form button:hover`: fondo rojo oscuro y desplazamiento vertical de `-2px`.
- `.profile-card:hover img`: escala `1.06`.

### Focus

- `a:focus-visible`, `button:focus-visible`, `input:focus-visible`, `textarea:focus-visible`: outline de `3px solid var(--color-red)` y `outline-offset: 4px`.
- `.contact-form input:focus`, `.contact-form textarea:focus`: borde rojo y `outline: none`.
- `select` no está incluido en los selectores de foco globales ni tiene reglas propias.

### Active, disabled y error

No existen reglas CSS para `:active`, `:disabled`, `:invalid`, `:user-invalid`, clases de error, mensajes de validación ni estados de éxito. No se deben inferir variantes visuales para esos estados a partir de este archivo.

### Movimiento reducido

Con `@media (prefers-reduced-motion: reduce)`, todos los elementos fuerzan `scroll-behavior: auto`, `transition-duration: 0.01ms`; esto reduce las transiciones existentes de navegación, botones e imágenes.

## Layout y responsive

El body tiene `min-width: 320px`. Los contenedores principales `.site-nav`, `.profiles-section` y `.contact-section` tienen anchos máximos de `1340px`, `1220px` y `1220px`, respectivamente, y se centran con `margin: 0 auto`.

| Breakpoint | Cambios existentes |
|---|---|
| `max-width: 900px` | Navegación más compacta; se oculta `.site-links a:nth-child(2)`; botón `.publish` se reduce; perfiles pasan a 3 columnas; secciones agregan padding horizontal de `16px`. |
| `max-width: 600px` | `.site-top` baja a `28px`; se ocultan `.site-links a:nth-child(3)` y `.telegram`; logo queda en `28px`; hero queda en `190px`; perfiles pasan a 2 columnas con gap de `10px`; contacto pasa a 1 columna; formulario reduce padding. |
| `prefers-reduced-motion: reduce` | Se desactiva prácticamente la animación y el scroll suave. |

No hay otros breakpoints definidos.

## Clases utilitarias y convenciones globales

No existe un conjunto de clases utilitarias genéricas como `.container`, `.text-muted`, `.sr-only` o clases de spacing. Las clases existentes son específicas de componentes y deben reutilizarse con su semántica actual.

Convenciones visuales observadas:

- Rojo para identidad, acciones, títulos destacados y navegación activa.
- Texto blanco sobre superficies rojas.
- Superficies suaves y claras para formularios, con controles blancos y radios redondeados.
- Tarjetas de perfil basadas en imagen a sangre, proporción vertical fija, overlay inferior y etiquetas rojas translúcidas.
- Navegación con enlaces muted y transiciones breves.
- Enlaces de contenido secundario subrayados.
- Bordes mínimos y sin sombras de caja.
- Accesibilidad de foco visible definida para enlaces, botones, inputs y textareas.

## Checklist para nuevas vistas Blade

- Usar `body` y las variables de `:root` existentes como base.
- Para navegación, combinar `.site-nav`, `.site-logo`, `.site-links`, `.telegram` y `.publish`.
- Para grillas de perfiles, usar `.profiles-section`, `.profiles-title`, `.profile-grid` y las clases internas de `.profile-card`.
- Para contacto, usar `.contact-section`, `.contact-copy` y `.contact-form` con sus selectores descendientes existentes.
- Mantener la estructura responsive de `900px` y `600px`.
- No asumir estilos para `select`, estados `active`, `disabled` o `error`: el stylesheet actual no los define.
