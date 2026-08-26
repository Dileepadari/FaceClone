<div class="layout-3col">
  <aside class="rail rail-left">
    <div class="rail-title" style="font-size:24px;font-weight:700;color:var(--text)">Events</div>
    <a class="rail-link is-active" href="/events"><span class="rail-glyph"><?= icon('calendar', 20) ?></span> Upcoming</a>
    <a class="rail-link" href="/events/create"><span class="rail-glyph"><?= icon('plus', 20) ?></span> Create event</a>
    <?php if ($hosting): ?>
      <hr class="rail-sep">
      <div class="rail-title">Hosted by you</div>
      <?php foreach ($hosting as $event): ?>
        <a class="rail-link" href="/events/<?= (int) $event['id'] ?>">
          <span class="event-date">
            <span class="m"><?= date('M', strtotime($event['starts_at'])) ?></span>
            <span class="d"><?= date('j', strtotime($event['starts_at'])) ?></span>
          </span>
          <span class="truncate"><?= e($event['title']) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </aside>

  <div class="column-main" style="max-width:900px">
    <div class="spread mb-16">
      <h1 class="page-title" style="margin:0">Upcoming events</h1>
      <a class="btn btn-primary" href="/events/create"><?= icon('plus', 16) ?> Create event</a>
    </div>

    <?php if (!$upcoming): ?>
      <div class="card"><div class="empty-state">
        <?= icon('calendar', 40) ?><h3>No upcoming events</h3>
        <p>Create one and invite your friends.</p>
        <a class="btn btn-primary mt-16" href="/events/create">Create an event</a>
      </div></div>
    <?php else: ?>
      <div class="tile-grid mb-16">
        <?php foreach ($upcoming as $event): ?>
          <div class="card-tile">
            <a class="tile-media" href="/events/<?= (int) $event['id'] ?>">
              <?php if (!empty($event['cover'])): ?><img src="<?= e($event['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            </a>
            <div class="tile-body">
              <span class="small" style="color:var(--red);font-weight:700;text-transform:uppercase">
                <?= date('D, j M \a\t g:i A', strtotime($event['starts_at'])) ?>
              </span>
              <a class="bold clamp-2" href="/events/<?= (int) $event['id'] ?>"><?= e($event['title']) ?></a>
              <?php if (!empty($event['location'])): ?>
                <span class="small muted truncate"><?= icon('location', 12, 'text-icon') ?><?= e($event['location']) ?></span>
              <?php endif; ?>
              <span class="small muted"><?= (int) $event['going_count'] ?> going &middot; <?= (int) $event['interested_count'] ?> interested</span>
              <form method="post" action="/events/<?= (int) $event['id'] ?>/rsvp" class="mt-8">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= $event['my_status'] === 'going' ? 'none' : 'going' ?>">
                <button class="btn btn-block <?= $event['my_status'] === 'going' ? 'btn-soft' : 'btn-primary' ?>" type="submit">
                  <?= $event['my_status'] === 'going' ? 'Going' : 'Join event' ?>
                </button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($past): ?>
      <hr class="divider mb-16">
      <h2 class="section-title mb-12">Past events</h2>
      <div class="tile-grid">
        <?php foreach ($past as $event): ?>
          <a class="card-tile" href="/events/<?= (int) $event['id'] ?>" style="color:inherit;opacity:.75">
            <div class="tile-media">
              <?php if (!empty($event['cover'])): ?><img src="<?= e($event['cover']) ?>" alt="" loading="lazy"><?php endif; ?>
            </div>
            <div class="tile-body">
              <span class="small muted"><?= date('j M Y', strtotime($event['starts_at'])) ?></span>
              <strong class="clamp-2"><?= e($event['title']) ?></strong>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
