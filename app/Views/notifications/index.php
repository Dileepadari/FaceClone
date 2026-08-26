<div class="layout-3col">
  <aside class="rail rail-left">
    <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Notifications</div>
    <a class="rail-link is-active" href="/notifications"><span class="rail-glyph"><?= icon('bell', 20) ?></span> All</a>
    <a class="rail-link" href="/friends/requests"><span class="rail-glyph"><?= icon('users', 20) ?></span> Friend requests</a>
    <a class="rail-link" href="/messages"><span class="rail-glyph"><?= icon('messenger', 20) ?></span> Messages</a>
    <a class="rail-link" href="/settings/notifications"><span class="rail-glyph"><?= icon('settings', 20) ?></span> Notification settings</a>
  </aside>

  <div class="column-main">
    <div class="card">
      <div class="card-head">
        <h2>Notifications</h2>
        <form method="post" action="/notifications/read-all">
          <?= csrf_field() ?>
          <button class="btn btn-ghost btn-sm" type="submit">Mark all as read</button>
        </form>
      </div>
      <hr class="divider">
      <div style="padding:8px">
        <?php if (!$notifications): ?>
          <div class="empty-state"><?= icon('bell', 40) ?><h3>Nothing here yet</h3>
            <p>Reactions, comments, friend requests and group activity all land here.</p></div>
        <?php else: ?>
          <?php foreach ($notifications as $n): ?>
            <div class="notif-item" style="position:relative">
              <a class="notif-row<?= $n['is_read'] ? '' : ' is-unread' ?>" href="/notifications/<?= (int) $n['id'] ?>/open">
                <span class="notif-avatar">
                  <img class="avatar avatar-60" src="<?= e(avatar_url($n['actor'])) ?>" alt="">
                  <span class="notif-glyph g-<?= e(\App\Models\Notification::iconFor($n['type'])) ?>">
                    <?= icon(\App\Models\Notification::iconFor($n['type']), 14) ?>
                  </span>
                </span>
                <span class="grow" style="min-width:0">
                  <span style="display:block"><?= e($n['text']) ?></span>
                  <span class="notif-time"><?= e(time_ago($n['created_at'])) ?></span>
                </span>
                <?php if (!$n['is_read']): ?><span class="notif-dot"></span><?php endif; ?>
              </a>
              <form class="notif-remove" method="post" action="/notifications/<?= (int) $n['id'] ?>/delete">
                <?= csrf_field() ?>
                <button class="icon-btn" style="background:none;width:28px;height:28px" type="submit"
                        aria-label="Remove notification"><?= icon('close', 14) ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>
