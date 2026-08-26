<?php
/**
 * Full-screen story viewer with timed progress bars.
 * @var array $stories
 * @var array $author
 */
?>
<div class="story-viewer" data-story-viewer
     data-prev-user="<?= $prevId ? (int) $prevId : '' ?>"
     data-next-user="<?= $nextId ? (int) $nextId : '' ?>">

  <a class="icon-btn story-close" href="/stories" aria-label="Close story viewer"><?= icon('close', 20) ?></a>

  <?php if ($prevId): ?>
    <a class="story-nav prev" href="/stories/user/<?= (int) $prevId ?>" aria-label="Previous person"><?= icon('chevron-left', 20) ?></a>
  <?php endif; ?>
  <?php if ($nextId): ?>
    <a class="story-nav next" href="/stories/user/<?= (int) $nextId ?>" aria-label="Next person"><?= icon('chevron-right', 20) ?></a>
  <?php endif; ?>

  <div class="story-stage">
    <div class="story-progress">
      <?php foreach ($stories as $_): ?><span><i></i></span><?php endforeach; ?>
    </div>

    <div class="story-head">
      <a href="<?= e(profile_url($author)) ?>"><img class="avatar avatar-40" src="<?= e(avatar_url($author)) ?>" alt=""></a>
      <div class="grow">
        <a class="bold" href="<?= e(profile_url($author)) ?>"><?= e(full_name($author)) ?></a>
        <div class="tiny" style="opacity:.85"><?= e(time_ago($stories[0]['created_at'])) ?></div>
      </div>
      <?php if ($isOwner): ?>
        <form method="post" action="/stories/<?= (int) $stories[0]['id'] ?>/delete">
          <?= csrf_field() ?>
          <button class="icon-btn" style="background:rgba(0,0,0,.4);color:#fff" type="submit"
                  data-confirm="Delete this story?" aria-label="Delete story"><?= icon('trash', 16) ?></button>
        </form>
      <?php endif; ?>
    </div>

    <button class="story-tap prev" type="button" aria-label="Previous story"></button>
    <button class="story-tap next" type="button" aria-label="Next story"></button>

    <?php foreach ($stories as $i => $story): ?>
      <div class="slide<?= $i === 0 ? ' is-active' : '' ?>" data-story-id="<?= (int) $story['id'] ?>">
        <?php if ($story['type'] === 'photo' && $story['media']): ?>
          <img src="<?= e($story['media']) ?>" alt="Story photo">
        <?php else: ?>
          <div class="text-slide" style="background:<?= e(background_style($story['background']) ?: 'linear-gradient(135deg,#7b4dff,#e356a7)') ?>">
            <?= e($story['text']) ?>
          </div>
        <?php endif; ?>

        <?php if ($isOwner): ?>
          <div class="story-viewers-bar">
            <?= icon('users', 16) ?>
            <span class="small">Seen by <?= \App\Models\Story::viewCount((int) $story['id']) ?></span>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
