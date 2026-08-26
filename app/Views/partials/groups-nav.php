<?php /** @var string $section  @var array $myGroups */ ?>
<nav aria-label="Groups sections">
  <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Groups</div>
  <a class="rail-link<?= $section === 'feed' ? ' is-active' : '' ?>" href="/groups">
    <span class="rail-glyph"><?= icon('groups', 20) ?></span> Your feed
  </a>
  <a class="rail-link<?= $section === 'discover' ? ' is-active' : '' ?>" href="/groups/discover">
    <span class="rail-glyph"><?= icon('search', 20) ?></span> Discover
  </a>
  <a class="rail-link<?= $section === 'create' ? ' is-active' : '' ?>" href="/groups/create">
    <span class="rail-glyph"><?= icon('plus', 20) ?></span> Create group
  </a>

  <?php if (!empty($myGroups)): ?>
    <hr class="rail-sep">
    <div class="rail-title">Groups you have joined</div>
    <?php foreach ($myGroups as $group): ?>
      <a class="rail-link" href="/g/<?= e($group['slug']) ?>">
        <?php if (!empty($group['cover'])): ?>
          <img class="rail-glyph" style="border-radius:8px" src="<?= e($group['cover']) ?>" alt="">
        <?php else: ?>
          <span class="rail-glyph" style="border-radius:8px"><?= icon('groups', 18) ?></span>
        <?php endif; ?>
        <span class="truncate"><?= e($group['name']) ?></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</nav>
