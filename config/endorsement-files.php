<?php

return [
    'disk' => env('ENDORSEMENT_FILES_DISK', 'local'),
    'directory' => env('ENDORSEMENT_FILES_DIRECTORY', 'endorsement-files'),
    'max_upload_mb' => (int) env('ENDORSEMENT_FILE_MAX_MB', 2048),
    'max_files_per_request' => (int) env('ENDORSEMENT_FILE_MAX_COUNT', 50),
];
