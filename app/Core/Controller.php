<?php
namespace App\Core;

/**
 * Base controller. Handles the guard helpers, view rendering with the shared
 * chrome data (unread counters, current user) and JSON replies.
 */
abstract class Controller
{
    protected ?array $user = null;

    /** Require an authenticated user for this action. */
    protected function auth(Request $request): array
    {
        return $this->user = Auth::requireLogin($request);
    }

    protected function guest(): void
    {
        Auth::requireGuest();
    }

    protected function csrf(Request $request): void
    {
        Csrf::verify($request);
    }

    /** Render a page inside the signed-in chrome. */
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        $GLOBALS['__old']    = Session::oldInput();
        $GLOBALS['__errors'] = Session::errors();

        $data['__user']    = $this->user ?? Auth::user();
        $data['__flashes'] = Session::takeFlashes();

        if ($data['__user']) {
            $data['__chrome'] = Chrome::forUser((int) $data['__user']['id']);
        }

        View::render($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function ok(array $extra = []): never
    {
        Response::json(['ok' => true] + $extra);
    }

    protected function fail(string $message, int $status = 422): never
    {
        Response::json(['ok' => false, 'error' => $message], $status);
    }

    protected function redirect(string $url): never
    {
        Response::redirect($url);
    }

    protected function back(Request $request, string $fallback = '/'): never
    {
        Response::back($request, $fallback);
    }

    /** Flash an error plus the submitted values, then bounce back to the form. */
    protected function backWithErrors(Request $request, Validator $validator, string $fallback = '/'): never
    {
        Session::flashInput($request->all(), $validator->errors());
        Session::flash('error', $validator->firstError());
        $this->back($request, $fallback);
    }
}
