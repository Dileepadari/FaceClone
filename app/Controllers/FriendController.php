<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Session;
use App\Models\Block;
use App\Models\Follow;
use App\Models\Friendship;
use App\Models\User;

final class FriendController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $id   = (int) $user['id'];

        $this->view('friends/index', [
            'title'       => 'Friends',
            'section'     => 'home',
            'requests'    => Friendship::pendingRequests($id, 20),
            'suggestions' => Friendship::suggestions($id, 12),
        ]);
    }

    public function requests(Request $request): void
    {
        $user = $this->auth($request);
        $id   = (int) $user['id'];

        $this->view('friends/requests', [
            'title'    => 'Friend requests',
            'section'  => 'requests',
            'requests' => Friendship::pendingRequests($id, 100),
            'sent'     => Friendship::sentRequests($id, 100),
        ]);
    }

    public function suggestions(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('friends/suggestions', [
            'title'       => 'People you may know',
            'section'     => 'suggestions',
            'suggestions' => Friendship::suggestions((int) $user['id'], 40),
        ]);
    }

    public function all(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('friends/all', [
            'title'   => 'All friends',
            'section' => 'all',
            'friends' => Friendship::friends((int) $user['id'], 500),
        ]);
    }

    /** Resolve the target user, or bail out the way the caller expects. */
    private function target(Request $request): array
    {
        $target = User::find($request->intParam('id'));
        if (!$target) {
            if ($request->isAjax()) {
                $this->fail('That person is no longer on FaceClone.', 404);
            }
            Session::flash('error', 'That person is no longer on FaceClone.');
            $this->back($request, '/friends');
        }
        return $target;
    }

    /** Reply with JSON for fetch calls, or bounce back for plain form posts. */
    private function respond(Request $request, array $payload, string $flash = ''): never
    {
        if ($request->isAjax()) {
            $this->ok($payload);
        }
        if ($flash !== '') {
            Session::flash('success', $flash);
        }
        $this->back($request, '/friends');
    }

    public function request(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        if (!Friendship::request((int) $user['id'], (int) $target['id'])) {
            $this->respond($request, ['status' => Friendship::status((int) $user['id'], (int) $target['id'])]);
        }

        Notifier::send((int) $target['id'], (int) $user['id'], 'friend_request', 'user', (int) $user['id'], '/friends/requests');
        $this->respond($request, ['status' => 'sent'], 'Friend request sent to ' . full_name($target) . '.');
    }

    public function accept(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        if (Friendship::accept((int) $user['id'], (int) $target['id'])) {
            Notifier::send((int) $target['id'], (int) $user['id'], 'friend_accepted', 'user', (int) $user['id'], profile_url($user));
        }

        $this->respond($request, ['status' => 'friends'], 'You and ' . full_name($target) . ' are now friends.');
    }

    public function decline(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        Friendship::decline((int) $user['id'], (int) $target['id']);
        $this->respond($request, ['status' => 'none'], 'Request declined.');
    }

    public function cancel(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        Friendship::cancel((int) $user['id'], (int) $target['id']);
        $this->respond($request, ['status' => 'none'], 'Friend request cancelled.');
    }

    public function remove(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        Friendship::unfriend((int) $user['id'], (int) $target['id']);
        $this->respond($request, ['status' => 'none'], full_name($target) . ' was removed from your friends.');
    }

    public function follow(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        $following = Follow::toggle((int) $user['id'], (int) $target['id']);
        $this->respond(
            $request,
            ['following' => $following],
            $following ? 'You are now following ' . full_name($target) . '.' : 'You unfollowed ' . full_name($target) . '.'
        );
    }

    public function block(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        Block::add((int) $user['id'], (int) $target['id']);
        if ($request->isAjax()) {
            $this->ok(['blocked' => true]);
        }
        Session::flash('success', full_name($target) . ' is blocked. They can no longer see your profile or contact you.');
        $this->redirect('/settings/blocking');
    }

    public function unblock(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);
        $target = $this->target($request);

        Block::remove((int) $user['id'], (int) $target['id']);
        Session::flash('success', full_name($target) . ' was unblocked.');
        $this->redirect('/settings/blocking');
    }
}
