<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Comment;
use App\Models\Group;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\SavedPost;

final class PostController extends Controller
{
    private const MAX_MEDIA = 8;

    public function store(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $content  = (string) $request->input('content', '');
        $groupId  = $request->int('group_id') ?: null;
        $files    = $request->fileList('media');
        $privacy  = (string) $request->input('privacy', 'friends');

        if (!in_array($privacy, ['public', 'friends', 'only_me'], true)) {
            $privacy = 'friends';
        }

        if ($groupId) {
            if (!Group::isMember($groupId, (int) $user['id'])) {
                Session::flash('error', 'Join the group before posting in it.');
                $this->back($request, '/groups');
            }
            $privacy = 'public'; // group membership already gates visibility
        }

        if (trim($content) === '' && $files === []) {
            Session::flash('error', 'Write something or add a photo before posting.');
            $this->back($request);
        }

        $postId = Post::create([
            'user_id'        => (int) $user['id'],
            'group_id'       => $groupId,
            'shared_post_id' => null,
            'content'        => $content,
            // A background only applies to a short text-only post, as on Facebook.
            'background'     => ($files === [] && mb_strlen($content) <= 200) ? (string) $request->input('background', '') : null,
            'feeling'        => (string) $request->input('feeling', ''),
            'location'       => (string) $request->input('location', ''),
            'privacy'        => $privacy,
        ]);

        $failed = 0;
        foreach (array_slice($files, 0, self::MAX_MEDIA) as $i => $file) {
            $stored = Upload::media($file, Upload::POST);
            if ($stored) {
                Post::addMedia($postId, $stored['path'], $stored['type'], $i);
            } else {
                $failed++;
            }
        }

        if ($failed > 0) {
            Session::flash('error', $failed . ' attachment(s) could not be uploaded: ' . Upload::lastError());
        } else {
            Session::flash('success', 'Your post is live.');
        }

        if ($groupId) {
            foreach (Group::members($groupId) as $member) {
                Notifier::send((int) $member['id'], (int) $user['id'], 'group_post', 'post', $postId, '/posts/' . $postId);
            }
        }

        $this->back($request, '/');
    }

    public function show(Request $request): void
    {
        $user = $this->auth($request);
        $post = Post::findVisible($request->intParam('id'), (int) $user['id']);

        if (!$post) {
            Response::notFound('This post is not available, or you do not have permission to view it.');
        }

        $this->view('feed/single', [
            'title'    => 'Post by ' . full_name($post['author']),
            'post'     => $post,
            'comments' => Comment::forPost((int) $post['id'], (int) $user['id']),
        ]);
    }

    public function update(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $post = Post::find($request->intParam('id'));
        if (!$post || (int) $post['user_id'] !== (int) $user['id']) {
            Response::forbidden('You can only edit your own posts.');
        }

        $privacy = (string) $request->input('privacy', $post['privacy']);
        Post::update((int) $post['id'], [
            'content'    => (string) $request->input('content', ''),
            'background' => $post['background'],
            'feeling'    => (string) $request->input('feeling', ''),
            'location'   => (string) $request->input('location', ''),
            'privacy'    => in_array($privacy, ['public', 'friends', 'only_me'], true) ? $privacy : 'friends',
        ]);

        Session::flash('success', 'Post updated.');
        $this->back($request, '/posts/' . $post['id']);
    }

    public function destroy(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $post = Post::find($request->intParam('id'));
        if (!$post || !Post::canEdit($post, (int) $user['id'])) {
            Response::forbidden('You cannot delete this post.');
        }

        Post::delete((int) $post['id']);

        if ($request->isAjax()) {
            $this->ok(['deleted' => (int) $post['id']]);
        }
        Session::flash('success', 'Post deleted.');
        $this->back($request, '/');
    }

    public function react(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $postId = $request->intParam('id');
        $post   = Post::find($postId);
        if (!$post || !Post::canView($post, (int) $user['id'])) {
            $this->fail('That post is not available.', 404);
        }

        $result  = Reaction::toggle((int) $user['id'], 'post', $postId, (string) $request->input('type', 'like'));
        $summary = Reaction::summaryFor('post', [$postId], (int) $user['id'])[$postId];

        if ($result['action'] !== 'removed') {
            Notifier::send((int) $post['user_id'], (int) $user['id'], 'reaction_post', 'post', $postId, '/posts/' . $postId);
        }

        $this->ok(['reaction' => $result['type'], 'summary' => $summary]);
    }

    /** The "who reacted" dialog. */
    public function reactions(Request $request): void
    {
        $user   = $this->auth($request);
        $postId = $request->intParam('id');
        $post   = Post::find($postId);

        if (!$post || !Post::canView($post, (int) $user['id'])) {
            $this->fail('That post is not available.', 404);
        }

        $filter = (string) $request->query('type', '');
        $this->ok([
            'html' => View::partial('partials/reaction-list', [
                'reactors' => Reaction::reactors('post', $postId, $filter ?: null),
                'summary'  => Reaction::summaryFor('post', [$postId], (int) $user['id'])[$postId],
                'active'   => $filter,
                'postId'   => $postId,
            ]),
        ]);
    }

    public function save(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $postId = $request->intParam('id');
        $post   = Post::find($postId);
        if (!$post || !Post::canView($post, (int) $user['id'])) {
            $this->fail('That post is not available.', 404);
        }

        $saved = SavedPost::toggle((int) $user['id'], $postId);
        $this->ok(['saved' => $saved, 'message' => $saved ? 'Post saved' : 'Removed from saved']);
    }

    public function share(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $original = Post::find($request->intParam('id'));
        if (!$original || !Post::canView($original, (int) $user['id'])) {
            Session::flash('error', 'That post is not available to share.');
            $this->back($request, '/');
        }

        // Sharing a share points at the original, so the chain never nests.
        $targetId = $original['shared_post_id'] ? (int) $original['shared_post_id'] : (int) $original['id'];
        $privacy  = (string) $request->input('privacy', 'friends');

        $newId = Post::create([
            'user_id'        => (int) $user['id'],
            'group_id'       => null,
            'shared_post_id' => $targetId,
            'content'        => (string) $request->input('content', ''),
            'background'     => null,
            'feeling'        => null,
            'location'       => null,
            'privacy'        => in_array($privacy, ['public', 'friends', 'only_me'], true) ? $privacy : 'friends',
        ]);

        $targetAuthor = (int) (Post::find($targetId)['user_id'] ?? 0);
        Notifier::send($targetAuthor, (int) $user['id'], 'post_share', 'post', $newId, '/posts/' . $newId);

        Session::flash('success', 'Shared to your timeline.');
        $this->back($request, '/');
    }
}
