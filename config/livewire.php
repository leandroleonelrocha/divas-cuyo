<?php

return [
    'temporary_file_upload' => [
        // The import page validates ZIP type and its own 100 MB limit.
        'rules' => ['required', 'file', 'max:102400'],
    ],
];
