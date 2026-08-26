<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/friends-nav', ['section' => $section]) ?></aside>

  <div class="column-main" style="max-width:900px">
    <?php if ($requests): ?>
      <div class="spread mb-12">
        <h2 class="section-title" style="margin:0">Friend requests</h2>
        <a class="small" href="/friends/requests">See all</a>
      </div>
      <div class="people-grid mb-16">
        <?php foreach (array_slice($requests, 0, 8) as $person): ?>
          <?= view_partial('partials/person-card', [
              'person'   => $person,
              'subtitle' => time_ago($person['requested_at']),
              'primaryAction' => '<button class="btn btn-primary btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/accept"
                    data-remove-card="1" data-toast-text="Friend request accepted">Confirm</button>',
              'secondaryAction' => '<button class="btn btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/decline"
                    data-remove-card="1" data-toast-text="Request declined">Delete</button>',
          ]) ?>
        <?php endforeach; ?>
      </div>
      <hr class="divider mb-16">
    <?php endif; ?>

    <div class="spread mb-12">
      <h2 class="section-title" style="margin:0">People you may know</h2>
      <a class="small" href="/friends/suggestions">See all</a>
    </div>

    <?php if (!$suggestions): ?>
      <div class="card"><div class="empty-state">
        <?= icon('users', 40) ?>
        <h3>No suggestions right now</h3>
        <p>Once you have a few friends, we can suggest people you have friends in common with.</p>
      </div></div>
    <?php else: ?>
      <div class="people-grid">
        <?php foreach ($suggestions as $person): ?>
          <?= view_partial('partials/person-card', [
              'person'   => $person,
              'subtitle' => ((int) ($person['mutuals'] ?? 0) > 0)
                  ? $person['mutuals'] . ' mutual friends'
                  : ($person['city'] ?: '@' . $person['username']),
              'primaryAction' => '<button class="btn btn-primary btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/request"
                    data-replace-with=\'<span class="btn btn-block">Request sent</span>\'>Add friend</button>',
              'secondaryAction' => '<a class="btn btn-block" href="' . e(profile_url($person)) . '">View profile</a>',
          ]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
