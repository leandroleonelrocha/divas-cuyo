# Estadísticas de perfiles

- Cuenta: /account/statistics. Solo muestra el perfil del usuario autenticado.
- Administrador: detalle de modelo, pestaña Estadísticas (?relation=statistics).
- Períodos: 7 días, 30 días y mes actual, con fechas de Argentina.
- Eventos: visita visible en navegador y clic en WhatsApp. El enlace no espera al registro.
- Visitas y clics se deduplican por separado por perfil y sesión durante 30 minutos desde el último evento contado. No son personas únicas ni mensajes enviados.
- Se excluyen administradores, el propietario autenticado, bots conocidos y perfiles no visibles.
- Solicitudes protegidas por CSRF, aviso legal y límite de frecuencia. Esto reduce abuso, pero no detecta todos los bots.
- Se guardan totales diarios y hashes HMAC temporales de sesión; no IP ni agente de usuario en estas tablas.
- No se reconstruyen eventos anteriores a la implementación.
- Excluir /estadisticas/sesion y /modelos/{slug}/estadisticas de cachés/CDN. El middleware responde private, no-store.
- Cookies eliminadas, bloqueadores o JavaScript deshabilitado afectan el conteo. El porcentaje es clics deduplicados / visitas deduplicadas, no conversión de personas únicas.

## Despliegue

1. Ejecutar php artisan migrate --force.
2. Activar el scheduler de Laravel (php artisan schedule:run cada minuto). La tarea prune-profile-stat-visitors elimina diariamente hashes con más de 24 horas; conserva los totales.
3. No requiere worker ni proveedor externo de analítica.
4. Probar un perfil visible como visitante, aceptar el aviso legal y tocar WhatsApp. Consultar las estadísticas. Repetir dentro de 30 minutos no incrementa el mismo tipo de evento.
