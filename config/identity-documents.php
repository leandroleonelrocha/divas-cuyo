<?php

return [
    'disk' => env('IDENTITY_DOCUMENT_DISK', 'identity_private'),
    'max_size_kb' => 5120,
    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
    ],
    'required_types' => [
        'dni_front',
        'dni_back',
        'selfie',
    ],
];
