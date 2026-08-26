<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/friends-nav', ['section' => $section]) ?></aside>

  <div class="column-main" style="max-width:900px">
    <h1 class="page-title">All friends <span class="muted" style="font-size:16px;font-weight:400">(<?= count($friends) ?>)</span></h1>
    <?php if (!$friends): ?>
      <div class="card"><div class="empty-state">
        <?= icon('users', 40) ?><h3>No friends yet</h3>
        <p>Find people you know and send them a request.</p>
        <a class="btn btn-primary mt-16" href="/friends/suggestions">See suggestions</a>
      </div></div>
    <?php else: ?>
      <div class="people-grid">
        <?php foreach ($friends as $person): ?>
          <?= view_partial('partials/person-card', [
              'person'   => $person,
              'subtitle' => $person['city'] ?: '@' . $person['username'],
              'primaryAction' => '<a class="btn btn-soft btn-block" href="/messages/new/' . (int) $person['id'] . '">Message</a>',
              'secondaryAction' => '<button class="btn btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/remove"
                    data-remove-card="1" data-confirm="Remove ' . e(full_name($person)) . ' from your friends?"
                    data-toast-text="Friend removed">Unfriend</button>',
          ]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
