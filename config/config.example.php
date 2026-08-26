<?php
/**
 * Copy this file to config/config.local.php and edit it.
 * config.local.php is git-ignored so credentials never get committed.
 */
return [
    'app' => [
        'name'     => 'FaceClone',
        'url'      => 'http://localhost:8000',
        'debug'    => true,
        'timezone' => 'UTC',
    ],
    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'database' => 'faceclone',
        'username' => 'faceclone',
        'password' => 'faceclone',
        'charset'  => 'utf8mb4',
    ],
    'uploads' => [
        'max_bytes'  => 8 * 1024 * 1024,
        'image_mime' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        'video_mime' => ['video/mp4', 'video/webm', 'video/quicktime'],
    ],
];
