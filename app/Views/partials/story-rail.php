<?php
/**
 * Horizontal story tray at the top of the feed.
 * @var array $trays
 * @var array $__user
 */
$me = $__user ?? current_user();
?>
<div class="card" style="padding:8px">
  <div class="story-rail scroll-x">
    <a class="story-card story-create" href="/stories/create">
      <img class="create-photo" src="<?= e(avatar_url($me)) ?>" alt="">
      <div class="create-foot">
        <span class="create-plus"><?= icon('plus', 20) ?></span>
        Create story
      </div>
    </a>

    <?php foreach ($trays as $tray): ?>
      <?php
        $latest = end($tray['stories']);
        $bg     = background_style($latest['background'] ?? null);
      ?>
      <a class="story-card" href="/stories/user/<?= (int) $tray['user']['id'] ?>">
        <?php if ($latest['type'] === 'photo' && $latest['media']): ?>
          <img class="story-bg" src="<?= e($latest['media']) ?>" alt="" loading="lazy">
        <?php else: ?>
          <div class="story-text-bg" style="background:<?= e($bg ?: 'linear-gradient(135deg,#7b4dff,#e356a7)') ?>">
            <?= e(mb_substr((string) $latest['text'], 0, 60)) ?>
          </div>
        <?php endif; ?>
        <span class="story-scrim"></span>
        <span class="story-ring<?= $tray['all_seen'] ? ' is-seen' : '' ?>">
          <img src="<?= e(avatar_url($tray['user'])) ?>" alt="">
        </span>
        <span class="story-name"><?= e($tray['is_self'] ? 'Your story' : full_name($tray['user'])) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
