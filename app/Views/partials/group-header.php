<?php
/**
 * Group cover, title, membership actions and tab strip.
 */
$slug = $group['slug'];
$tabs = ['discussion' => 'Discussion', 'about' => 'About', 'members' => 'Members', 'photos' => 'Photos'];
if ($isModerator) {
    $tabs['requests'] = 'Requests' . ($pendingCount > 0 ? ' (' . $pendingCount . ')' : '');
}
if ($isMember) {
    $tabs['invite'] = 'Invite';
}
if ($isAdmin) {
    $tabs['settings'] = 'Settings';
}
?>
<div class="profile-head">
  <div class="profile-head-inner">
    <div class="group-cover">
      <?php if (!empty($group['cover'])): ?><img src="<?= e($group['cover']) ?>" alt=""><?php endif; ?>
      <?php if ($isAdmin): ?>
        <form class="cover-edit" style="position:absolute;right:16px;bottom:16px" method="post"
              action="/g/<?= e($slug) ?>/cover" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="file" id="group-cover-input" name="cover" accept="image/*" hidden data-auto-submit>
          <button class="btn" type="button" data-trigger-file="#group-cover-input">
            <?= icon('camera', 18) ?> Edit cover
          </button>
        </form>
      <?php endif; ?>
    </div>

    <div style="padding:16px">
      <h1 style="font-size:32px;font-weight:700"><?= e($group['name']) ?></h1>
      <p class="muted mt-8">
        <?= icon($group['privacy'] === 'private' ? 'lock' : 'globe', 14, '') ?>
        <?= $group['privacy'] === 'private' ? 'Private group' : 'Public group' ?>
        &middot; <?= number_short((int) $memberCount) ?> <?= $memberCount === 1 ? 'member' : 'members' ?>
      </p>

      <div class="row mt-12" style="flex-wrap:wrap">
        <?php if ($isMember): ?>
          <a class="btn btn-primary" href="/g/<?= e($slug) ?>/invite"><?= icon('plus', 16) ?> Invite</a>
          <form method="post" action="/g/<?= e($slug) ?>/leave">
            <?= csrf_field() ?>
            <button class="btn" type="submit" data-confirm="Leave <?= e($group['name']) ?>?">
              <?= icon('logout', 16) ?> Leave group
            </button>
          </form>
        <?php elseif (($membership['status'] ?? '') === 'pending'): ?>
          <span class="btn" style="cursor:default"><?= icon('check', 16) ?> Request pending</span>
        <?php else: ?>
          <form method="post" action="/g/<?= e($slug) ?>/join">
            <?= csrf_field() ?>
            <button class="btn btn-primary" type="submit">
              <?= icon('plus', 16) ?>
              <?= ($membership['status'] ?? '') === 'invited' ? 'Accept invitation' : ($group['privacy'] === 'private' ? 'Request to join' : 'Join group') ?>
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <nav class="profile-tabs" aria-label="Group sections">
      <?php foreach ($tabs as $key => $label): ?>
        <a href="/g/<?= e($slug) ?><?= $key === 'discussion' ? '' : '/' . $key ?>"
           class="<?= $tab === $key ? 'is-active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</div>
