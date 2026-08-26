<?= view_partial('partials/group-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">About this group</h2>
      <p><?= nl2br(e($group['description'] ?: 'This group does not have a description yet.')) ?></p>
      <hr class="divider mt-16 mb-16">
      <div class="intro-row">
        <?= icon($group['privacy'] === 'private' ? 'lock' : 'globe', 20) ?>
        <span><strong><?= $group['privacy'] === 'private' ? 'Private group' : 'Public group' ?></strong><br>
          <span class="small muted">
            <?= $group['privacy'] === 'private'
                ? 'Only members can see posts. An admin approves each new member.'
                : 'Anyone can find this group, read the posts and join instantly.' ?>
          </span></span>
      </div>
      <div class="intro-row"><?= icon('users', 20) ?>
        <span><strong><?= number_short((int) $memberCount) ?> members</strong></span></div>
      <div class="intro-row"><?= icon('calendar', 20) ?>
        <span><strong>Created <?= date('j F Y', strtotime($group['created_at'])) ?></strong></span></div>
    </div>
  </div>
</div>
