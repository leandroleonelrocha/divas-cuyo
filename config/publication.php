<?php

return [
    'expiry_warning_days' => (int) env('PUBLICATION_EXPIRY_WARNING_DAYS', 7),
    'monthly_prices_ars' => [
        'encounters' => 50000,
        'virtual' => 30000,
    ],
];
