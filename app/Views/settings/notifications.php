<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/settings-nav', ['section' => $section]) ?></aside>

  <div class="column-main">
    <h1 class="page-title">Notifications</h1>
    <div class="card">
      <div class="card-body">
        <p class="muted mb-12">Choose what you want to be notified about. Turning something off stops new notifications of that kind from being created.</p>
        <form method="post" action="/settings/notifications">
          <?= csrf_field() ?>
          <?php
            $toggles = [
                'notify_reactions' => ['Reactions', 'When someone reacts to your posts or comments.'],
                'notify_comments'  => ['Comments', 'When someone comments on your post or replies to you.'],
                'notify_friends'   => ['Friend requests', 'When you receive or someone accepts a friend request.'],
                'notify_messages'  => ['Messages', 'When you get a new message in Messenger.'],
                'notify_groups'    => ['Groups', 'Invitations, join requests and new posts in your groups.'],
            ];
          ?>
          <?php foreach ($toggles as $key => [$label, $hint]): ?>
            <label class="checkbox-row" style="border-bottom:1px solid var(--divider-soft)">
              <input type="checkbox" name="<?= $key ?>" value="1" <?= $settings[$key] ? 'checked' : '' ?>>
              <span><span class="label-main"><?= $label ?></span><span class="label-sub"><?= $hint ?></span></span>
            </label>
          <?php endforeach; ?>
          <button class="btn btn-primary mt-16" type="submit">Save notification settings</button>
        </form>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>
