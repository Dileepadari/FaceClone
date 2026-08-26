<?php /** @var array $conversations */ ?>
<div class="spread" style="padding:8px 8px 12px">
  <h2 style="font-size:20px;font-weight:700">Chats</h2>
  <a class="btn btn-ghost btn-sm" href="/messages">Open Messenger</a>
</div>

<?php if (!$conversations): ?>
  <div class="empty-state"><?= icon('messenger', 32) ?><p>No conversations yet.</p></div>
<?php else: ?>
  <?php foreach ($conversations as $conv): ?>
    <a class="conv-row<?= $conv['unread'] > 0 ? ' is-unread' : '' ?>" href="/messages/<?= (int) $conv['id'] ?>">
      <img class="avatar avatar-48" src="<?= e($conv['partner'] ? avatar_url($conv['partner']) : '/avatar/0?n=' . urlencode($conv['title'])) ?>" alt="">
      <span class="grow" style="min-width:0">
        <span class="conv-name truncate" style="display:block"><?= e($conv['title']) ?></span>
        <span class="conv-preview truncate" style="display:block">
          <?php if ($conv['last_body']): ?>
            <?= e(mb_substr($conv['last_body'], 0, 42)) ?>
          <?php elseif ($conv['last_attachment']): ?>
            Sent a photo
          <?php else: ?>
            Say hello
          <?php endif; ?>
          <?php if ($conv['last_at']): ?> &middot; <?= e(time_ago($conv['last_at'])) ?><?php endif; ?>
        </span>
      </span>
      <?php if ($conv['unread'] > 0): ?><span class="notif-dot"></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
<?php endif; ?>
