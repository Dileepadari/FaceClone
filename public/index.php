<?php
/**
 * FaceClone front controller. Every request enters here.
 */
declare(strict_types=1);

$root = dirname(__DIR__);

require $root . '/app/Core/Autoloader.php';
App\Core\Autoloader::register($root . '/app');
require $root . '/app/Core/helpers.php';

use App\Core\App;
use App\Core\Auth;
use App\Core\Request;
use App\Core\Router;
use App\Core\Session;
use App\Core\View;

try {
    App::boot($root);
} catch (Throwable $e) {
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo View::capture('errors/boot', ['message' => $e->getMessage()], null);
    exit;
}

Session::start();

$request = new Request();

// Keep presence fresh without a query on every single hit.
if (Auth::check() && (Session::get('_presence_at', 0) < time() - 60)) {
    \App\Models\User::touchLastSeen((int) Auth::id());
    Session::set('_presence_at', time());
}

$router = new Router();
require $root . '/app/routes.php';

try {
    $router->dispatch($request);
} catch (Throwable $e) {
    if (App::isDebug()) {
        throw $e;
    }
    error_log('[faceclone] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    View::render('errors/500', [], 'layouts/bare');
}
