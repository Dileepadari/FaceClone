<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Notifier;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Upload;
use App\Core\View;
use App\Models\Block;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\User;
use App\Models\UserSetting;

final class MessageController extends Controller
{
    public function index(Request $request): void
    {
        $user = $this->auth($request);
        $this->view('messages/index', [
            'title'         => 'Messenger',
            'conversations' => Conversation::inbox((int) $user['id']),
            'conversation'  => null,
            'messages'      => [],
            'contacts'      => Friendship::friends((int) $user['id'], 30),
        ], 'layouts/messenger');
    }

    /** Open (or create) the 1:1 thread with someone. */
    public function startWith(Request $request): void
    {
        $user   = $this->auth($request);
        $target = User::find((int) $request->param('userId'));

        if (!$target) {
            Response::notFound('That person is no longer on FaceClone.');
        }
        if (!$this->canMessage((int) $user['id'], $target)) {
            Response::forbidden('You cannot send messages to this person.');
        }

        $conversationId = Conversation::between((int) $user['id'], (int) $target['id']);
        $this->redirect('/messages/' . $conversationId);
    }

    public function show(Request $request): void
    {
        $user           = $this->auth($request);
        $conversationId = $request->intParam('id');

        if (!Conversation::isParticipant($conversationId, (int) $user['id'])) {
            Response::forbidden('You are not part of that conversation.');
        }

        $conversation = Conversation::find($conversationId);
        $messages     = Message::thread($conversationId, 100);
        Conversation::markRead($conversationId, (int) $user['id']);

        $partner = $conversation['is_group'] ? null : Conversation::partner($conversationId, (int) $user['id']);

        $this->view('messages/index', [
            'title'         => $partner ? full_name($partner) : ($conversation['name'] ?: 'Group chat'),
            'conversations' => Conversation::inbox((int) $user['id']),
            'conversation'  => $conversation,
            'partner'       => $partner,
            'participants'  => Conversation::participants($conversationId),
            'messages'      => $messages,
            'contacts'      => Friendship::friends((int) $user['id'], 30),
            'lastId'        => $messages ? (int) end($messages)['id'] : 0,
        ], 'layouts/messenger');
    }

    public function send(Request $request): void
    {
        $user           = $this->auth($request);
        $this->csrf($request);
        $conversationId = $request->intParam('id');

        if (!Conversation::isParticipant($conversationId, (int) $user['id'])) {
            $this->fail('You are not part of that conversation.', 403);
        }

        $body = (string) $request->input('body', '');
        $file = $request->file('attachment');

        if (trim($body) === '' && !$file) {
            $this->fail('Type a message first.');
        }

        $attachment = null;
        if ($file) {
            $attachment = Upload::image($file, Upload::MSG);
            if (!$attachment) {
                $this->fail(Upload::lastError() ?: 'That attachment could not be uploaded.');
            }
        }

        $messageId = Message::send($conversationId, (int) $user['id'], $body, $attachment);

        foreach (Conversation::participants($conversationId) as $participant) {
            Notifier::send(
                (int) $participant['id'],
                (int) $user['id'],
                'message_new',
                'conversation',
                $conversationId,
                '/messages/' . $conversationId
            );
        }

        $message           = Message::find($messageId);
        $message['sender'] = $user;

        if ($request->isAjax()) {
            $this->ok([
                'id'   => $messageId,
                'html' => View::partial('partials/message-bubble', ['message' => $message, '__user' => $user]),
            ]);
        }

        $this->redirect('/messages/' . $conversationId);
    }

    /** Long-poll style endpoint: returns messages newer than the given id. */
    public function poll(Request $request): void
    {
        $user           = $this->auth($request);
        $conversationId = $request->intParam('id');

        if (!Conversation::isParticipant($conversationId, (int) $user['id'])) {
            $this->fail('You are not part of that conversation.', 403);
        }

        $after    = max(0, $request->int('after'));
        $messages = Message::thread($conversationId, 50, $after);

        $html = '';
        foreach ($messages as $message) {
            $html .= View::partial('partials/message-bubble', ['message' => $message, '__user' => $user]);
        }

        if ($messages) {
            Conversation::markRead($conversationId, (int) $user['id']);
        }

        $this->ok([
            'html'   => $html,
            'lastId' => $messages ? (int) end($messages)['id'] : $after,
            'count'  => count($messages),
        ]);
    }

    public function unread(Request $request): void
    {
        $user = $this->auth($request);
        $this->ok(['count' => Message::unreadCount((int) $user['id'])]);
    }

    /** Feeds the Messenger dropdown in the top bar. */
    public function recent(Request $request): void
    {
        $user = $this->auth($request);
        $this->ok([
            'html'  => View::partial('partials/messenger-dropdown', [
                'conversations' => Conversation::inbox((int) $user['id'], 8),
                '__user'        => $user,
            ]),
            'count' => Message::unreadCount((int) $user['id']),
        ]);
    }

    private function canMessage(int $viewerId, array $target): bool
    {
        $targetId = (int) $target['id'];
        if ($viewerId === $targetId) {
            return false;
        }
        if (Block::exists($viewerId, $targetId) || Block::exists($targetId, $viewerId)) {
            return false;
        }
        $settings = UserSetting::forUser($targetId);
        if ($settings['who_can_message'] === 'friends') {
            return Friendship::areFriends($viewerId, $targetId);
        }
        return true;
    }
}
