<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/groups-nav', ['section' => $section, 'myGroups' => $myGroups]) ?></aside>

  <div class="column-main" style="max-width:900px">
    <h1 class="page-title">Discover groups</h1>
    <?php if (!$discover): ?>
      <div class="card"><div class="empty-state">
        <?= icon('groups', 40) ?><h3>You have joined everything</h3>
        <p>There are no other groups to show right now.</p>
        <a class="btn btn-primary mt-16" href="/groups/create">Create a group</a>
      </div></div>
    <?php else: ?>
      <div class="tile-grid">
        <?php foreach ($discover as $group): ?>
          <div class="card-tile">
            <a class="tile-media" href="/g/<?= e($group['slug']) ?>">
              <?php if (!empty($group['cover'])): ?><img src="<?= e($group['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            </a>
            <div class="tile-body">
              <a class="bold clamp-2" href="/g/<?= e($group['slug']) ?>"><?= e($group['name']) ?></a>
              <span class="small muted">
                <?= $group['privacy'] === 'private' ? 'Private' : 'Public' ?>
                &middot; <?= number_short((int) $group['member_count']) ?> members
              </span>
              <?php if (!empty($group['description'])): ?>
                <p class="small muted clamp-2"><?= e($group['description']) ?></p>
              <?php endif; ?>
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
