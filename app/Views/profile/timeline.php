<?= view_partial('partials/profile-header', get_defined_vars()) ?>

<div class="profile-layout">
  <div class="profile-side">
    <?= view_partial('partials/profile-intro', ['owner' => $owner, 'isSelf' => $isSelf]) ?>

    <div class="card">
      <div class="card-body">
        <div class="spread mb-12">
          <h2 class="section-title" style="margin:0">Photos</h2>
          <a class="small" href="<?= e(profile_url($owner)) ?>/photos">See all photos</a>
        </div>
        <?php if (!$photos): ?>
          <p class="muted small">No photos yet.</p>
        <?php else: ?>
          <div class="photo-grid">
            <?php foreach (array_slice($photos, 0, 9) as $photo): ?>
              <a href="<?= e($photo['path']) ?>" data-lightbox="<?= e($photo['path']) ?>">
                <img src="<?= e($photo['path']) ?>" alt="Photo" loading="lazy">
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <div class="spread mb-12">
          <h2 class="section-title" style="margin:0">Friends</h2>
          <a class="small" href="<?= e(profile_url($owner)) ?>/friends">See all friends</a>
        </div>
        <p class="muted small mb-12"><?= number_short((int) $friendCount) ?> friends</p>
        <?php if (!$friends): ?>
          <p class="muted small">No friends yet.</p>
        <?php else: ?>
          <div class="photo-grid">
            <?php foreach ($friends as $friend): ?>
              <a href="<?= e(profile_url($friend)) ?>" style="border-radius:8px;overflow:hidden" title="<?= e(full_name($friend)) ?>">
                <img src="<?= e(avatar_url($friend)) ?>" alt="<?= e(full_name($friend)) ?>" loading="lazy">
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <?php if ($isSelf): ?>
      <?= view_partial('partials/composer', ['__user' => $viewer]) ?>
    <?php endif; ?>

    <?php if (!$posts): ?>
      <div class="card"><div class="empty-state">
        <?= icon('edit', 40) ?>
        <h3>No posts to show</h3>
        <p><?= $isSelf ? 'Share your first post and it will appear here.' : e(full_name($owner)) . ' has not posted anything you can see.' ?></p>
      </div></div>
    <?php else: ?>
      <?php foreach ($posts as $post): ?>
        <?= view_partial('partials/post-card', ['post' => $post, '__user' => $viewer]) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
