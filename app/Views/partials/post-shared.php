<?php
/**
 * The embedded original inside a shared post.
 * @var array $shared
 */
$bg = background_style($shared['background'] ?? null);
?>
<a class="post-shared" href="/posts/<?= (int) $shared['id'] ?>" style="display:block;color:inherit">
  <div class="post-head">
    <img class="avatar avatar-32" src="<?= e(avatar_url($shared['author'])) ?>" alt="">
    <div class="grow">
      <div class="post-author"><?= e(full_name($shared['author'])) ?></div>
      <div class="post-meta"><?= e(time_ago($shared['created_at'])) ?></div>
    </div>
  </div>
  <?php if ($bg !== '' && trim((string) $shared['content']) !== ''): ?>
    <div class="post-bg" style="background:<?= e($bg) ?>;min-height:180px;font-size:22px"><?= rich_text($shared['content']) ?></div>
  <?php elseif (trim((string) $shared['content']) !== ''): ?>
    <div class="post-body"><div class="post-text clamp-3"><?= rich_text($shared['content']) ?></div></div>
  <?php endif; ?>
  <?php if (!empty($shared['media'])): ?>
    <?= view_partial('partials/post-media', ['media' => $shared['media'], 'postUrl' => '/posts/' . $shared['id']]) ?>
  <?php endif; ?>
</a>
