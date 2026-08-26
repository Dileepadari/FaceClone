<?= view_partial('partials/group-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-16">Group settings</h2>
      <form method="post" action="/g/<?= e($group['slug']) ?>/settings">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field-label" for="name">Group name</label>
          <input class="input <?= has_error('name') ? 'has-error' : '' ?>" id="name" name="name"
                 value="<?= old('name', $group['name']) ?>" maxlength="120" required>
          <?= field_error('name') ?>
        </div>
        <div class="field">
          <label class="field-label" for="description">Description</label>
          <textarea class="textarea" id="description" name="description" rows="5"
                    maxlength="2000"><?= old('description', $group['description']) ?></textarea>
        </div>
        <div class="field">
          <span class="field-label">Privacy</span>
          <label class="radio-card" style="border:1px solid var(--divider);margin-bottom:8px">
            <input type="radio" name="privacy" value="public" <?= $group['privacy'] === 'public' ? 'checked' : '' ?>>
            <span><strong>Public</strong><br><span class="small muted">Anyone can see the posts and join instantly.</span></span>
          </label>
          <label class="radio-card" style="border:1px solid var(--divider)">
            <input type="radio" name="privacy" value="private" <?= $group['privacy'] === 'private' ? 'checked' : '' ?>>
            <span><strong>Private</strong><br><span class="small muted">Posts are members only and admins approve new joins.</span></span>
          </label>
        </div>
        <button class="btn btn-primary" type="submit">Save settings</button>
      </form>

      <hr class="divider mt-16 mb-16">

      <h2 class="section-title" style="color:var(--red)">Danger zone</h2>
      <p class="small muted mb-12">Deleting the group removes every post, photo and membership. This cannot be undone.</p>
      <form method="post" action="/g/<?= e($group['slug']) ?>/delete">
        <?= csrf_field() ?>
        <button class="btn btn-danger" type="submit"
                data-confirm="Permanently delete <?= e($group['name']) ?> and all of its posts?">
          <?= icon('trash', 16) ?> Delete group
        </button>
      </form>
    </div>
  </div>
</div>
