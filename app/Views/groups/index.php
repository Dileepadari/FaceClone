<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/groups-nav', ['section' => $section, 'myGroups' => $myGroups]) ?></aside>

  <div class="column-main" style="max-width:900px">
    <?php if ($invites): ?>
      <h2 class="section-title">Invitations</h2>
      <div class="tile-grid mb-16">
        <?php foreach ($invites as $group): ?>
          <div class="card-tile">
            <a class="tile-media" href="/g/<?= e($group['slug']) ?>">
              <?php if (!empty($group['cover'])): ?><img src="<?= e($group['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            </a>
            <div class="tile-body">
              <a class="bold" href="/g/<?= e($group['slug']) ?>"><?= e($group['name']) ?></a>
              <span class="small muted"><?= number_short((int) $group['member_count']) ?> members</span>
              <form method="post" action="/g/<?= e($group['slug']) ?>/join" class="mt-8">
                <?= csrf_field() ?>
                <button class="btn btn-primary btn-block" type="submit">Accept invitation</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <hr class="divider mb-16">
    <?php endif; ?>

    <div class="spread mb-12">
      <h1 class="page-title" style="margin:0">Your groups</h1>
      <a class="btn btn-soft btn-sm" href="/groups/create"><?= icon('plus', 14) ?> Create</a>
    </div>

    <?php if (!$myGroups): ?>
      <div class="card"><div class="empty-state">
        <?= icon('groups', 40) ?><h3>You have not joined any groups</h3>
        <p>Groups are where people with a shared interest post together.</p>
        <div class="row mt-16" style="justify-content:center">
          <a class="btn btn-primary" href="/groups/discover">Discover groups</a>
          <a class="btn" href="/groups/create">Create your own</a>
        </div>
      </div></div>
    <?php else: ?>
      <div class="tile-grid mb-16">
        <?php foreach ($myGroups as $group): ?>
          <a class="card-tile" href="/g/<?= e($group['slug']) ?>" style="color:inherit">
            <div class="tile-media">
              <?php if (!empty($group['cover'])): ?><img src="<?= e($group['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            </div>
            <div class="tile-body">
              <strong class="clamp-2"><?= e($group['name']) ?></strong>
              <span class="small muted">
                <?= number_short((int) $group['member_count']) ?> members
                <?php if ($group['role'] !== 'member'): ?>&middot; You are <?= e($group['role']) ?><?php endif; ?>
              </span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($discover): ?>
      <hr class="divider mb-16">
      <div class="spread mb-12">
        <h2 class="section-title" style="margin:0">Suggested for you</h2>
        <a class="small" href="/groups/discover">See all</a>
      </div>
      <div class="tile-grid">
        <?php foreach (array_slice($discover, 0, 4) as $group): ?>
          <div class="card-tile">
            <a class="tile-media" href="/g/<?= e($group['slug']) ?>">
              <?php if (!empty($group['cover'])): ?><img src="<?= e($group['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            </a>
            <div class="tile-body">
              <a class="bold clamp-2" href="/g/<?= e($group['slug']) ?>"><?= e($group['name']) ?></a>
              <span class="small muted"><?= number_short((int) $group['member_count']) ?> members</span>
              <form method="post" action="/g/<?= e($group['slug']) ?>/join" class="mt-8">
                <?= csrf_field() ?>
                <button class="btn btn-soft btn-block" type="submit">
                  <?= $group['privacy'] === 'private' ? 'Request to join' : 'Join group' ?>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
