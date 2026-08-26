<?php /** @var string $section */ ?>
<nav aria-label="Settings sections">
  <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Settings</div>
  <a class="rail-link<?= $section === 'account' ? ' is-active' : '' ?>" href="/settings/account">
    <span class="rail-glyph"><?= icon('settings', 20) ?></span> Account</a>
  <a class="rail-link<?= $section === 'privacy' ? ' is-active' : '' ?>" href="/settings/privacy">
    <span class="rail-glyph"><?= icon('lock', 20) ?></span> Privacy</a>
  <a class="rail-link<?= $section === 'notifications' ? ' is-active' : '' ?>" href="/settings/notifications">
    <span class="rail-glyph"><?= icon('bell', 20) ?></span> Notifications</a>
  <a class="rail-link<?= $section === 'appearance' ? ' is-active' : '' ?>" href="/settings/appearance">
    <span class="rail-glyph"><?= icon('moon', 20) ?></span> Display</a>
  <a class="rail-link<?= $section === 'blocking' ? ' is-active' : '' ?>" href="/settings/blocking">
    <span class="rail-glyph"><?= icon('shield', 20) ?></span> Blocking</a>
</nav>
