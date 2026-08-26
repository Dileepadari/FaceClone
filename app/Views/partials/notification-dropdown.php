<?php /** @var array $notifications */ ?>
<div class="spread" style="padding:8px 8px 12px">
  <h2 style="font-size:20px;font-weight:700">Notifications</h2>
  <form method="post" action="/notifications/read-all">
    <?= csrf_field() ?>
    <button class="btn btn-ghost btn-sm" type="submit">Mark all read</button>
  </form>
</div>

<?php if (!$notifications): ?>
  <div class="empty-state"><?= icon('bell', 32) ?><p>No notifications yet.</p></div>
<?php else: ?>
  <?php foreach ($notifications as $n): ?>
    <a class="notif-row<?= $n['is_read'] ? '' : ' is-unread' ?>" href="/notifications/<?= (int) $n['id'] ?>/open">
      <span class="notif-avatar">
        <img class="avatar avatar-48" src="<?= e(avatar_url($n['actor'])) ?>" alt="">
        <span class="notif-glyph g-<?= e(\App\Models\Notification::iconFor($n['type'])) ?>">
          <?= icon(\App\Models\Notification::iconFor($n['type']), 12) ?>
        </span>
      </span>
      <span class="grow" style="min-width:0">
        <span style="display:block"><?= e($n['text']) ?></span>
        <span class="notif-time"><?= e(time_ago($n['created_at'])) ?></span>
      </span>
      <?php if (!$n['is_read']): ?><span class="notif-dot"></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
  <a class="menu-item center" href="/notifications" style="justify-content:center;color:var(--blue-text)">See all notifications</a>
<?php endif; ?>
