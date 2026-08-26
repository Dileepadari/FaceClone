<?php /** @var array $categories  @var string $category */ ?>
<nav aria-label="Marketplace">
  <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Marketplace</div>
  <a class="rail-link" href="/marketplace/create"><span class="rail-glyph"><?= icon('plus', 20) ?></span> Create listing</a>
  <a class="rail-link<?= nav_active('/marketplace/selling') ?>" href="/marketplace/selling">
    <span class="rail-glyph"><?= icon('tag', 20) ?></span> Your listings</a>
  <hr class="rail-sep">
  <div class="rail-title">Categories</div>
  <a class="rail-link<?= ($category ?? '') === '' ? ' is-active' : '' ?>" href="/marketplace">
    <span class="rail-glyph"><?= icon('marketplace', 18) ?></span> All items</a>
  <?php foreach ($categories as $key => $label): ?>
    <a class="rail-link<?= ($category ?? '') === $key ? ' is-active' : '' ?>" href="/marketplace?category=<?= e($key) ?>">
      <span class="rail-glyph"><?= icon('tag', 18) ?></span> <?= e($label) ?>
    </a>
  <?php endforeach; ?>
</nav>
