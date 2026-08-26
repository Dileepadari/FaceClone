<?php
/**
 * Persistent top bar: brand + search, centre navigation, account controls.
 * @var array $__user
 * @var array $__chrome
 */
$notifCount = (int) ($__chrome['unread_notifications'] ?? 0);
$msgCount   = (int) ($__chrome['unread_messages'] ?? 0);
$reqCount   = (int) ($__chrome['friend_requests'] ?? 0);
$q          = $_GET['q'] ?? '';
?>
<header class="topbar">
  <div class="topbar-left">
    <a class="brand" href="/" aria-label="FaceClone home">
      <span class="brand-mark"><img class="logo-mono" src="<?= asset('assets/img/logo-mark.png') ?>" alt=""></span>
      <span class="brand-name">FaceClone</span>
    </a>

    <form class="search-pill" action="/search" method="get" role="search">
      <?= icon('search', 17) ?>
      <input type="search" name="q" placeholder="Search FaceClone" value="<?= e($q) ?>"
             aria-label="Search FaceClone" autocomplete="off" data-typeahead>
      <div class="typeahead" id="typeahead-panel"></div>
    </form>
  </div>

  <nav class="topbar-nav" aria-label="Primary">
    <a href="/" class="<?= trim(nav_active('/')) ?>" title="Home" aria-label="Home"><?= icon('home', 26) ?></a>
    <a href="/watch" class="<?= trim(nav_active('/watch')) ?>" title="Watch" aria-label="Watch"><?= icon('watch', 26) ?></a>
    <a href="/marketplace" class="<?= trim(nav_active('/marketplace')) ?>" title="Marketplace" aria-label="Marketplace"><?= icon('marketplace', 26) ?></a>
    <a href="/groups" class="<?= trim(nav_active('/groups')) ?>" title="Groups" aria-label="Groups"><?= icon('groups', 26) ?></a>
    <a href="/friends" class="<?= trim(nav_active('/friends')) ?>" title="Friends" aria-label="Friends">
      <?= icon('friends', 26) ?>
      <?php if ($reqCount > 0): ?><span class="pill-count"><?= $reqCount ?></span><?php endif; ?>
    </a>
  </nav>

  <div class="topbar-right">
    <div class="dropdown">
      <button class="icon-btn" type="button" data-dropdown="menu-panel"
              aria-controls="menu-panel" aria-expanded="false" aria-label="Menu" title="Menu">
        <?= icon('menu-grid', 20) ?>
      </button>
      <div class="dropdown-panel" id="menu-panel">
        <div class="dropdown-title">Menu</div>
        <a class="menu-item" href="/events"><span class="menu-glyph"><?= icon('calendar', 20) ?></span> Events</a>
        <a class="menu-item" href="/saved"><span class="menu-glyph"><?= icon('bookmark', 20) ?></span> Saved</a>
        <a class="menu-item" href="/memories"><span class="menu-glyph"><?= icon('memories', 20) ?></span> Memories</a>
        <a class="menu-item" href="/stories"><span class="menu-glyph"><?= icon('reels', 20) ?></span> Stories</a>
        <a class="menu-item" href="/groups"><span class="menu-glyph"><?= icon('groups', 20) ?></span> Groups</a>
        <a class="menu-item" href="/marketplace"><span class="menu-glyph"><?= icon('marketplace', 20) ?></span> Marketplace</a>
        <a class="menu-item" href="/friends"><span class="menu-glyph"><?= icon('users', 20) ?></span> Friends</a>
        <a class="menu-item" href="/watch"><span class="menu-glyph"><?= icon('watch', 20) ?></span> Watch</a>
      </div>
    </div>

    <div class="dropdown" data-message-badge>
      <button class="icon-btn" type="button" data-dropdown="messenger-panel" data-load="/api/messages/recent"
              aria-controls="messenger-panel" aria-expanded="false" aria-label="Messenger" title="Messenger">
        <?= icon('messenger', 20) ?>
      </button>
      <?php if ($msgCount > 0): ?><span class="badge"><?= $msgCount > 99 ? '99+' : $msgCount ?></span><?php endif; ?>
      <div class="dropdown-panel" id="messenger-panel"></div>
    </div>

    <div class="dropdown">
      <button class="icon-btn" type="button" data-dropdown="notif-panel" data-load="/api/notifications"
              aria-controls="notif-panel" aria-expanded="false" aria-label="Notifications" title="Notifications">
        <?= icon('bell', 20) ?>
      </button>
      <?php if ($notifCount > 0): ?><span class="badge"><?= $notifCount > 99 ? '99+' : $notifCount ?></span><?php endif; ?>
      <div class="dropdown-panel" id="notif-panel"></div>
    </div>

    <div class="dropdown">
      <button class="avatar-btn" type="button" data-dropdown="account-panel"
              aria-controls="account-panel" aria-expanded="false" aria-label="Your account">
        <img class="avatar avatar-40" src="<?= e(avatar_url($__user)) ?>" alt="<?= e(full_name($__user)) ?>">
      </button>
      <div class="dropdown-panel" id="account-panel">
        <a class="menu-item" href="<?= e(profile_url($__user)) ?>" style="padding:12px 8px">
          <img class="avatar avatar-60" src="<?= e(avatar_url($__user)) ?>" alt="">
          <span class="grow">
            <span class="bold" style="font-size:17px"><?= e(full_name($__user)) ?></span><br>
            <span class="menu-sub">See your profile</span>
          </span>
        </a>
        <hr class="rail-sep">
        <a class="menu-item" href="/settings/account"><span class="menu-glyph"><?= icon('settings', 20) ?></span>
          <span class="grow">Settings &amp; privacy</span></a>
        <a class="menu-item" href="/settings/appearance"><span class="menu-glyph"><?= icon('moon', 20) ?></span>
          <span class="grow">Display &amp; accessibility</span></a>
        <a class="menu-item" href="/settings/blocking"><span class="menu-glyph"><?= icon('shield', 20) ?></span>
          <span class="grow">Blocking</span></a>
        <a class="menu-item" href="/saved"><span class="menu-glyph"><?= icon('bookmark', 20) ?></span>
          <span class="grow">Saved posts</span></a>
        <hr class="rail-sep">
        <form method="post" action="/logout">
          <?= csrf_field() ?>
          <button class="menu-item" type="submit" style="width:100%">
            <span class="menu-glyph"><?= icon('logout', 20) ?></span><span class="grow">Log out</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</header>
