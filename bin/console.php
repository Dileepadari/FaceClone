<?php
/**
 * FaceClone management CLI.
 *
 *   php bin/console.php install [--seed] [--force]   load schema, optionally seed demo data
 *   php bin/console.php seed [--force]               load demo data only
 *   php bin/console.php doctor                       check the environment and connection
 *   php bin/console.php prune-stories                delete expired stories
 *   php bin/console.php make-user <email> <password> "First Last"
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("This script must be run from the command line.\n");
}

$root = dirname(__DIR__);
require $root . '/app/Core/Autoloader.php';
App\Core\Autoloader::register($root . '/app');
require $root . '/app/Core/helpers.php';

use App\Core\App;
use App\Core\Database;

$argvv   = $argv;
$command = $argvv[1] ?? 'help';
$flags   = array_values(array_filter(array_slice($argvv, 2), static fn($a) => str_starts_with($a, '--')));
$args    = array_values(array_filter(array_slice($argvv, 2), static fn($a) => !str_starts_with($a, '--')));
$has     = static fn(string $flag) => in_array('--' . $flag, $flags, true);

function out(string $line = ''): void  { fwrite(STDOUT, $line . PHP_EOL); }
function ok(string $line): void        { out("\033[32m✓\033[0m " . $line); }
function warn(string $line): void      { out("\033[33m!\033[0m " . $line); }
function bad(string $line): void       { fwrite(STDERR, "\033[31m✗\033[0m " . $line . PHP_EOL); }

// doctor runs before boot so it can report a failed connection usefully.
if ($command === 'doctor') {
    out('FaceClone environment check');
    out('---------------------------');
    out('PHP version      : ' . PHP_VERSION . (version_compare(PHP_VERSION, '8.1', '>=') ? '  ok' : '  needs 8.1+'));
    foreach (['pdo_mysql', 'gd', 'mbstring', 'fileinfo', 'json'] as $ext) {
        extension_loaded($ext) ? ok("extension $ext") : bad("extension $ext is missing");
    }
    foreach (['public/uploads', 'storage/logs'] as $dir) {
        is_writable($root . '/' . $dir) ? ok("$dir is writable") : bad("$dir is not writable");
    }
    try {
        App::boot($root);
        $config = App::config('db');
        ok("connected to {$config['database']} on {$config['host']} as {$config['username']}");
        $tables = Database::instance()->column('SHOW TABLES');
        out('Tables           : ' . (count($tables) ?: 'none - run "php bin/console.php install"'));
    } catch (Throwable $e) {
        bad('database: ' . $e->getMessage());
        exit(1);
    }
    exit(0);
}

try {
    App::boot($root);
} catch (Throwable $e) {
    bad($e->getMessage());
    out('Run "php bin/console.php doctor" for details.');
    exit(1);
}

$db = Database::instance();

switch ($command) {
    case 'install':
        $existing = $db->column('SHOW TABLES');
        if ($existing && !$has('force')) {
            warn(count($existing) . ' tables already exist. Re-run with --force to drop and recreate them.');
            exit(1);
        }

        out('Loading schema…');
        $sql = file_get_contents($root . '/database/schema.sql');
        $db->pdo()->exec($sql);
        ok('Schema loaded (' . count($db->column('SHOW TABLES')) . ' tables).');

        if ($has('seed')) {
            require $root . '/database/seed.php';
            seed_database($db, true);
        }
        out('');
        ok('Ready. Start the app with:  php -S localhost:8000 -t public server.php');
        out('(the server.php argument is required - see DEVDOC.md gotchas)');
        break;

    case 'seed':
        require $root . '/database/seed.php';
        seed_database($db, $has('force'));
        break;

    case 'prune-stories':
        $n = \App\Models\Story::prune();
        ok("Removed $n expired stories.");
        break;

    case 'make-user':
        [$email, $password, $name] = [$args[0] ?? null, $args[1] ?? null, $args[2] ?? null];
        if (!$email || !$password || !$name) {
            bad('Usage: php bin/console.php make-user <email> <password> "First Last"');
            exit(1);
        }
        $parts = preg_split('/\s+/', trim($name), 2);
        $id = \App\Models\User::create([
            'first_name' => $parts[0],
            'last_name'  => $parts[1] ?? '',
            'username'   => \App\Models\User::suggestUsername($parts[0], $parts[1] ?? ''),
            'email'      => strtolower($email),
            'password'   => $password,
            'dob'        => '1995-01-01',
            'gender'     => 'custom',
        ]);
        ok("Created user #$id ($email).");
        break;

    default:
        out('FaceClone CLI');
        out('');
        out('  php bin/console.php doctor                     check PHP extensions, permissions, database');
        out('  php bin/console.php install [--seed] [--force] create the schema (and demo data)');
        out('  php bin/console.php seed [--force]             load demo data into an existing schema');
        out('  php bin/console.php prune-stories              delete stories older than 24 hours');
        out('  php bin/console.php make-user <email> <pass> "First Last"');
        break;
}
