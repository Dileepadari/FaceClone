<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/settings-nav', ['section' => $section]) ?></aside>

  <div class="column-main">
    <h1 class="page-title">Privacy</h1>
    <div class="card">
      <div class="card-body">
        <form method="post" action="/settings/privacy">
          <?= csrf_field() ?>

          <div class="field">
            <label class="field-label" for="default_privacy">Who can see your future posts?</label>
            <select class="select" id="default_privacy" name="default_privacy">
              <?php foreach (['public' => 'Public', 'friends' => 'Friends', 'only_me' => 'Only me'] as $v => $label): ?>
                <option value="<?= $v ?>" <?= $settings['default_privacy'] === $v ? 'selected' : '' ?>><?= $label ?></option>
              <?php endforeach; ?>
            </select>
            <p class="field-hint">This is the audience preselected in the composer. You can change it per post.</p>
          </div>

          <div class="field">
            <label class="field-label" for="who_can_friend">Who can send you friend requests?</label>
            <select class="select" id="who_can_friend" name="who_can_friend">
              <option value="everyone" <?= $settings['who_can_friend'] === 'everyone' ? 'selected' : '' ?>>Everyone</option>
              <option value="friends_of_friends" <?= $settings['who_can_friend'] === 'friends_of_friends' ? 'selected' : '' ?>>Friends of friends</option>
            </select>
          </div>

          <div class="field">
            <label class="field-label" for="who_can_message">Who can message you?</label>
            <select class="select" id="who_can_message" name="who_can_message">
              <option value="everyone" <?= $settings['who_can_message'] === 'everyone' ? 'selected' : '' ?>>Everyone</option>
              <option value="friends" <?= $settings['who_can_message'] === 'friends' ? 'selected' : '' ?>>Friends only</option>
            </select>
          </div>

          <hr class="divider mt-16 mb-8">

          <label class="checkbox-row">
            <input type="checkbox" name="show_online" value="1" <?= $settings['show_online'] ? 'checked' : '' ?>>
            <span><span class="label-main">Show when you are active</span>
              <span class="label-sub">Friends can see a green dot next to your photo when you are online.</span></span>
          </label>

          <button class="btn btn-primary mt-16" type="submit">Save privacy settings</button>
        </form>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>
