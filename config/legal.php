<?php

return [
    'version' => env('LEGAL_DISCLAIMER_VERSION', '1'),
    'days' => (int) env('LEGAL_ACCEPTANCE_DAYS', 30),
    'cookie' => 'divas_legal_access',
    // Production must use HTTPS. Local HTTP development can explicitly disable this.
    'secure' => (bool) env('LEGAL_COOKIE_SECURE', true),
];
