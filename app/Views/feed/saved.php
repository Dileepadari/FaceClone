<div class="layout-3col">
  <aside class="rail rail-left">
    <?= view_partial('partials/left-rail', ['__user' => $__user, 'myGroups' => []]) ?>
  </aside>

  <div class="column-main">
    <h1 class="page-title">Saved <span class="muted" style="font-size:16px;font-weight:400">(<?= (int) $count ?>)</span></h1>
    <?php if (!$posts): ?>
      <div class="card"><div class="empty-state">
        <?= icon('bookmark', 40) ?>
        <h3>Nothing saved yet</h3>
        <p>Use the &hellip; menu on any post and choose <strong>Save post</strong> to keep it here.</p>
        <a class="btn btn-primary mt-16" href="/">Browse your feed</a>
      </div></div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?= view_partial('partials/post-card', ['post' => $post, '__user' => $__user]) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
