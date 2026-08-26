<div class="auth-hero" style="grid-template-columns:1fr;max-width:520px">
  <div class="auth-card">
    <h2 style="font-size:22px;font-weight:700">Choose a new password</h2>
    <p class="muted mt-8">Pick something at least 8 characters long that you do not use elsewhere.</p>
    <hr class="auth-divider">

    <form method="post" action="/reset-password">
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <div class="field">
        <input class="input" type="password" name="password" placeholder="New password"
               autocomplete="new-password" required autofocus aria-label="New password">
      </div>
      <div class="field">
        <input class="input" type="password" name="password_confirm" placeholder="Confirm new password"
               autocomplete="new-password" required aria-label="Confirm new password">
      </div>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Change password</button>
    </form>
  </div>
</div>
