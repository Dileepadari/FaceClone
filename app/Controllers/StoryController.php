<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Models\Friendship;
use App\Models\Story;
use App\Models\User;

final class StoryController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        Story::prune();

        $this->view('stories/index', [
            'title' => 'Stories',
            'trays' => Story::trays((int) $user['id']),
        ]);
    }

    public function create(Request $request): void
    {
        $this->auth($request);
        $this->view('stories/create', ['title' => 'Create a story']);
    }

    public function store(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $type = (string) $request->input('type', 'photo');

        if ($type === 'text') {
            $text = (string) $request->input('text', '');
            if (trim($text) === '') {
                Session::flash('error', 'Write something for your story.');
                $this->back($request, '/stories/create');
            }
            Story::create((int) $user['id'], [
                'type'       => 'text',
                'text'       => mb_substr($text, 0, 500),
                'background' => (string) $request->input('background', 'bg1'),
            ]);
        } else {
            $file = $request->file('media');
            if (!$file) {
                Session::flash('error', 'Choose a photo for your story.');
                $this->back($request, '/stories/create');
            }
            $path = Upload::image($file, Upload::STORY);
            if (!$path) {
                Session::flash('error', Upload::lastError() ?: 'That photo could not be uploaded.');
                $this->back($request, '/stories/create');
            }
            Story::create((int) $user['id'], ['type' => 'photo', 'media' => $path]);
        }

        Session::flash('success', 'Your story is live for the next 24 hours.');
        $this->redirect('/stories');
    }

    /** Full-screen story viewer for one person's stories. */
    public function viewer(Request $request): void
    {
        $user     = $this->auth($request);
        $authorId = $request->intParam('id');
        $author   = User::find($authorId);

        if (!$author) {
            Response::notFound('That person is not on FaceClone.');
        }
        if ($authorId !== (int) $user['id'] && !Friendship::areFriends($authorId, (int) $user['id'])) {
            Response::forbidden('Only friends can see this story.');
        }

        $stories = Story::forUser($authorId, (int) $user['id']);
        if ($stories === []) {
            Session::flash('error', 'That story has expired.');
            $this->redirect('/stories');
        }

        // The tray order drives the next/previous arrows in the viewer.
        $trays = Story::trays((int) $user['id']);
        $order = array_map(static fn(array $t) => (int) $t['user']['id'], $trays);
        $index = array_search($authorId, $order, true);

        $this->view('stories/viewer', [
            'title'    => full_name($author) . "'s story",
            'author'   => $author,
            'stories'  => $stories,
            'isOwner'  => $authorId === (int) $user['id'],
            'prevId'   => $index !== false && $index > 0 ? $order[$index - 1] : null,
            'nextId'   => $index !== false && isset($order[$index + 1]) ? $order[$index + 1] : null,
        ], 'layouts/immersive');
    }

    public function seen(Request $request): void
    {
        $user  = $this->auth($request);
        $this->csrf($request);

        $story = Story::find($request->intParam('id'));
        if (!$story) {
            $this->fail('That story has expired.', 404);
        }

        Story::markSeen((int) $story['id'], (int) $user['id']);
        $this->ok(['views' => Story::viewCount((int) $story['id'])]);
    }

    public function viewers(Request $request): void
    {
        $user  = $this->auth($request);
        $story = Story::find($request->intParam('id'));

        if (!$story || (int) $story['user_id'] !== (int) $user['id']) {
            $this->fail('You can only see viewers of your own story.', 403);
        }

        $viewers = Story::viewers((int) $story['id']);
        $this->ok([
            'count'   => count($viewers),
            'viewers' => array_map(static fn(array $v) => [
                'name'   => full_name($v),
                'avatar' => avatar_url($v),
                'url'    => profile_url($v),
                'at'     => time_ago($v['viewed_at']),
            ], $viewers),
        ]);
    }

    public function destroy(Request $request): void
    {
        $user  = $this->auth($request);
        $this->csrf($request);

        $story = Story::find($request->intParam('id'));
        if (!$story || (int) $story['user_id'] !== (int) $user['id']) {
            Response::forbidden('You can only delete your own story.');
        }

        Story::delete((int) $story['id']);
        Session::flash('success', 'Story deleted.');
        $this->redirect('/stories');
    }
}
