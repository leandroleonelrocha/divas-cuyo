# Evidencia T057 — 2026-10-01

Capturas de Chrome 154 sobre rutas Laravel reales y fixtures sintéticos en `divas_cuyo_test`. Inspección visual realizada por el agente; eventos de teclado enviados a Chrome por CDP. No es un estudio con participantes ni validación en dispositivos físicos.

| Escenario | 360 px | 768 px | 1440 px |
| --- | --- | --- | --- |
| Completo | [Captura](completa-360.png) | [Captura](completa-768.png) | [Captura](completa-1440.png) |
| Mínimo | [Captura](minimo-360.png) | [Captura](minimo-768.png) | [Captura](minimo-1440.png) |
| Textos largos | [Captura](textos-largos-360.png) | [Captura](textos-largos-768.png) | [Captura](textos-largos-1440.png) |
| 404 | [Captura](inexistente-360.png) | [Captura](inexistente-768.png) | [Captura](inexistente-1440.png) |

Cada combinación incluye además `-viewport.png`, `-focus-skip.png`, `-focus-brand.png` y `-skip-target.png`. Los perfiles completo y largo incluyen `-keyboard-end.png` y `-focus-return.png`.

Ejemplos inspeccionados al tamaño de viewport:

- [Primer Tab: enlace de salto, 360 px](completa-360-focus-skip.png).
- [Segundo Tab: marca/inicio, 768 px](completa-768-focus-brand.png).
- [Enter: foco en main, 1440 px](completa-1440-skip-target.png).
- [End: servicios y bio, 360 px](completa-360-keyboard-end.png).
- [Texto sin espacios al pie, 360 px](textos-largos-360-keyboard-end.png).
- [Retorno del foco al inicio, 360 px](completa-360-focus-return.png).

[measurements.json](measurements.json) registra estados HTTP, recursos, dimensiones, contrastes calculados desde estilos efectivos, estados de foco y árbol de accesibilidad. [keyboard-scroll.json](keyboard-scroll.json) registra End y retorno de foco después de finalizar el scroll suave. [provenance.json](provenance.json) identifica la versión de las vistas y CSS observados mediante SHA-256.

Los dibujos son imágenes sintéticas para revisar carga, recorte y distribución; no representan personas ni acreditan calidad fotográfica. No se modificó la funcionalidad ni el diseño para producir estas capturas. Los datos temporales se retiran al finalizar.
