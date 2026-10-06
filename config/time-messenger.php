<?php

return [
    'token'    => env('TIME_TOKEN'),
    'base_url' => env('TIME_BASE_URL'),

    /*
     * Default user for reactions when the reaction data does not specify one.
     */
    'user_id' => env('TIME_USER_ID'),

    /*
     * Timeout in seconds for downloading a file. Kept apart from the 30-second limit of JSON
     * calls: see "File download timeout" in the README.
     */
    'file_download_timeout' => (int) env('TIME_FILE_DOWNLOAD_TIMEOUT', 90),
];
