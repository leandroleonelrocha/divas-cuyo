# T058 — Guía preparada para cinco participantes

**Estado vigente (2026-10-01): preparada, no ejecutada y POSTERGADA por decisión explícita del usuario; no requerida para el cierre técnico del MVP.** El motivo es cerrar la entrega técnica sin realizar este estudio en esta etapa. El protocolo siguiente se conserva para una eventual reactivación; sus reglas no implican que se hayan realizado sesiones. No hay resultados humanos registrados. Esta guía cubre exclusivamente SC-008; no inicia T059 ni T060.

## Objetivo y preparación de la sesión futura

SC-008 exige que **al menos cuatro de cinco personas identifiquen correctamente nombre artístico, disponibilidad y tipo de publicación en menos de 30 segundos, sin ayuda ni login**. Los tres datos deben ser identificados por la misma persona dentro de ese tiempo.

- Invitar a cinco personas reales, P01–P05, individualmente. Evitar que observen sesiones anteriores o conozcan las respuestas de antemano. No contactar participantes como parte de esta preparación.
- Usar perfiles sintéticos en un entorno aislado. No utilizar producción ni `divas_cuyo`. Si se usa MySQL, conservar las guardas de `PUBLIC_PROFILE_MYSQL_TEST_*` y el sufijo `_test`. En esta preparación no se crean fixtures ni se conecta a ninguna base.
- Preparar un perfil completo con los tres datos presentes, bio, características, servicios y varias fotos; y otro mínimo con secciones opcionales ausentes. El mínimo no sirve para medir SC-008 si carece de modalidad.
- Fijar previamente las respuestas correctas del perfil completo en la ficha del moderador. No mostrarlas a los participantes. Admitir paráfrasis inequívocas de las etiquetas públicas; no confundir disponibilidad con modalidad ni inferir posibilidad de reservar.
- Mantener la misma versión y perfil completo para los cinco. Propuesta de reparto: P01–P03 a 360 px, P04 a 768 px y P05 a 1440 px. Registrar viewport real, navegador y dispositivo; este reparto no acredita resultados por cada tamaño con una muestra suficiente.
- Abrir una sesión de navegador sin autenticar, situar la página arriba y comprobar carga antes de mostrarla. No predesplazarla hacia la modalidad. Preparar cronómetro y ficha. Las rutas temporales de T057 ya fueron retiradas: habrá que recrear el entorno cuando se autorice ejecutar T058.

## Guion breve del moderador

> Vamos a revisar si este perfil se entiende con facilidad. Estamos evaluando la página. No necesitás iniciar sesión ni ingresar datos personales. Te voy a pedir cuatro cosas; podés desplazarte como lo harías normalmente. En la primera voy a medir el tiempo y no voy a darte pistas. Después conversaremos sobre lo que te resultó claro o confuso. ¿Podemos comenzar y tomar notas anónimas?

No exigir pensar en voz alta durante la tarea cronometrada. No señalar elementos, sugerir dónde buscar ni confirmar respuestas mientras se mide. Si solicita ayuda, responder: «Hacé lo que harías normalmente; lo conversamos al terminar». Registrar cualquier pista efectivamente dada como ayuda.

## Cuatro tareas para cada participante

| Orden | Consigna que se lee | Qué observar |
| --- | --- | --- |
| 1 — SC-008 | «En este perfil, decime el nombre artístico, si figura disponible o no disponible y qué modalidad ofrece». | Las tres respuestas literales, tiempo conjunto y ayuda. |
| 2 — Lectura | «Buscá la presentación personal y contame algo que diga. Después encontrá una característica física publicada». | Si localiza bio y características y puede leer un dato de cada una. |
| 3 — Servicios y fotos | «Decime un servicio que ofrece y encontrá una foto distinta de la principal». | Si distingue servicios e imágenes secundarias; dificultades de lectura o desplazamiento. No exigir ampliar fotos: la galería actual no ofrece esa interacción. |
| 4 — Información ausente | Mostrar el perfil mínimo: «Decime qué podés saber de este perfil y qué información que buscaste antes ya no aparece». | Si reconoce secciones ausentes sin interpretar datos no publicados como errores o inventar información. |

Leer la tarea 1 antes de revelar el perfil. Iniciar el cronómetro cuando la página cargada se muestre al participante, sin otro contenido que tape el perfil. Detenerlo cuando dé su respuesta final con los tres datos. No corregir ni completar respuestas. Si no termina, permitir hasta 60 segundos para observar la dificultad y registrar «no completó en 60 s». A los 30 segundos ya no puede cumplir SC-008; no anunciar ese umbral durante la tarea.

Registrar errores y autocorrecciones espontáneas, sin otorgar un segundo intento puntuable. Una interrupción técnica invalida la medición: anotarla y reprogramar antes de evaluar el criterio; no reemplazar un fallo de comprensión por otro participante. Hacer las tareas 2–4 después de la medición, sin límite de éxito cronometrado; duración orientativa total de sesión: 5–8 minutos.

## Preguntas posteriores

1. ¿Qué información viste primero y qué te costó encontrar?
2. ¿Qué entendiste por el estado de disponibilidad y por la modalidad?
3. ¿Pudiste leer cómodamente la biografía, las características y los servicios? ¿Dónde hubo dificultad?
4. ¿Cómo interpretaste el perfil con menos información? ¿Te pareció que faltaba cargar algo?
5. ¿Qué cambiarías para entender el perfil más rápido?

No anticipar respuestas esperadas. Recoger una frase literal relevante por persona y separar esa cita de la interpretación del moderador.

## Criterios de éxito y decisión futura

- **Éxito individual SC-008:** tres respuestas correctas, tiempo estrictamente menor que 30,0 s, ninguna ayuda y ningún login, en el primer intento válido. Exactamente 30,0 s no cumple.
- **Éxito del grupo SC-008:** al menos 4/5 éxitos individuales con cinco sesiones válidas. No usar promedio de tiempos ni sumar aciertos parciales entre personas.
- **Menos de cinco sesiones válidas:** evidencia incompleta; T058 permanece pendiente. No computar sesiones ausentes como éxitos o fallos.
- **Cinco sesiones y menos de cuatro éxitos:** SC-008 no satisfecho; registrar obstáculos y mantener T058 pendiente. Una futura repetición debe conservar la evidencia de esta ronda y distinguir versión y participantes; no mezclar rondas para alcanzar 4/5.
- **Tareas 2–4:** observación complementaria, con resultado «sin ayuda», «con ayuda» o «no completada». No agregan ni sustituyen éxitos de SC-008. Un problema descubierto se documenta; este protocolo no autoriza cambios funcionales automáticos.

## Plantilla simple de registro

Copiar y completar sólo al ejecutar sesiones reales. `—` significa sin dato, no cero ni fallo.

**Ronda:** — · **Fecha:** — · **Moderador:** — · **Versión/hash:** —

**Perfil completo/URL:** — · **Perfil mínimo/URL:** —

**Clave del moderador:** nombre — · disponibilidad — · modalidad —

| Persona | Dispositivo / navegador / viewport | Respuestas: nombre / disponibilidad / modalidad | Tiempo (s) | Ayuda | Login | SC-008: sí/no/inválida |
| --- | --- | --- | --- | --- | --- | --- |
| P01 | — | — | — | — | — | — |
| P02 | — | — | — | — | — | — |
| P03 | — | — | — | — | — | — |
| P04 | — | — | — | — | — | — |
| P05 | — | — | — | — | — | — |

| Persona | Tarea 2: bio/físicos | Tarea 3: servicios/fotos | Tarea 4: ausencias | Dificultad observada / cita / evidencia |
| --- | --- | --- | --- | --- |
| P01 | — | — | — | — |
| P02 | — | — | — | — |
| P03 | — | — | — | — |
| P04 | — | — | — | — |
| P05 | — | — | — | — |

**Incidentes técnicos o desviaciones del guion:** —

**Sesiones válidas:** —/5 · **Éxitos SC-008:** —/5 · **Conclusión:** pendiente de ejecución.

## Evidencia que se guardará en validation.md

1. Fecha, identificador de ronda, moderador, versión observada y enlaces a los perfiles/fixtures; respuestas esperadas y captura inicial sin pistas de cada variante usada.
2. Las dos tablas completadas con datos reales, respuestas literales, tiempos, ayuda/login y validez. Identificar participantes únicamente con P01–P05; no guardar nombres ni datos de contacto.
3. Incidentes y citas posteriores que expliquen las dificultades, distinguiendo observación de interpretación. No convertir preguntas posteriores en un nuevo intento de la tarea 1.
4. Enlaces a notas por sesión o capturas relevantes. Una grabación de pantalla es opcional y requiere acuerdo previo del participante; si sólo se usa cronómetro y notas, declararlo. Las capturas de T057 no prueban tiempos ni respuestas humanas de T058.
5. Conteo explícito de éxitos sobre cinco sesiones válidas, aplicación del umbral estricto y conclusión: «evidencia incompleta», «SC-008 no satisfecho» o «SC-008 satisfecho». Cualquier decisión posterior sobre la casilla de T058 debe basarse en esa evidencia, nunca en esta guía preparada.

**En esta preparación:** no se realizan sesiones, no se rellenan respuestas o tiempos, no se marca T058, no se ejecutan T059/T060 y no se hace commit.
