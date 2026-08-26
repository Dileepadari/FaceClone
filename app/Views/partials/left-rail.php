<?php
/**
 * Home left rail: shortcuts and the viewer's groups.
 * @var array $__user
 * @var array $myGroups
 */
$me = $__user ?? current_user();
?>
<nav aria-label="Shortcuts">
  <a class="rail-link" href="<?= e(profile_url($me)) ?>">
    <img class="avatar rail-glyph" src="<?= e(avatar_url($me)) ?>" alt="">
    <?= e(full_name($me)) ?>
  </a>
  <a class="rail-link<?= nav_active('/friends') ?>" href="/friends">
    <span class="rail-glyph"><?= icon('friends', 20) ?></span> Friends
  </a>
  <a class="rail-link<?= nav_active('/groups') ?>" href="/groups">
    <span class="rail-glyph"><?= icon('groups', 20) ?></span> Groups
  </a>
  <a class="rail-link<?= nav_active('/marketplace') ?>" href="/marketplace">
    <span class="rail-glyph"><?= icon('marketplace', 20) ?></span> Marketplace
  </a>
  <a class="rail-link<?= nav_active('/watch') ?>" href="/watch">
    <span class="rail-glyph"><?= icon('watch', 20) ?></span> Watch
  </a>
  <a class="rail-link<?= nav_active('/memories') ?>" href="/memories">
    <span class="rail-glyph"><?= icon('memories', 20) ?></span> Memories
  </a>
  <a class="rail-link<?= nav_active('/saved') ?>" href="/saved">
    <span class="rail-glyph"><?= icon('bookmark', 20) ?></span> Saved
  </a>
  <a class="rail-link<?= nav_active('/events') ?>" href="/events">
    <span class="rail-glyph"><?= icon('calendar', 20) ?></span> Events
  </a>
  <a class="rail-link<?= nav_active('/stories') ?>" href="/stories">
    <span class="rail-glyph"><?= icon('reels', 20) ?></span> Stories
  </a>

  <?php if (!empty($myGroups)): ?>
    <hr class="rail-sep">
    <div class="rail-title">Your shortcuts</div>
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

  <hr class="rail-sep">
  <p class="tiny muted" style="padding:8px">
    FaceClone &middot; Privacy &middot; Terms &middot; Cookies &middot; &copy; <?= date('Y') ?>
  </p>
</nav>
