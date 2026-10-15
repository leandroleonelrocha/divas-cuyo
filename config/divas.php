<?php

return [
    /*
    | Activa la importación de perfiles reales en DatabaseSeeder.
    | Definir SEED_DIVAS_CUYO=true en el entorno (ej. Laravel Cloud).
    */
    'seed_real_profiles' => filter_var(env('SEED_DIVAS_CUYO', false), FILTER_VALIDATE_BOOLEAN),
];
