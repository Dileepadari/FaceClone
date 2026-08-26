<?php /** Mobile tab bar, mirrors the top bar's primary destinations. */ ?>
<nav class="bottom-nav" aria-label="Primary mobile">
  <a href="/" class="<?= trim(nav_active('/')) ?>" aria-label="Home"><?= icon('home', 24) ?></a>
  <a href="/watch" class="<?= trim(nav_active('/watch')) ?>" aria-label="Watch"><?= icon('watch', 24) ?></a>
  <a href="/friends" class="<?= trim(nav_active('/friends')) ?>" aria-label="Friends">
    <?= icon('friends', 24) ?>
    <?php if (($__chrome['friend_requests'] ?? 0) > 0): ?><span class="badge"><?= (int) $__chrome['friend_requests'] ?></span><?php endif; ?>
  </a>
  <a href="/messages" class="<?= trim(nav_active('/messages')) ?>" aria-label="Messenger">
    <?= icon('messenger', 24) ?>
    <?php if (($__chrome['unread_messages'] ?? 0) > 0): ?><span class="badge"><?= (int) $__chrome['unread_messages'] ?></span><?php endif; ?>
  </a>
  <a href="/notifications" class="<?= trim(nav_active('/notifications')) ?>" aria-label="Notifications">
    <?= icon('bell', 24) ?>
    <?php if (($__chrome['unread_notifications'] ?? 0) > 0): ?><span class="badge"><?= (int) $__chrome['unread_notifications'] ?></span><?php endif; ?>
  </a>
</nav>
