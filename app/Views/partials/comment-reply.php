<?php
/**
 * A second-level reply. Replies do not nest further, matching Facebook.
 * @var array $reply
 * @var int   $postId
 * @var array $__user
 */
$summary = $reply['reactions'] ?? ['total' => 0, 'top' => [], 'mine' => null];
$types   = reaction_types();
?>
<div class="comment" data-comment-id="<?= (int) $reply['id'] ?>">
  <a href="<?= e(profile_url($reply['author'])) ?>">
    <img class="avatar avatar-28" src="<?= e(avatar_url($reply['author'])) ?>" alt="">
  </a>
  <div class="grow" style="min-width:0">
    <div class="comment-bubble">
      <a class="name" href="<?= e(profile_url($reply['author'])) ?>"><?= e(full_name($reply['author'])) ?></a>
      <div class="body" data-comment-body data-raw="<?= e($reply['content']) ?>"><?= rich_text($reply['content']) ?></div>
    </div>
    <?php if (!empty($reply['image'])): ?>
      <div class="comment-image">
        <a href="<?= e($reply['image']) ?>" data-lightbox="<?= e($reply['image']) ?>">
          <img src="<?= e($reply['image']) ?>" alt="Photo in reply" loading="lazy">
        </a>
      </div>
    <?php endif; ?>
    <div class="comment-actions">
      <span data-reaction-holder data-react-url="/comments/<?= (int) $reply['id'] ?>/react" style="position:relative">
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
      <?php if (!empty($reply['can_edit'])): ?>
        <button type="button" data-edit-comment="<?= (int) $reply['id'] ?>">Edit</button>
      <?php endif; ?>
      <button type="button" data-delete-comment="<?= (int) $reply['id'] ?>">Delete</button>
      <span class="muted" style="font-weight:400"><?= e(time_ago($reply['created_at'])) ?></span>
      <span class="comment-count-pill<?= $summary['total'] > 0 ? '' : ' hidden' ?>" data-reaction-summary>
        <span data-reaction-chips><?php foreach ($summary['top'] as $t): ?><?= $types[$t]['emoji'] ?? '' ?><?php endforeach; ?></span>
        <span data-reaction-count><?= $summary['total'] > 0 ? (int) $summary['total'] : '' ?></span>
      </span>
    </div>
  </div>
</div>
