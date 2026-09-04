<?php
namespace App\Core;

final class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    public static function back(Request $request, string $fallback = '/'): never
    {
        self::redirect($request->referer($fallback));
    }

    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function notFound(string $message = 'Page not found'): never
    {
        http_response_code(404);
        View::render('errors/404', ['message' => $message], 'layouts/bare');
        exit;
    }

    /**
     * The path exists but not under this verb. Says so with a 405 and the Allow
     * header the spec requires, rather than pretending the path is missing.
     */
    public static function methodNotAllowed(string $allowed): never
    {
        http_response_code(405);
        header('Allow: ' . $allowed);
        View::render('errors/404', ['message' => 'That action uses a different request method.'], 'layouts/bare');
        exit;
    }

    public static function forbidden(string $message = 'You do not have access to this content'): never
    {
        http_response_code(403);
        View::render('errors/403', ['message' => $message], 'layouts/bare');
        exit;
    }
}
