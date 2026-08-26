<?php
namespace App\Core;

final class Request
{
    private array $query;
    private array $body;
    private array $files;
    private string $method;
    private string $path;
    private array $routeParams = [];

    public function __construct()
    {
        $this->query  = $_GET;
        $this->files  = $_FILES;
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path   = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '', '/');

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $raw        = file_get_contents('php://input') ?: '';
            $decoded    = json_decode($raw, true);
            $this->body = is_array($decoded) ? $decoded : [];
        } else {
            $this->body = $_POST;
        }

        // Allow forms to emulate PUT/PATCH/DELETE via a _method field.
        if ($this->method === 'POST' && isset($this->body['_method'])) {
            $override = strtoupper((string) $this->body['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $this->method = $override;
            }
        }
    }

    public function method(): string { return $this->method; }
    public function path(): string   { return $this->path; }

    public function setRouteParams(array $params): void { $this->routeParams = $params; }
    public function param(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }
    public function intParam(string $key): int { return (int) $this->param($key, 0); }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function raw(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) ($this->body[$key] ?? $this->query[$key] ?? $default);
    }

    public function bool(string $key): bool
    {
        return filter_var($this->body[$key] ?? false, FILTER_VALIDATE_BOOL);
    }

    public function all(): array { return $this->body; }

    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $file;
    }

    /** Normalise a multi-file input into a list of single-file arrays. */
    public function fileList(string $key): array
    {
        $file = $this->files[$key] ?? null;
        if (!is_array($file) || !isset($file['name'])) {
            return [];
        }
        if (!is_array($file['name'])) {
            return ($file['error'] === UPLOAD_ERR_NO_FILE) ? [] : [$file];
        }
        $out = [];
        foreach (array_keys($file['name']) as $i) {
            if ($file['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name'     => $file['name'][$i],
                'type'     => $file['type'][$i],
                'tmp_name' => $file['tmp_name'][$i],
                'error'    => $file['error'][$i],
                'size'     => $file['size'][$i],
            ];
        }
        return $out;
    }

    public function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    public function referer(string $fallback = '/'): string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        // Only honour same-origin referers so this cannot be used as an open redirect.
        if ($ref !== '' && parse_url($ref, PHP_URL_HOST) === ($_SERVER['HTTP_HOST'] ?? null)) {
            return $ref;
        }
        return $fallback;
    }

    public function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; }
}
