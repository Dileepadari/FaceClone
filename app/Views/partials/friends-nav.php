<?php /** @var string $section */ ?>
<nav aria-label="Friends sections">
  <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Friends</div>
  <a class="rail-link<?= $section === 'home' ? ' is-active' : '' ?>" href="/friends">
    <span class="rail-glyph"><?= icon('friends', 20) ?></span> Home
  </a>
  <a class="rail-link<?= $section === 'requests' ? ' is-active' : '' ?>" href="/friends/requests">
    <span class="rail-glyph"><?= icon('users', 20) ?></span> Friend requests
  </a>
  <a class="rail-link<?= $section === 'suggestions' ? ' is-active' : '' ?>" href="/friends/suggestions">
    <span class="rail-glyph"><?= icon('plus-circle', 20) ?></span> Suggestions
  </a>
  <a class="rail-link<?= $section === 'all' ? ' is-active' : '' ?>" href="/friends/all">
    <span class="rail-glyph"><?= icon('check-circle', 20) ?></span> All friends
  </a>
</nav>
