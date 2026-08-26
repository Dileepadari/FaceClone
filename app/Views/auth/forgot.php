<div class="auth-hero" style="grid-template-columns:1fr;max-width:520px">
  <div class="auth-card">
    <h2 style="font-size:22px;font-weight:700">Find your account</h2>
    <p class="muted mt-8">Enter the email address linked to your account.</p>
    <hr class="auth-divider">

    <?php if (!empty($link)): ?>
      <div class="alert is-success">
        <strong>Reset link created.</strong>
        <p class="mt-8">This build has no mail transport configured, so the link is shown here instead of being emailed.</p>
        <p class="mt-8"><a href="<?= e($link) ?>">Open the reset link</a></p>
      </div>
    <?php endif; ?>

    <form method="post" action="/forgot-password">
      <?= csrf_field() ?>
      <div class="field">
        <input class="input" type="text" name="email" placeholder="Email address or username"
               value="<?= old('email') ?>" required autofocus aria-label="Email address or username">
      </div>
      <div class="row" style="justify-content:flex-end">
        <a class="btn" href="/login">Cancel</a>
        <button class="btn btn-primary" type="submit">Search</button>
      </div>
    </form>
  </div>
</div>
