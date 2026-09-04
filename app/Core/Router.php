<?php
namespace App\Core;

/**
 * Pattern router. Routes are declared as '/posts/{id}/comments' where {name}
 * captures one path segment.
 */
final class Router
{
    /** @var array<string, array<int, array{regex:string, keys:array, handler:mixed}>> */
    private array $routes = [];

    public function get(string $path, mixed $handler): void    { $this->add('GET', $path, $handler); }
    public function post(string $path, mixed $handler): void   { $this->add('POST', $path, $handler); }
    public function put(string $path, mixed $handler): void    { $this->add('PUT', $path, $handler); }
    public function delete(string $path, mixed $handler): void { $this->add('DELETE', $path, $handler); }

    /** Register the same handler for GET and POST. */
    public function form(string $path, mixed $handler): void
    {
        $this->add('GET', $path, $handler);
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, mixed $handler): void
    {
        $keys  = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', static function ($m) use (&$keys) {
            $keys[] = $m[1];
            return '([^/]+)';
        }, rtrim($path, '/') ?: '/');

        $this->routes[$method][] = [
            'regex'   => '#^' . $regex . '$#',
            'keys'    => $keys,
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): void
    {
        $path   = rtrim($request->path(), '/') ?: '/';
        $method = $request->method();

        // HEAD must work wherever GET does (RFC 9110). Nothing registers HEAD
        // routes, so without this every HEAD request fell through to the
        // "wrong verb" branch and answered 404, which breaks health checks,
        // link checkers and anything that probes before fetching.
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            array_shift($matches);
            $params = [];
            foreach ($route['keys'] as $i => $key) {
                $params[$key] = urldecode($matches[$i] ?? '');
            }
            $request->setRouteParams($params);
            $this->invoke($route['handler'], $request);
            return;
        }

        // Path exists under another verb - report that rather than a bare 404.
        foreach ($this->routes as $otherMethod => $routes) {
            if ($otherMethod === $method) {
                continue;
            }
            foreach ($routes as $route) {
                if (preg_match($route['regex'], $path)) {
                    // Response::notFound() sets 404 unconditionally, so setting
                    // 405 here and then calling it shipped a 404 with an Allow
                    // header: the opposite of what the comment above promises.
                    Response::methodNotAllowed($otherMethod);
                }
            }
        }

        Response::notFound();
    }

    private function invoke(mixed $handler, Request $request): void
    {
        if (is_callable($handler)) {
            $handler($request);
            return;
        }
        [$class, $action] = $handler;
        $controller = new $class();
        $controller->$action($request);
    }
}
