<?= view_partial('partials/profile-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">Groups <span class="muted" style="font-weight:400">(<?= count($groups) ?>)</span></h2>
      <?php if (!$groups): ?>
        <div class="empty-state"><?= icon('groups', 40) ?><h3>No groups yet</h3>
          <p><?= $isSelf ? 'Join a group and it will appear here.' : e(full_name($owner)) . ' is not in any groups.' ?></p>
          <?php if ($isSelf): ?><a class="btn btn-primary mt-16" href="/groups/discover">Discover groups</a><?php endif; ?>
        </div>
      <?php else: ?>
        <div class="tile-grid">
          <?php foreach ($groups as $group): ?>
            <a class="card-tile" href="/g/<?= e($group['slug']) ?>" style="color:inherit">
              <div class="tile-media">
                <?php if (!empty($group['cover'])): ?>
                  <img src="<?= e($group['cover']) ?>" alt="" loading="lazy">
                <?php endif; ?>
              </div>
              <div class="tile-body">
                <strong class="clamp-2"><?= e($group['name']) ?></strong>
                <span class="small muted">
                  <?= $group['privacy'] === 'private' ? 'Private' : 'Public' ?> group
                  &middot; <?= number_short((int) $group['member_count']) ?> members
                </span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
