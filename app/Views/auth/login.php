<div class="auth-hero">
  <div class="auth-pitch">
    <div class="auth-brand">
      <span class="brand-mark"><img class="logo-mono" src="<?= asset('assets/img/logo-mark.png') ?>" alt=""></span>
      <h1>FaceClone</h1>
    </div>
    <p>Connect with friends and the world around you on FaceClone.</p>
  </div>

  <div>
    <div class="auth-card">
      <form method="post" action="/login">
        <?= csrf_field() ?>
        <div class="field">
          <input class="input <?= has_error('email') ? 'has-error' : '' ?>" type="text" name="email"
                 placeholder="Email address or username" value="<?= old('email') ?>"
                 autocomplete="username" required autofocus aria-label="Email address or username">
          <?= field_error('email') ?>
        </div>
        <div class="field">
          <input class="input <?= has_error('password') ? 'has-error' : '' ?>" type="password" name="password"
                 placeholder="Password" autocomplete="current-password" required aria-label="Password">
          <?= field_error('password') ?>
        </div>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Log in</button>

        <label class="checkbox-row" style="justify-content:center;padding-bottom:0">
          <input type="checkbox" name="remember" value="1">
          <span class="small">Keep me logged in for 30 days</span>
        </label>

        <p class="center mt-8"><a href="/forgot-password">Forgotten password?</a></p>
        <hr class="auth-divider">
        <p class="center">
          <a class="btn btn-block btn-lg" style="background:#42b72a;color:#fff" href="/register">Create new account</a>
        </p>
      </form>
    </div>
    <p class="center mt-16 small"><strong>Create a Page</strong> for a celebrity, brand or business.</p>
  </div>
</div>
