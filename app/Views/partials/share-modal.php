<?php
/**
 * Share dialog for one post.
 * @var array $post
 * @var array $__user
 */
$me = $__user ?? current_user();
?>
<div class="modal-backdrop" id="share-post-<?= (int) $post['id'] ?>" role="dialog" aria-modal="true" aria-label="Share post">
  <div class="modal">
    <form method="post" action="/posts/<?= (int) $post['id'] ?>/share">
      <?= csrf_field() ?>
      <div class="modal-head">
        <h2>Share this post</h2>
        <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button>
      </div>
      <div class="modal-body">
        <div class="row mb-12">
          <img class="avatar avatar-40" src="<?= e(avatar_url($me)) ?>" alt="">
          <div>
            <div class="bold"><?= e(full_name($me)) ?></div>
            <select class="privacy-select" name="privacy" aria-label="Share audience">
              <option value="friends">Friends</option>
              <option value="public">Public</option>
              <option value="only_me">Only me</option>
            </select>
          </div>
        </div>
        <textarea class="textarea" name="content" rows="3" placeholder="Say something about this…"></textarea>
        <div class="mt-12" style="border:1px solid var(--divider);border-radius:8px;padding:10px">
          <div class="row">
            <img class="avatar avatar-28" src="<?= e(avatar_url($post['author'])) ?>" alt="">
            <span class="bold small"><?= e(full_name($post['author'])) ?></span>
            <span class="muted small">&middot; <?= e(time_ago($post['created_at'])) ?></span>
          </div>
          <?php if (trim((string) $post['content']) !== ''): ?>
            <p class="small clamp-3 mt-8"><?= e($post['content']) ?></p>
          <?php endif; ?>
          <?php if (!empty($post['media'][0]) && $post['media'][0]['type'] === 'image'): ?>
            <img class="mt-8" style="border-radius:6px;max-height:160px;object-fit:cover;width:100%"
                 src="<?= e($post['media'][0]['path']) ?>" alt="">
          <?php endif; ?>
        </div>
      </div>
      <div class="modal-foot">
        <button class="btn btn-primary btn-block" type="submit">Share now</button>
      </div>
    </form>
  </div>
</div>
