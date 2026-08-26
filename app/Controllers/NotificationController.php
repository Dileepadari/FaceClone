<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Notification;

final class NotificationController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('notifications/index', [
            'title'         => 'Notifications',
            'notifications' => Notification::forUser((int) $user['id'], 60),
        ]);
    }

    /** Feeds the bell dropdown in the top bar. */
    public function dropdown(Request $request): void
    {
        $user = $this->auth($request);
        $this->ok([
            'html'  => View::partial('partials/notification-dropdown', [
                'notifications' => Notification::forUser((int) $user['id'], 10),
                '__user'        => $user,
            ]),
            'count' => Notification::unreadCount((int) $user['id']),
        ]);
    }

    public function readAll(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        Notification::markAllRead((int) $user['id']);

        if ($request->isAjax()) {
            $this->ok(['count' => 0]);
        }
        $this->back($request, '/notifications');
    }

    /** Mark one notification read, then forward to whatever it points at. */
    public function open(Request $request): void
    {
        $user         = $this->auth($request);
        $notification = Notification::find($request->intParam('id'), (int) $user['id']);

        if (!$notification) {
            Response::notFound('That notification no longer exists.');
        }

        Notification::markRead((int) $notification['id'], (int) $user['id']);

        $url = $notification['url'] ?? '';
        $this->redirect($url !== '' && str_starts_with($url, '/') ? $url : '/notifications');
    }

    public function destroy(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        Notification::delete($request->intParam('id'), (int) $user['id']);

        if ($request->isAjax()) {
            $this->ok(['count' => Notification::unreadCount((int) $user['id'])]);
        }
        $this->back($request, '/notifications');
    }
}
