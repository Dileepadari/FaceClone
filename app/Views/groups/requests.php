<?= view_partial('partials/group-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">Join requests <span class="muted" style="font-weight:400">(<?= count($requests) ?>)</span></h2>
      <?php if (!$requests): ?>
        <div class="empty-state"><?= icon('check-circle', 40) ?><h3>Nothing to review</h3>
          <p>New requests to join this group will appear here.</p></div>
      <?php else: ?>
        <?php foreach ($requests as $person): ?>
          <div class="spread" style="padding:10px 0;border-bottom:1px solid var(--divider-soft)">
            <a class="row" href="<?= e(profile_url($person)) ?>" style="color:inherit;min-width:0">
              <img class="avatar avatar-48" src="<?= e(avatar_url($person)) ?>" alt="">
              <span style="min-width:0">
                <span class="bold truncate" style="display:block"><?= e(full_name($person)) ?></span>
                <span class="small muted">Requested <?= e(time_ago($person['joined_at'])) ?></span>
              </span>
            </a>
            <div class="row">
              <form method="post" action="/g/<?= e($group['slug']) ?>/approve/<?= (int) $person['id'] ?>">
                <?= csrf_field() ?><button class="btn btn-primary btn-sm" type="submit">Approve</button>
              </form>
              <form method="post" action="/g/<?= e($group['slug']) ?>/reject/<?= (int) $person['id'] ?>">
                <?= csrf_field() ?><button class="btn btn-sm" type="submit">Decline</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
