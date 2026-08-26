<div class="layout-3col">
  <aside class="rail rail-left">
    <?= view_partial('partials/left-rail', ['__user' => $__user, 'myGroups' => []]) ?>
  </aside>

  <div class="column-main">
    <h1 class="page-title">Memories</h1>
    <p class="muted mb-16">Posts you shared on this day in previous years.</p>
    <?php if (!$posts): ?>
      <div class="card"><div class="empty-state">
        <?= icon('memories', 40) ?>
        <h3>No memories for today</h3>
        <p>Once you have been posting for a while, this day in past years will show up here.</p>
      </div></div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <div class="card card-pad" style="margin-bottom:-8px;border-radius:8px 8px 0 0">
          <strong><?= date('Y', strtotime($post['created_at'])) ?></strong>
          <span class="muted">&middot; <?= date('F j', strtotime($post['created_at'])) ?></span>
        </div>
        <?= view_partial('partials/post-card', ['post' => $post, '__user' => $__user]) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
