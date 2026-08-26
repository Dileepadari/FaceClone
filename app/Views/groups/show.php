<?= view_partial('partials/group-header', get_defined_vars()) ?>

<div class="profile-layout">
  <div class="profile-side">
    <div class="card">
      <div class="card-body">
        <h2 class="section-title">About</h2>
        <p class="small"><?= nl2br(e($group['description'] ?: 'No description yet.')) ?></p>
        <hr class="divider mt-12 mb-12">
        <div class="intro-row">
          <?= icon($group['privacy'] === 'private' ? 'lock' : 'globe', 18) ?>
          <span><strong><?= $group['privacy'] === 'private' ? 'Private' : 'Public' ?></strong><br>
            <span class="small muted">
              <?= $group['privacy'] === 'private'
                  ? 'Only members can see who is in the group and what they post.'
                  : 'Anyone can see who is in the group and what they post.' ?>
            </span></span>
        </div>
        <div class="intro-row"><?= icon('calendar', 18) ?>
          <span>Created <?= date('j F Y', strtotime($group['created_at'])) ?></span></div>
      </div>
    </div>

    <?php if ($canRead && $members): ?>
      <div class="card">
        <div class="card-body">
          <div class="spread mb-12">
            <h2 class="section-title" style="margin:0">Members</h2>
            <a class="small" href="/g/<?= e($group['slug']) ?>/members">See all</a>
          </div>
          <p class="muted small mb-12"><?= number_short((int) $memberCount) ?> members</p>
          <div class="photo-grid">
            <?php foreach ($members as $member): ?>
              <a href="<?= e(profile_url($member)) ?>" style="border-radius:8px;overflow:hidden" title="<?= e(full_name($member)) ?>">
                <img src="<?= e(avatar_url($member)) ?>" alt="<?= e(full_name($member)) ?>" loading="lazy">
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <div>
    <?php if (!$canRead): ?>
      <div class="card"><div class="empty-state">
        <?= icon('lock', 40) ?>
        <h3>This group is private</h3>
        <p>Join the group to see what the members are posting.</p>
      </div></div>
    <?php else: ?>
      <?php if ($isMember): ?>
        <?= view_partial('partials/composer', [
            '__user'      => $viewer,
            'groupId'     => (int) $group['id'],
            'placeholder' => 'Write something to the group…',
        ]) ?>
      <?php endif; ?>

      <?php if (!$posts): ?>
        <div class="card"><div class="empty-state">
          <?= icon('groups', 40) ?><h3>No posts yet</h3>
          <p><?= $isMember ? 'Be the first to post in this group.' : 'Join the group to start the conversation.' ?></p>
        </div></div>
      <?php else: ?>
        <?php foreach ($posts as $post): ?>
          <?= view_partial('partials/post-card', ['post' => $post, '__user' => $viewer]) ?>
        <?php endforeach; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
