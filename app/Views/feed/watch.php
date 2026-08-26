<div class="layout-3col">
  <aside class="rail rail-left">
    <div class="rail-title">Watch</div>
    <a class="rail-link is-active" href="/watch"><span class="rail-glyph"><?= icon('watch', 20) ?></span> Home</a>
    <a class="rail-link" href="/saved"><span class="rail-glyph"><?= icon('bookmark', 20) ?></span> Saved videos</a>
    <a class="rail-link" href="/"><span class="rail-glyph"><?= icon('home', 20) ?></span> Back to feed</a>
  </aside>

  <div class="column-main">
    <h1 class="page-title">Watch</h1>
    <?php if (!$posts): ?>
      <div class="card"><div class="empty-state">
        <?= icon('watch', 40) ?>
        <h3>No videos yet</h3>
        <p>Public posts with a video attached appear here. Try posting one from the composer.</p>
        <a class="btn btn-primary mt-16" href="/">Go to your feed</a>
      </div></div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?= view_partial('partials/post-card', ['post' => $post, '__user' => $__user]) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
