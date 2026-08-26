<div class="layout-narrow" style="max-width:860px">
  <div class="card">
    <?php if (!empty($event['cover'])): ?>
      <div style="aspect-ratio:24/10;background:var(--surface-2)">
        <img src="<?= e($event['cover']) ?>" alt="" style="width:100%;height:100%;object-fit:cover">
      </div>
    <?php endif; ?>

    <div class="card-body">
      <p class="bold" style="color:var(--red);text-transform:uppercase">
        <?= date('l, j F Y \a\t g:i A', strtotime($event['starts_at'])) ?>
      </p>
      <h1 style="font-size:28px;font-weight:700;margin-top:4px"><?= e($event['title']) ?></h1>

      <?php if (!empty($event['location'])): ?>
        <div class="intro-row mt-12"><?= icon('location', 20) ?> <span><?= e($event['location']) ?></span></div>
      <?php endif; ?>
      <div class="intro-row"><?= icon('users', 20) ?>
        <span><?= (int) $event['going_count'] ?> going &middot; <?= (int) $event['interested_count'] ?> interested</span></div>
      <div class="intro-row">
        <img class="avatar avatar-28" src="<?= e(avatar_url($host)) ?>" alt="">
        <span>Hosted by <a href="<?= e(profile_url($host)) ?>"><?= e(full_name($host)) ?></a></span>
      </div>

      <div class="row mt-16" style="flex-wrap:wrap">
        <?php foreach (['going' => 'Going', 'interested' => 'Interested'] as $status => $label): ?>
          <form method="post" action="/events/<?= (int) $event['id'] ?>/rsvp">
            <?= csrf_field() ?>
            <input type="hidden" name="status" value="<?= $event['my_status'] === $status ? 'none' : $status ?>">
            <button class="btn <?= $event['my_status'] === $status ? 'btn-primary' : '' ?>" type="submit">
              <?= $event['my_status'] === $status ? icon('check', 16) : '' ?> <?= $label ?>
            </button>
          </form>
        <?php endforeach; ?>
        <?php if ($isHost): ?>
          <form method="post" action="/events/<?= (int) $event['id'] ?>/delete">
            <?= csrf_field() ?>
            <button class="btn btn-danger" type="submit" data-confirm="Delete this event?">
              <?= icon('trash', 16) ?> Delete event
            </button>
          </form>
        <?php endif; ?>
      </div>

      <?php if (!empty($event['description'])): ?>
        <hr class="divider mt-16 mb-16">
        <h2 class="section-title">Details</h2>
        <p><?= nl2br(e($event['description'])) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php foreach ([['Going', $going], ['Interested', $interested]] as [$label, $people]): ?>
    <?php if ($people): ?>
      <div class="card">
        <div class="card-body">
          <h2 class="section-title mb-12"><?= $label ?> (<?= count($people) ?>)</h2>
          <div class="people-grid">
            <?php foreach ($people as $person): ?>
              <?= view_partial('partials/person-card', [
                  'person'        => $person,
                  'subtitle'      => $person['city'] ?: '@' . $person['username'],
                  'primaryAction' => '<a class="btn btn-block btn-soft" href="' . e(profile_url($person)) . '">View profile</a>',
              ]) ?>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>
