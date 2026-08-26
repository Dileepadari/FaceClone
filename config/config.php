<?php
/**
 * Effective configuration: defaults from config.example.php, overridden by
 * config.local.php when present, then by FACECLONE_* environment variables.
 */
$config = require __DIR__ . '/config.example.php';

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) {
        foreach ($override as $section => $values) {
            $config[$section] = array_merge($config[$section] ?? [], (array) $values);
        }
    }
}

$env = [
    'FACECLONE_DB_HOST'     => ['db', 'host'],
    'FACECLONE_DB_PORT'     => ['db', 'port'],
    'FACECLONE_DB_NAME'     => ['db', 'database'],
    'FACECLONE_DB_USER'     => ['db', 'username'],
    'FACECLONE_DB_PASSWORD' => ['db', 'password'],
    'FACECLONE_URL'         => ['app', 'url'],
    'FACECLONE_DEBUG'       => ['app', 'debug'],
];
foreach ($env as $key => [$section, $field]) {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        $config[$section][$field] = $field === 'debug' ? filter_var($value, FILTER_VALIDATE_BOOL) : $value;
    }
}

return $config;
