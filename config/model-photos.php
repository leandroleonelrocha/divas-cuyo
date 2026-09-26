<?php

return [
    'disk' => env('MODEL_PHOTOS_DISK', 'model_photos'),
    'max_photos' => 5,
    'max_size_kb' => 10240,
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],
    'allowed_extensions' => [
        'jpg',
        'jpeg',
        'png',
        'webp',
    ],
    // Las fotos se redimensionan automáticamente; no se exige una resolución mínima.
    'min_width' => 1,
    'min_height' => 1,
    'max_width' => 10000,
    'max_height' => 10000,
    'processed_max_side' => 2000,
    'thumbnail' => [
        'width' => 400,
        'height' => 400,
    ],
    'watermark' => [
        'enabled' => true,
        'asset' => 'public/images/watermark-divas-cuyo.png',
        'position' => 'bottom-right',
        'opacity' => 38,
        'relative_width' => 0.20,
        'margin' => 24,
    ],
    'path_prefix' => 'model-profiles',
];
