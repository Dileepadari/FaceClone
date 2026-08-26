<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/settings-nav', ['section' => $section]) ?></aside>

  <div class="column-main">
    <h1 class="page-title">Blocking</h1>
    <div class="card">
      <div class="card-body">
        <h2 class="section-title">Blocked people</h2>
        <p class="muted small mt-8 mb-16">
          Blocked people cannot see your profile or posts, message you, or send you a friend request.
          Blocking someone also removes any existing friendship.
        </p>

        <?php if (!$blocked): ?>
          <div class="empty-state"><?= icon('shield', 40) ?><h3>You have not blocked anyone</h3>
            <p>Use the menu on someone's profile to block them.</p></div>
        <?php else: ?>
          <?php foreach ($blocked as $person): ?>
            <div class="spread" style="padding:10px 0;border-bottom:1px solid var(--divider-soft)">
              <div class="row" style="min-width:0">
                <img class="avatar avatar-48" src="<?= e(avatar_url($person)) ?>" alt="">
                <div style="min-width:0">
                  <div class="bold truncate"><?= e(full_name($person)) ?></div>
                  <div class="small muted">Blocked <?= e(time_ago($person['blocked_at'])) ?></div>
                </div>
              </div>
              <form method="post" action="/friends/<?= (int) $person['id'] ?>/unblock">
                <?= csrf_field() ?>
                <button class="btn btn-sm" type="submit">Unblock</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>
