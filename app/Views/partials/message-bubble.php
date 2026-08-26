<?php
/**
 * One message row in a thread.
 * @var array $message
 * @var array $__user
 */
$me     = $__user ?? current_user();
$isMine = (int) $message['sender_id'] === (int) $me['id'];
?>
<div class="bubble-row<?= $isMine ? ' is-mine' : '' ?>">
  <?php if (!$isMine): ?>
    <a href="<?= e(profile_url($message['sender'])) ?>" title="<?= e(full_name($message['sender'])) ?>">
      <img class="avatar avatar-28" src="<?= e(avatar_url($message['sender'])) ?>" alt="">
    </a>
  <?php endif; ?>

  <?php if (!empty($message['attachment'])): ?>
    <a class="bubble-attachment" href="<?= e($message['attachment']) ?>" data-lightbox="<?= e($message['attachment']) ?>">
      <img src="<?= e($message['attachment']) ?>" alt="Attachment" loading="lazy">
    </a>
  <?php endif; ?>

  <?php if (trim((string) $message['body']) !== ''): ?>
    <div class="bubble" title="<?= e(full_datetime($message['created_at'])) ?>"><?= rich_text($message['body']) ?></div>
  <?php endif; ?>

  <span class="bubble-time"><?= e(time_ago($message['created_at'])) ?></span>
</div>
