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

/*
 * Security headers, sent on every response before anything writes a body.
 *
 * A social site is a clickjacking target in a way a brochure page is not: the
 * whole surface is one-click actions (react, follow, accept, delete) behind an
 * already-authenticated session, which is exactly what a transparent iframe over
 * a decoy page is for. Nothing here embeds FaceClone anywhere, so DENY.
 *
 * A Content-Security-Policy is the obvious next one and is deliberately not here
 * yet: the views use inline styles and inline handlers in enough places that
 * adding one blind would break pages rather than protect them. See not_for_you.md.
 */
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

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
