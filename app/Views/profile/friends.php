<?= view_partial('partials/profile-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <div class="spread mb-16">
        <h2 class="section-title" style="margin:0">Friends <span class="muted" style="font-weight:400">(<?= (int) $friendCount ?>)</span></h2>
        <div class="row">
          <a class="chip" href="<?= e(profile_url($owner)) ?>/followers">Followers</a>
          <a class="chip" href="<?= e(profile_url($owner)) ?>/following">Following</a>
        </div>
      </div>

      <?php if (!$friends): ?>
        <div class="empty-state"><?= icon('users', 40) ?><h3>No friends yet</h3>
          <p><?= e(full_name($owner)) ?> has not added anyone yet.</p></div>
      <?php else: ?>
        <div class="people-grid">
          <?php foreach ($friends as $friend): ?>
            <?php
              $isMe   = (int) $friend['id'] === (int) $viewer['id'];
              $action = $isMe
                ? '<a class="btn btn-block" href="' . e(profile_url($friend)) . '">Your profile</a>'
                : '<a class="btn btn-block btn-soft" href="/messages/new/' . (int) $friend['id'] . '">Message</a>';
            ?>
            <?= view_partial('partials/person-card', [
                'person'         => $friend,
                'subtitle'       => $friend['city'] ?: '@' . $friend['username'],
                'primaryAction'  => $action,
            ]) ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
