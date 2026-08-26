<?= view_partial('partials/group-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">Invite friends to <?= e($group['name']) ?></h2>

      <?php if (!$friends): ?>
        <div class="empty-state"><?= icon('users', 40) ?><h3>Everyone is already here</h3>
          <p>All of your friends have been invited or have already joined.</p></div>
      <?php else: ?>
        <?php foreach ($friends as $friend): ?>
          <div class="spread" style="padding:10px 0;border-bottom:1px solid var(--divider-soft)" data-person-card>
            <a class="row" href="<?= e(profile_url($friend)) ?>" style="color:inherit;min-width:0">
              <img class="avatar avatar-48" src="<?= e(avatar_url($friend)) ?>" alt="">
              <span class="bold truncate"><?= e(full_name($friend)) ?></span>
            </a>
            <button class="btn btn-primary btn-sm" type="button"
                    data-friend-action="/g/<?= e($group['slug']) ?>/invite/<?= (int) $friend['id'] ?>"
                    data-replace-with='<span class="small muted">Invited</span>'>Invite</button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if ($invited): ?>
        <h2 class="section-title mt-16 mb-12">Invitations sent (<?= count($invited) ?>)</h2>
        <?php foreach ($invited as $person): ?>
          <div class="row" style="padding:8px 0">
            <img class="avatar avatar-36" src="<?= e(avatar_url($person)) ?>" alt="">
            <span class="grow truncate"><?= e(full_name($person)) ?></span>
            <span class="small muted">Awaiting reply</span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
