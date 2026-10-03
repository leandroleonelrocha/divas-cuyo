# Evidencia del cierre técnico — 2026-10-01

Registros reales de T059 y conciliación T060 en [validation.md](../../validation.md). T058 postergada por el usuario; SC-008 sin evaluar. Sin despliegue ni commit.

| Ejecución | Registro | Resultado |
| --- | --- | --- |
| Suite completa `php artisan test --no-ansi` | [Salida](php-artisan-test.txt) | 299 tests / 1876 assertions; exit 0 |
| `vendor/bin/phpunit --configuration phpunit.mysql.xml` | [Salida](mysql-integration.txt) | 7 tests / 188 assertions; exit 0 |
| `vendor/bin/pint --test` inicial | [Salida](pint-initial.txt) | exit 1: un fallo previo de formato |
| Pint global después de corregir import de Htmlable | [Salida](pint-final.txt) | exit 0 |
| `php artisan test tests/Feature/Admin --no-ansi` posterior al formato | [Salida](admin-after-format.txt) | 49 tests / 264 assertions; exit 0 |
| `npm run build` | [Salida](vite-build.txt) | exit 0 |
| Consulta independiente de base efectiva/tablas | [Registro](mysql-final-guard.txt) | divas_cuyo_test vacía |
| Vistas/CSS y build frente a T057 | [Hashes](visual-provenance-check.json) | Sin cambios; evidencia visual vigente |

Las invocaciones Artisan usaron explícitamente `APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: DB_URL='' CACHE_STORE=array SESSION_DRIVER=array QUEUE_CONNECTION=sync`. MySQL usó sólo `PUBLIC_PROFILE_MYSQL_TEST_*`, con guardas antes de conectar/migrar. No se ejecutaron simultáneamente las suites que comparten Storage fake. No hubo instalación de dependencias ni ejecución de T058.

El ajuste de formato importa `Illuminate\Contracts\Support\Htmlable` en ViewModelProfile y mantiene el mismo tipo de retorno; no cambia comportamiento. Se conserva el resultado inicial fallido para no ocultarlo. Extensión `.txt` para que la regla global que ignora `*.log` no excluya la evidencia de futuros cambios versionados.
