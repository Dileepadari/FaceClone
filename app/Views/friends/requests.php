<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/friends-nav', ['section' => $section]) ?></aside>

  <div class="column-main" style="max-width:900px">
    <h1 class="page-title">Friend requests</h1>

    <h2 class="section-title">Received (<?= count($requests) ?>)</h2>
    <?php if (!$requests): ?>
      <div class="card"><div class="empty-state">
        <?= icon('users', 40) ?><h3>No pending requests</h3>
        <p>When someone sends you a friend request it will show up here.</p>
      </div></div>
    <?php else: ?>
      <div class="people-grid mb-16">
        <?php foreach ($requests as $person): ?>
          <?= view_partial('partials/person-card', [
              'person'   => $person,
              'subtitle' => 'Requested ' . time_ago($person['requested_at']),
              'primaryAction' => '<button class="btn btn-primary btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/accept"
                    data-remove-card="1" data-toast-text="You are now friends">Confirm</button>',
              'secondaryAction' => '<button class="btn btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/decline"
                    data-remove-card="1" data-toast-text="Request declined">Delete</button>',
          ]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <hr class="divider mb-16">

    <h2 class="section-title">Sent (<?= count($sent) ?>)</h2>
    <?php if (!$sent): ?>
      <p class="muted">You have no outgoing requests.</p>
    <?php else: ?>
      <div class="people-grid">
        <?php foreach ($sent as $person): ?>
          <?= view_partial('partials/person-card', [
              'person'   => $person,
              'subtitle' => 'Sent ' . time_ago($person['requested_at']),
              'primaryAction' => '<button class="btn btn-block" type="button"
                    data-friend-action="/friends/' . (int) $person['id'] . '/cancel"
                    data-remove-card="1" data-toast-text="Request cancelled">Cancel request</button>',
          ]) ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <aside class="rail rail-right"></aside>
</div>
