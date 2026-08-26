<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/settings-nav', ['section' => $section]) ?></aside>

  <div class="column-main">
    <h1 class="page-title">Account</h1>

    <div class="card">
      <div class="card-body">
        <h2 class="section-title mb-16">Your details</h2>
        <form method="post" action="/settings/account">
          <?= csrf_field() ?>
          <div class="field-row">
            <div class="field">
              <label class="field-label" for="first_name">First name</label>
              <input class="input <?= has_error('first_name') ? 'has-error' : '' ?>" id="first_name"
                     name="first_name" value="<?= old('first_name', $__user['first_name']) ?>" required>
              <?= field_error('first_name') ?>
            </div>
            <div class="field">
              <label class="field-label" for="last_name">Surname</label>
              <input class="input <?= has_error('last_name') ? 'has-error' : '' ?>" id="last_name"
                     name="last_name" value="<?= old('last_name', $__user['last_name']) ?>" required>
              <?= field_error('last_name') ?>
            </div>
          </div>
          <div class="field">
            <label class="field-label" for="username">Username</label>
            <input class="input <?= has_error('username') ? 'has-error' : '' ?>" id="username"
                   name="username" value="<?= old('username', $__user['username']) ?>" required>
            <p class="field-hint">Your profile URL is /u/<?= e($__user['username']) ?></p>
            <?= field_error('username') ?>
          </div>
          <div class="field">
            <label class="field-label" for="email">Email address</label>
            <input class="input <?= has_error('email') ? 'has-error' : '' ?>" type="email" id="email"
                   name="email" value="<?= old('email', $__user['email']) ?>" required>
            <?= field_error('email') ?>
          </div>
          <button class="btn btn-primary" type="submit">Save changes</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="section-title mb-16">Change password</h2>
        <form method="post" action="/settings/password">
          <?= csrf_field() ?>
          <div class="field">
            <label class="field-label" for="current_password">Current password</label>
            <input class="input <?= has_error('current_password') ? 'has-error' : '' ?>" type="password"
                   id="current_password" name="current_password" autocomplete="current-password" required>
            <?= field_error('current_password') ?>
          </div>
          <div class="field-row">
            <div class="field">
              <label class="field-label" for="password">New password</label>
              <input class="input <?= has_error('password') ? 'has-error' : '' ?>" type="password"
                     id="password" name="password" autocomplete="new-password" required>
              <?= field_error('password') ?>
            </div>
            <div class="field">
              <label class="field-label" for="password_confirm">Confirm new password</label>
              <input class="input <?= has_error('password_confirm') ? 'has-error' : '' ?>" type="password"
                     id="password_confirm" name="password_confirm" autocomplete="new-password" required>
              <?= field_error('password_confirm') ?>
            </div>
          </div>
          <button class="btn btn-primary" type="submit">Update password</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="section-title" style="color:var(--red)">Deactivate or delete</h2>
        <p class="small muted mt-8 mb-16">
          Deactivating hides your profile and posts until you log in again. Deleting removes everything permanently.
        </p>
        <div class="row">
          <button class="btn" type="button" data-modal-open="deactivate-modal">Deactivate account</button>
          <button class="btn btn-danger" type="button" data-modal-open="delete-modal">Delete account</button>
        </div>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>

<div class="modal-backdrop" id="deactivate-modal" role="dialog" aria-modal="true" aria-label="Deactivate account">
  <div class="modal">
    <form method="post" action="/settings/deactivate">
      <?= csrf_field() ?>
      <div class="modal-head"><h2>Deactivate account</h2>
        <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button></div>
      <div class="modal-body">
        <p>Your profile, posts and photos will be hidden. Log in again at any time to restore everything exactly as it was.</p>
        <div class="field mt-16">
          <label class="field-label" for="deactivate-password">Confirm your password</label>
          <input class="input" type="password" id="deactivate-password" name="password" required>
        </div>
      </div>
      <div class="modal-foot"><button class="btn btn-block" type="submit">Deactivate my account</button></div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="delete-modal" role="dialog" aria-modal="true" aria-label="Delete account">
  <div class="modal">
    <form method="post" action="/settings/delete">
      <?= csrf_field() ?>
      <div class="modal-head"><h2>Delete account</h2>
        <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button></div>
      <div class="modal-body">
        <div class="alert is-error">This permanently removes your posts, photos, comments, messages and groups. It cannot be undone.</div>
        <div class="field">
          <label class="field-label" for="delete-password">Confirm your password</label>
          <input class="input" type="password" id="delete-password" name="password" required>
        </div>
        <div class="field">
          <label class="field-label" for="delete-confirm">Type DELETE to confirm</label>
          <input class="input" id="delete-confirm" name="confirm" placeholder="DELETE" required>
        </div>
      </div>
      <div class="modal-foot"><button class="btn btn-danger btn-block" type="submit">Permanently delete my account</button></div>
    </form>
  </div>
</div>
