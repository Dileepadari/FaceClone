<?= view_partial('partials/profile-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16"><?= e($heading) ?> <span class="muted" style="font-weight:400">(<?= count($people) ?>)</span></h2>
      <?php if (!$people): ?>
        <div class="empty-state"><?= icon('users', 40) ?><p><?= e($empty) ?></p></div>
      <?php else: ?>
        <div class="people-grid">
          <?php foreach ($people as $person): ?>
            <?= view_partial('partials/person-card', [
                'person'        => $person,
                'subtitle'      => $person['city'] ?: '@' . $person['username'],
                'primaryAction' => '<a class="btn btn-block btn-soft" href="' . e(profile_url($person)) . '">View profile</a>',
            ]) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
