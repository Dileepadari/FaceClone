<?php
/**
 * Messenger: conversation list on the left, open thread on the right.
 */
$open = $conversation ?? null;
?>
<div class="messenger<?= $open ? ' has-thread' : '' ?>">
  <aside class="msg-list">
    <div class="msg-list-head">
      <h1 style="font-size:24px;font-weight:700">Chats</h1>
      <div class="search-pill mt-12" style="max-width:none">
        <?= icon('search', 16) ?>
        <input type="search" placeholder="Search in Messenger" aria-label="Search conversations"
               oninput="(function(v,root){root.querySelectorAll('[data-conv-name]').forEach(function(el){
                 el.closest('.conv-row').style.display = el.textContent.toLowerCase().includes(v.toLowerCase()) ? '' : 'none';});
               })(this.value, document)">
      </div>
    </div>

    <div class="msg-list-body">
      <?php if (!$conversations): ?>
        <div class="empty-state">
          <?= icon('messenger', 40) ?>
          <h3>No conversations yet</h3>
          <p>Start one from a friend's profile or the contacts list.</p>
        </div>
        <?php foreach (array_slice($contacts, 0, 10) as $contact): ?>
          <a class="conv-row" href="/messages/new/<?= (int) $contact['id'] ?>">
            <img class="avatar avatar-48" src="<?= e(avatar_url($contact)) ?>" alt="">
            <span class="grow"><span class="conv-name" data-conv-name><?= e(full_name($contact)) ?></span>
              <br><span class="conv-preview">Start a conversation</span></span>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ($conversations as $conv): ?>
          <a class="conv-row<?= $conv['unread'] > 0 ? ' is-unread' : '' ?><?= $open && (int) $open['id'] === (int) $conv['id'] ? ' is-active' : '' ?>"
             href="/messages/<?= (int) $conv['id'] ?>">
            <?php if ($conv['partner'] && \App\Models\User::isOnline($conv['partner'])): ?>
              <span class="avatar-online"><img class="avatar avatar-48" src="<?= e(avatar_url($conv['partner'])) ?>" alt=""></span>
            <?php else: ?>
              <img class="avatar avatar-48"
                   src="<?= e($conv['partner'] ? avatar_url($conv['partner']) : '/avatar/0?n=' . urlencode($conv['title'])) ?>" alt="">
            <?php endif; ?>
            <span class="grow" style="min-width:0">
              <span class="conv-name truncate" data-conv-name style="display:block"><?= e($conv['title']) ?></span>
              <span class="conv-preview truncate" style="display:block">
                <?php if ($conv['last_sender_id'] && (int) $conv['last_sender_id'] === (int) $__user['id']): ?>You: <?php endif; ?>
                <?php if ($conv['last_body']): ?><?= e(mb_substr($conv['last_body'], 0, 40)) ?>
                <?php elseif ($conv['last_attachment']): ?>Sent a photo
                <?php else: ?>No messages yet<?php endif; ?>
                <?php if ($conv['last_at']): ?>&middot; <?= e(time_ago($conv['last_at'])) ?><?php endif; ?>
              </span>
            </span>
            <?php if ($conv['unread'] > 0): ?><span class="notif-dot"></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </aside>

  <?php if (!$open): ?>
    <section class="thread" style="align-items:center;justify-content:center">
      <div class="empty-state">
        <?= icon('messenger', 48) ?>
        <h3>Select a conversation</h3>
        <p>Choose someone from the list, or start a new chat from a profile.</p>
      </div>
    </section>
  <?php else: ?>
    <section class="thread" data-thread="<?= (int) $open['id'] ?>" data-last-id="<?= (int) $lastId ?>">
      <header class="thread-head">
        <a class="icon-btn" href="/messages" style="background:none" aria-label="Back to chats"><?= icon('chevron-left', 20) ?></a>
        <?php $peer = $partner ?? null; ?>
        <a href="<?= $peer ? e(profile_url($peer)) : '#' ?>" style="line-height:0">
          <img class="avatar avatar-40" src="<?= e($peer ? avatar_url($peer) : '/avatar/0?n=' . urlencode((string) $open['name'])) ?>" alt="">
        </a>
        <div class="grow" style="min-width:0">
          <a class="bold truncate" style="display:block;color:var(--text)" href="<?= $peer ? e(profile_url($peer)) : '#' ?>">
            <?= e($peer ? full_name($peer) : ($open['name'] ?: 'Group chat')) ?>
          </a>
          <span class="tiny muted">
            <?php if ($peer && \App\Models\User::isOnline($peer)): ?>Active now
            <?php elseif ($peer && $peer['last_seen']): ?>Active <?= e(time_ago($peer['last_seen'])) ?> ago
            <?php else: ?><?= count($participants) ?> participants<?php endif; ?>
          </span>
        </div>
        <?php if ($peer): ?>
          <a class="icon-btn" href="<?= e(profile_url($peer)) ?>" aria-label="View profile" title="View profile"><?= icon('info', 18) ?></a>
        <?php endif; ?>
      </header>

      <div class="thread-body scroll-y" data-thread-body>
        <?php if (!$messages): ?>
          <div class="empty-state" style="margin:auto">
            <img class="avatar avatar-80" style="margin:0 auto 12px"
                 src="<?= e($peer ? avatar_url($peer) : '/avatar/0') ?>" alt="">
            <h3><?= e($peer ? full_name($peer) : ($open['name'] ?: 'Group chat')) ?></h3>
            <p>Say hello to start the conversation.</p>
          </div>
        <?php else: ?>
          <?php foreach ($messages as $message): ?>
            <?= view_partial('partials/message-bubble', ['message' => $message, '__user' => $__user]) ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <footer class="thread-foot">
        <div data-attachment-preview class="hidden mb-8"></div>
        <form class="thread-composer" data-thread-form method="post"
              action="/messages/<?= (int) $open['id'] ?>" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <label class="icon-btn" style="background:none;cursor:pointer" title="Attach a photo">
            <?= icon('photo', 20) ?>
            <input type="file" name="attachment" accept="image/*" hidden
                   data-file-preview="[data-attachment-preview]">
          </label>
          <div class="thread-input-wrap">
            <textarea class="thread-input" name="body" rows="1" placeholder="Aa" aria-label="Message text"></textarea>
          </div>
          <button class="icon-btn" style="background:none;color:var(--blue)" type="submit" aria-label="Send message">
            <?= icon('send', 20) ?>
          </button>
        </form>
      </footer>
    </section>
  <?php endif; ?>
</div>
