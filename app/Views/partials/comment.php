<?php
/**
 * A top-level comment with its reply thread.
 * @var array $comment
 * @var int   $postId
 * @var array $__user
 */
$me      = $__user ?? current_user();
$summary = $comment['reactions'] ?? ['total' => 0, 'top' => [], 'mine' => null];
$types   = reaction_types();
?>
<div class="comment" data-comment-id="<?= (int) $comment['id'] ?>">
  <a href="<?= e(profile_url($comment['author'])) ?>">
    <img class="avatar avatar-32" src="<?= e(avatar_url($comment['author'])) ?>" alt="">
  </a>
  <div class="grow" style="min-width:0">
    <div class="comment-bubble">
      <a class="name" href="<?= e(profile_url($comment['author'])) ?>"><?= e(full_name($comment['author'])) ?></a>
      <div class="body" data-comment-body data-raw="<?= e($comment['content']) ?>"><?= rich_text($comment['content']) ?></div>
    </div>
    <?php if (!empty($comment['image'])): ?>
      <div class="comment-image">
        <a href="<?= e($comment['image']) ?>" data-lightbox="<?= e($comment['image']) ?>">
          <img src="<?= e($comment['image']) ?>" alt="Photo in comment" loading="lazy">
        </a>
      </div>
    <?php endif; ?>

    <div class="comment-actions">
      <span data-reaction-holder data-react-url="/comments/<?= (int) $comment['id'] ?>/react" style="position:relative">
        <div class="reaction-bar">
          <?php foreach ($types as $key => $meta): ?>
            <button type="button" data-react="<?= e($key) ?>" aria-label="<?= e($meta['label']) ?>"><?= $meta['emoji'] ?></button>
          <?php endforeach; ?>
        </div>
        <button type="button" data-reaction-button data-react="<?= $summary['mine'] ? e($summary['mine']) : 'like' ?>"
                class="<?= $summary['mine'] ? 'is-reacted' : '' ?>">
          <span data-reaction-label><?= $summary['mine'] ? e($types[$summary['mine']]['label']) : 'Like' ?></span>
        </button>
      </span>
      <button type="button" data-reply-to="<?= (int) $comment['id'] ?>">Reply</button>
      <?php if (!empty($comment['can_edit'])): ?>
        <button type="button" data-edit-comment="<?= (int) $comment['id'] ?>">Edit</button>
      <?php endif; ?>
      <button type="button" data-delete-comment="<?= (int) $comment['id'] ?>">Delete</button>
      <span class="muted" style="font-weight:400"><?= e(time_ago($comment['created_at'])) ?></span>
      <?php if ($summary['total'] > 0): ?>
        <span class="comment-count-pill" data-reaction-summary>
          <span data-reaction-chips><?php foreach ($summary['top'] as $t): ?><?= $types[$t]['emoji'] ?? '' ?><?php endforeach; ?></span>
          <span data-reaction-count><?= (int) $summary['total'] ?></span>
        </span>
      <?php else: ?>
        <span class="comment-count-pill hidden" data-reaction-summary>
          <span data-reaction-chips></span><span data-reaction-count></span>
        </span>
      <?php endif; ?>
    </div>

    <div class="comment-replies" data-replies>
      <?php foreach ($comment['replies'] ?? [] as $reply): ?>
        <?= view_partial('partials/comment-reply', ['reply' => $reply, 'postId' => $postId, '__user' => $me]) ?>
      <?php endforeach; ?>
    </div>

    <form class="comment-form hidden" data-reply-form data-comment-form data-dismiss-after="1"
          method="post" action="/comments" enctype="multipart/form-data" style="padding:8px 0 0">
      <?= csrf_field() ?>
      <input type="hidden" name="post_id" value="<?= (int) $postId ?>">
      <input type="hidden" name="parent_id" value="<?= (int) $comment['id'] ?>">
      <img class="avatar avatar-28" src="<?= e(avatar_url($me)) ?>" alt="">
      <div class="comment-input-wrap">
        <textarea class="comment-input" name="content" rows="1"
                  placeholder="Reply to <?= e($comment['author']['first_name'] ?? 'this comment') ?>…"
                  aria-label="Write a reply"></textarea>
        <div class="comment-tools">
          <button class="send-btn" type="submit" aria-label="Post reply"><?= icon('send', 16) ?></button>
        </div>
      </div>
    </form>
  </div>
</div>
