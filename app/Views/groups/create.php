<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/groups-nav', ['section' => $section, 'myGroups' => $myGroups]) ?></aside>

  <div class="column-main">
    <div class="card">
      <div class="card-body">
        <h1 class="page-title">Create a group</h1>
        <form method="post" action="/groups" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="field">
            <label class="field-label" for="name">Group name</label>
            <input class="input <?= has_error('name') ? 'has-error' : '' ?>" id="name" name="name"
                   value="<?= old('name') ?>" maxlength="120" required autofocus>
            <?= field_error('name') ?>
          </div>
          <div class="field">
            <label class="field-label" for="description">What is this group about?</label>
            <textarea class="textarea" id="description" name="description" rows="4"
                      maxlength="2000" placeholder="Tell people what to expect"><?= old('description') ?></textarea>
            <?= field_error('description') ?>
          </div>
          <div class="field">
            <span class="field-label">Privacy</span>
            <label class="radio-card" style="border:1px solid var(--divider);margin-bottom:8px">
              <input type="radio" name="privacy" value="public" checked>
              <span><strong>Public</strong><br><span class="small muted">Anyone can see who is in the group and what they post.</span></span>
            </label>
            <label class="radio-card" style="border:1px solid var(--divider)">
              <input type="radio" name="privacy" value="private">
              <span><strong>Private</strong><br><span class="small muted">Only members can see posts, and admins approve who joins.</span></span>
            </label>
          </div>
          <div class="field">
            <label class="field-label" for="cover">Cover photo (optional)</label>
            <input class="input" type="file" id="cover" name="cover" accept="image/*"
                   data-file-preview="#group-cover-preview">
            <div id="group-cover-preview" class="hidden mt-8"></div>
          </div>
          <button class="btn btn-primary btn-lg" type="submit">Create group</button>
        </form>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>
