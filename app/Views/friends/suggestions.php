<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/friends-nav', ['section' => $section]) ?></aside>

  <div class="column-main" style="max-width:900px">
    <h1 class="page-title">People you may know</h1>
    <?php if (!$suggestions): ?>
      <div class="card"><div class="empty-state">
        <?= icon('users', 40) ?><h3>Nothing to suggest yet</h3>
        <p>Add a few friends and we can work out who else you might know.</p>
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
