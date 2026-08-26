<?php
namespace App\Core;

use App\Models\User;

final class Auth
{
    private static ?array $user = null;
    private const COOKIE = 'faceclone_remember';

    public static function attempt(string $email, string $password, bool $remember = false): bool
    {
        $user = User::findByEmailOrUsername($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        if (!$user['is_active']) {
            User::reactivate((int) $user['id']);
        }

        // Upgrade legacy hashes transparently on successful login.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        self::login((int) $user['id'], $remember);
        return true;
    }

    public static function login(int $userId, bool $remember = false): void
    {
        Session::regenerate();
        Session::set('user_id', $userId);
        self::$user = null;
        User::touchLastSeen($userId);

        if ($remember) {
            self::issueRememberToken($userId);
        }
    }

    public static function logout(): void
    {
        $token = $_COOKIE[self::COOKIE] ?? null;
        if ($token && str_contains($token, ':')) {
            [$selector] = explode(':', $token, 2);
            Database::instance()->execute('DELETE FROM remember_tokens WHERE selector = ?', [$selector]);
        }
        setcookie(self::COOKIE, '', time() - 3600, '/');
        Session::destroy();
        self::$user = null;
    }

    /** Resolve the signed-in user, falling back to a remember-me cookie. */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = Session::get('user_id');
        if (!$id) {
            $id = self::resolveRememberToken();
            if ($id) {
                Session::set('user_id', $id);
            }
        }
        if (!$id) {
            return null;
        }
        $user = User::find((int) $id);
        if (!$user) {
            Session::forget('user_id');
            return null;
        }
        return self::$user = $user;
    }

    public static function id(): ?int
    {
        $user = self::user();
        return $user ? (int) $user['id'] : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /** Refresh the cached user row after a profile update. */
    public static function forgetCache(): void
    {
        self::$user = null;
    }

    private static function issueRememberToken(int $userId): void
    {
        $selector  = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $expires   = date('Y-m-d H:i:s', strtotime('+30 days'));

        Database::instance()->execute(
            'INSERT INTO remember_tokens (user_id, selector, validator, expires_at) VALUES (?, ?, ?, ?)',
            [$userId, $selector, hash('sha256', $validator), $expires]
        );
        setcookie(self::COOKIE, "$selector:$validator", [
            'expires'  => strtotime('+30 days'),
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function resolveRememberToken(): ?int
    {
        $cookie = $_COOKIE[self::COOKIE] ?? '';
        if (!str_contains($cookie, ':')) {
            return null;
        }
        [$selector, $validator] = explode(':', $cookie, 2);
        $row = Database::instance()->first(
            'SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1',
            [$selector]
        );
        if (!$row || !hash_equals($row['validator'], hash('sha256', $validator))) {
            return null;
        }
        return (int) $row['user_id'];
    }

    /** Redirect guests to the login page, remembering where they wanted to go. */
    public static function requireLogin(Request $request): array
    {
        $user = self::user();
        if ($user) {
            return $user;
        }
        if ($request->isAjax()) {
            Response::json(['ok' => false, 'error' => 'Please sign in to continue.'], 401);
        }
        Session::set('intended', $request->path());
        Response::redirect('/login');
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            Response::redirect('/');
        }
    }
}
