<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\View;
use App\Models\Event;
use App\Models\Friendship;
use App\Models\Group;
use App\Models\Post;
use App\Models\SavedPost;
use App\Models\Story;

final class FeedController extends Controller
{
    private const PAGE_SIZE = 8;

    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $id   = (int) $user['id'];

        Story::prune();

        $this->view('feed/index', [
            'title'       => 'FaceClone',
            'posts'       => Post::feed($id, self::PAGE_SIZE),
            'trays'       => Story::trays($id),
            'suggestions' => Friendship::suggestions($id, 5),
            'requests'    => Friendship::pendingRequests($id, 3),
            'contacts'    => Friendship::friends($id, 20),
            'myGroups'    => Group::forUser($id, 6),
            'events'      => Event::upcoming($id, 3),
            'nextOffset'  => self::PAGE_SIZE,
        ]);
    }

    /** Infinite scroll: returns the next page of rendered post cards. */
    public function more(Request $request): void
    {
        $user   = $this->auth($request);
        $offset = max(0, $request->int('offset'));
        $posts  = Post::feed((int) $user['id'], self::PAGE_SIZE, $offset);

        $html = '';
        foreach ($posts as $post) {
            $html .= View::partial('partials/post-card', ['post' => $post, '__user' => $user]);
        }

        $this->json([
            'ok'         => true,
            'html'       => $html,
            'count'      => count($posts),
            'nextOffset' => $offset + count($posts),
            'hasMore'    => count($posts) === self::PAGE_SIZE,
        ]);
    }

    public function watch(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('feed/watch', [
            'title' => 'Watch',
            'posts' => Post::videoFeed((int) $user['id'], 20),
        ]);
    }

    public function saved(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('feed/saved', [
            'title' => 'Saved',
            'posts' => Post::saved((int) $user['id'], 30),
            'count' => SavedPost::count((int) $user['id']),
        ]);
    }

    /** Posts from this day in previous years. */
    public function memories(Request $request): void
    {
        $user  = $this->auth($request);
        $rows  = \App\Core\Database::instance()->all(
            'SELECT p.id, p.user_id, p.group_id, p.shared_post_id, p.content, p.background, p.feeling,
                    p.location, p.privacy, p.created_at, p.edited_at
             FROM posts p
             WHERE p.user_id = ?
               AND DATE_FORMAT(p.created_at, "%m-%d") = DATE_FORMAT(CURDATE(), "%m-%d")
               AND YEAR(p.created_at) < YEAR(CURDATE())
             ORDER BY p.created_at DESC',
            [(int) $user['id']]
        );

        $this->view('feed/memories', [
            'title' => 'Memories',
            'posts' => Post::hydrate($rows, (int) $user['id']),
        ]);
    }
}
