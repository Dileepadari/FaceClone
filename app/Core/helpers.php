<?php
/**
 * Global view helpers. Kept as plain functions so templates stay readable.
 */

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

/** Escape for HTML output. Every dynamic value in a view goes through this. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string   { return App::url($path); }
function asset(string $path): string      { return App::asset($path); }
function csrf_field(): string             { return Csrf::field(); }
function csrf_token(): string             { return Csrf::token(); }
function current_user(): ?array           { return Auth::user(); }
function auth_id(): ?int                  { return Auth::id(); }

function view_partial(string $view, array $data = []): string
{
    return View::partial($view, $data);
}

/** Full display name for a user row. */
function full_name(?array $user): string
{
    if (!$user) {
        return 'Deleted user';
    }
    return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'FaceClone user';
}

/** Avatar URL, falling back to a generated initials image. */
function avatar_url(?array $user): string
{
    if (!empty($user['avatar'])) {
        return $user['avatar'];
    }
    $seed = urlencode(full_name($user));
    return '/avatar/' . ($user['id'] ?? 0) . '?n=' . $seed;
}

function cover_url(?array $user): string
{
    return !empty($user['cover']) ? $user['cover'] : '';
}

function profile_url(?array $user): string
{
    if (empty($user)) {
        return '#';
    }
    return '/u/' . rawurlencode((string) ($user['username'] ?? $user['id']));
}

/** Facebook-style relative timestamp: 5m, 3h, Mar 4, Mar 4 2023. */
function time_ago(?string $datetime): string
{
    if (!$datetime) {
        return '';
    }
    $ts   = strtotime($datetime);
    $diff = time() - $ts;

    if ($diff < 5)      return 'Just now';
    if ($diff < 60)     return $diff . 's';
    if ($diff < 3600)   return floor($diff / 60) . 'm';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'd';
    if (date('Y', $ts) === date('Y')) {
        return date('M j', $ts);
    }
    return date('M j, Y', $ts);
}

function full_datetime(?string $datetime): string
{
    return $datetime ? date('l, F j, Y \a\t g:i A', strtotime($datetime)) : '';
}

/** 1200 -> 1.2K, 3400000 -> 3.4M */
function number_short(int $n): string
{
    if ($n < 1000)      return (string) $n;
    if ($n < 1000000)   return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
}

/** Escape post text, then turn URLs, #tags and @mentions into links. */
function rich_text(?string $text): string
{
    $html = nl2br(e($text));
    $html = preg_replace(
        '#(https?://[^\s<]+)#i',
        '<a href="$1" target="_blank" rel="noopener noreferrer nofollow">$1</a>',
        $html
    );
    $html = preg_replace('/(^|\s)#([a-zA-Z0-9_]{2,40})/', '$1<a href="/search?q=%23$2">#$2</a>', $html);
    $html = preg_replace('/(^|\s)@([a-zA-Z0-9._]{3,40})/', '$1<a href="/u/$2">@$2</a>', $html);
    return $html;
}

/** Text-post gradient backgrounds, keyed by the value stored in posts.background. */
function post_backgrounds(): array
{
    return [
        'bg1' => 'linear-gradient(135deg,#7b4dff 0%,#e356a7 100%)',
        'bg2' => 'linear-gradient(135deg,#1877f2 0%,#42b7ff 100%)',
        'bg3' => 'linear-gradient(135deg,#f5515f 0%,#9f041b 100%)',
        'bg4' => 'linear-gradient(135deg,#11998e 0%,#38ef7d 100%)',
        'bg5' => 'linear-gradient(135deg,#fc4a1a 0%,#f7b733 100%)',
        'bg6' => 'linear-gradient(135deg,#232526 0%,#414345 100%)',
        'bg7' => 'linear-gradient(135deg,#8e2de2 0%,#4a00e0 100%)',
        'bg8' => 'linear-gradient(135deg,#0f2027 0%,#2c5364 100%)',
    ];
}

function background_style(?string $key): string
{
    return post_backgrounds()[$key] ?? '';
}

/** The six Facebook reactions plus 'like', with emoji and label. */
function reaction_types(): array
{
    return [
        'like'  => ['emoji' => '👍', 'label' => 'Like',  'color' => '#1877f2'],
        'love'  => ['emoji' => '❤️', 'label' => 'Love',  'color' => '#f33e58'],
        'care'  => ['emoji' => '🥰', 'label' => 'Care',  'color' => '#f7b125'],
        'haha'  => ['emoji' => '😆', 'label' => 'Haha',  'color' => '#f7b125'],
        'wow'   => ['emoji' => '😮', 'label' => 'Wow',   'color' => '#f7b125'],
        'sad'   => ['emoji' => '😢', 'label' => 'Sad',   'color' => '#f7b125'],
        'angry' => ['emoji' => '😡', 'label' => 'Angry', 'color' => '#e9710f'],
    ];
}

function privacy_icon(string $privacy): string
{
    return match ($privacy) {
        'public'   => 'globe',
        'friends'  => 'friends',
        'only_me'  => 'lock',
        default    => 'globe',
    };
}

function privacy_label(string $privacy): string
{
    return match ($privacy) {
        'public'  => 'Public',
        'friends' => 'Friends',
        'only_me' => 'Only me',
        default   => 'Public',
    };
}

/** Inline SVG icon set. Returns markup for the named icon. */
function icon(string $name, int $size = 20, string $class = ''): string
{
    $paths = require App::basePath('app/Core/icons.php');
    $body  = $paths[$name] ?? $paths['dot'];
    $cls   = $class !== '' ? ' class="' . e($class) . '"' : '';
    return sprintf(
        '<svg%s width="%d" height="%d" viewBox="0 0 24 24" aria-hidden="true" focusable="false">%s</svg>',
        $cls,
        $size,
        $size,
        $body
    );
}

/** Mark a nav item active when the current path matches. */
function nav_active(string $prefix): string
{
    $path = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');
    if ($prefix === '/') {
        return $path === '/' ? ' is-active' : '';
    }
    return str_starts_with($path, $prefix) ? ' is-active' : '';
}

/** Repopulate a field after a failed form submit. */
function old(string $key, mixed $default = ''): string
{
    global $__old;
    return e((string) ($__old[$key] ?? $default));
}

function field_error(string $key): string
{
    global $__errors;
    $message = $__errors[$key] ?? null;
    return $message ? '<p class="field-error">' . e($message) . '</p>' : '';
}

function has_error(string $key): bool
{
    global $__errors;
    return isset($__errors[$key]);
}
