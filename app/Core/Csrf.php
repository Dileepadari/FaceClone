<?php
namespace App\Core;

final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="csrf" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function check(Request $request): bool
    {
        if (in_array($request->method(), ['GET', 'HEAD'], true)) {
            return true;
        }
        $sent = (string) ($request->raw('csrf') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        return $sent !== '' && hash_equals(self::token(), $sent);
    }

    /** Abort the request when the token is missing or wrong. */
    public static function verify(Request $request): void
    {
        if (self::check($request)) {
            return;
        }
        if ($request->isAjax()) {
            Response::json(['ok' => false, 'error' => 'Your session expired. Refresh the page and try again.'], 419);
        }
        http_response_code(419);
        Session::flash('error', 'Your session expired. Please try again.');
        Response::back($request);
    }
}
