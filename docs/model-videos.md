# Videos de perfiles

Hasta 3 videos por perfil, 100 MB y 60 segundos (config/model-videos.php). MP4/MOV; el servidor inspecciona el contenedor y duración con ffprobe. El archivo recibido permanece privado. Un trabajo produce MP4 H.264/AAC y portada con la marca de agua incorporada. Los archivos se entregan únicamente después de autorización; publicar exige procesamiento completo, marca de agua, aprobación administrativa y perfil visible.

## Puesta en marcha

1. Instalar FFmpeg/ffprobe con libx264 (el Dockerfile ya lo incluye). En MAMP configurar FFMPEG_BINARY y FFPROBE_BINARY con las rutas absolutas. También subir upload_max_filesize a 104M y post_max_size a 110M y revisar límites del proxy/web server.
2. Ejecutar `php artisan migrate`.
3. Ejecutar permanentemente `php artisan queue:work video_database --queue=videos --timeout=900 --tries=3`, mediante el supervisor del servidor. El worker debe compartir base de datos, almacenamiento y caché con la aplicación. No usar la conexión sync para videos.
4. Reiniciar workers después de desplegar: `php artisan queue:restart`.

La conexión video_database reserva trabajos por 960 segundos, más que el timeout de 900 segundos. El bloqueo de procesamiento expira a los 930 segundos. La cola de videos no debe ser atendida por workers con un retry_after menor. Los procesos de ffmpeg tienen timeout propio y dos threads. Si falta el logo o falla el procesado, no se habilita la publicación. El propietario puede reintentar desde Mis videos y ver los motivos de rechazo.

Los límites de duración y dimensiones se validan en segundo plano; una subida aceptada puede terminar en error. Los errores técnicos quedan en failed_jobs/logs, sin exponerlos al usuario. Los originales se conservan hasta eliminar el video para permitir reintentos. El borrado oculta inmediatamente el video y encola limpieza de originales y copias alojadas. Supervisar trabajos fallidos, espacio de disco y copias de seguridad; un borrado fallido se recupera con queue:retry.

## Proveedores externos

Implementar App\Contracts\VideoHostingProvider y registrar la clase en model-videos.providers. MODEL_VIDEOS_PROVIDER determina el proveedor de nuevas cargas; cada video conserva el suyo. El contrato contempla carga idempotente, disponibilidad, reproducción/portada, exportación y borrado. El proveedor debe alojar los recursos de forma privada y entregar respuestas autorizadas o URLs firmadas de corta duración; no devolver URLs públicas permanentes. Las URLs firmadas ya emitidas pueden seguir funcionando hasta su vencimiento después de ocultar el perfil.

La vista usa siempre las rutas de la aplicación, que resuelven el proveedor después de comprobar los permisos. El original nunca se envía al proveedor externo. El proveedor local responde con soporte de HTTP Range para adelantar el video.

`php artisan model-videos:migrate-hosting ID proveedor` copia el MP4 procesado y la portada, comprueba disponibilidad y cambia la referencia de forma atómica. Conserva el proveedor/recurso anterior en hosting_history. Si falla, mantiene el alojamiento original. Al eliminar el video se eliminan también las copias del historial. Mantener registrados los proveedores anteriores mientras existan videos o copias que los referencien.

## Validación

`php artisan test --filter=ModelVideo`

Las pruebas de procesamiento real necesitan FFmpeg y ffprobe. La integración restante usa un procesador simulado para comprobar autorización, moderación, cola, borrado y entrega sin depender de binarios del equipo.
