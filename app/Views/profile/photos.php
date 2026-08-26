<?= view_partial('partials/profile-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">Photos <span class="muted" style="font-weight:400">(<?= count($photos) ?>)</span></h2>
      <?php if (!$photos): ?>
        <div class="empty-state"><?= icon('photo', 40) ?><h3>No photos yet</h3>
          <p>Photos attached to posts show up here.</p></div>
      <?php else: ?>
        <div class="photo-grid cols-5">
          <?php foreach ($photos as $photo): ?>
            <a href="<?= e($photo['path']) ?>" data-lightbox="<?= e($photo['path']) ?>"
               title="Posted <?= e(time_ago($photo['created_at'])) ?>">
              <img src="<?= e($photo['path']) ?>" alt="Photo" loading="lazy">
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
