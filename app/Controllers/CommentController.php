<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Upload;
use App\Core\View;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;

final class CommentController extends Controller
{
    public function index(Request $request): void
    {
        $user   = $this->auth($request);
        $postId = $request->intParam('id');
        $post   = Post::find($postId);

        if (!$post || !Post::canView($post, (int) $user['id'])) {
            $this->fail('That post is not available.', 404);
        }

        $comments = Comment::forPost($postId, (int) $user['id']);
        $html     = '';
        foreach ($comments as $comment) {
            $html .= View::partial('partials/comment', ['comment' => $comment, 'postId' => $postId, '__user' => $user]);
        }

        $this->ok(['html' => $html, 'count' => Comment::countForPost($postId)]);
    }

    public function store(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $postId  = $request->int('post_id');
        $content = (string) $request->input('content', '');
        $file    = $request->file('image');

        $post = Post::find($postId);
        if (!$post || !Post::canView($post, (int) $user['id'])) {
            $this->fail('That post is not available.', 404);
        }
        if ($content === '' && !$file) {
            $this->fail('Write a comment before posting.');
        }

        $image = null;
        if ($file) {
            $image = Upload::image($file, Upload::POST);
            if (!$image) {
                $this->fail(Upload::lastError() ?: 'That image could not be uploaded.');
            }
        }

        $parentId  = $request->int('parent_id') ?: null;
        $commentId = Comment::create($postId, (int) $user['id'], $content, $parentId, $image);

        $this->notifyThread($post, $parentId, $postId, (int) $user['id']);

        $comment = Comment::hydrateOne($commentId, (int) $user['id']);
        $html    = $parentId
            ? View::partial('partials/comment-reply', ['reply' => $comment, 'postId' => $postId, '__user' => $user])
            : View::partial('partials/comment', ['comment' => $comment, 'postId' => $postId, '__user' => $user]);

        $this->ok([
            'html'     => $html,
            'parentId' => $parentId,
            'count'    => Comment::countForPost($postId),
        ]);
    }

    /** Notify the post author, the parent commenter and other thread participants. */
    private function notifyThread(array $post, ?int $parentId, int $postId, int $actorId): void
    {
        $url = '/posts/' . $postId;
        Notifier::send((int) $post['user_id'], $actorId, 'comment_post', 'post', $postId, $url);

        if ($parentId) {
            $parent = Comment::find($parentId);
            if ($parent) {
                Notifier::send((int) $parent['user_id'], $actorId, 'comment_reply', 'post', $postId, $url);
            }
            return;
        }

        foreach (Comment::threadParticipants($postId) as $participant) {
            if ($participant !== (int) $post['user_id']) {
                Notifier::send($participant, $actorId, 'comment_also', 'post', $postId, $url);
            }
        }
    }

    public function update(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $comment = Comment::find($request->intParam('id'));
        if (!$comment || (int) $comment['user_id'] !== (int) $user['id']) {
            $this->fail('You can only edit your own comments.', 403);
        }

        $content = (string) $request->input('content', '');
        if ($content === '') {
            $this->fail('A comment cannot be empty.');
        }

        Comment::update((int) $comment['id'], $content);
        $this->ok(['content' => rich_text($content)]);
    }

    public function destroy(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $comment = Comment::find($request->intParam('id'));
        if (!$comment) {
            $this->fail('That comment no longer exists.', 404);
        }

        $post    = Post::find((int) $comment['post_id']);
        $isMine  = (int) $comment['user_id'] === (int) $user['id'];
        $isOwner = $post && (int) $post['user_id'] === (int) $user['id'];

        if (!$isMine && !$isOwner) {
            $this->fail('You cannot delete this comment.', 403);
        }

        Comment::delete((int) $comment['id']);
        $this->ok(['deleted' => (int) $comment['id'], 'count' => Comment::countForPost((int) $comment['post_id'])]);
    }

    public function react(Request $request): void
    {
        $user = $this->auth($request);
        $this->csrf($request);

        $commentId = $request->intParam('id');
        $comment   = Comment::find($commentId);
        if (!$comment) {
            $this->fail('That comment no longer exists.', 404);
        }

        $result  = Reaction::toggle((int) $user['id'], 'comment', $commentId, (string) $request->input('type', 'like'));
        $summary = Reaction::summaryFor('comment', [$commentId], (int) $user['id'])[$commentId];

        if ($result['action'] !== 'removed') {
            Notifier::send(
                (int) $comment['user_id'],
                (int) $user['id'],
                'reaction_comment',
                'post',
                (int) $comment['post_id'],
                '/posts/' . $comment['post_id']
            );
        }

        $this->ok(['reaction' => $result['type'], 'summary' => $summary]);
    }
}
