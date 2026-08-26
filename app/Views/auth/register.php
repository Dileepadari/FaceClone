<div class="auth-hero">
  <div class="auth-pitch">
    <div class="auth-brand">
      <span class="brand-mark"><img class="logo-mono" src="<?= asset('assets/img/logo-mark.png') ?>" alt=""></span>
      <h1>FaceClone</h1>
    </div>
    <p>Create an account to share posts, stories and photos with the people in your life.</p>
  </div>

  <div class="auth-card">
    <div class="center mb-16">
      <h2 style="font-size:28px;font-weight:700">Sign up</h2>
      <p class="muted">It is quick and easy.</p>
    </div>
    <hr class="auth-divider" style="margin-top:0">

    <form method="post" action="/register">
      <?= csrf_field() ?>

      <div class="field-row">
        <div class="field">
          <input class="input <?= has_error('first_name') ? 'has-error' : '' ?>" type="text" name="first_name"
                 placeholder="First name" value="<?= old('first_name') ?>" required autofocus aria-label="First name">
          <?= field_error('first_name') ?>
        </div>
        <div class="field">
          <input class="input <?= has_error('last_name') ? 'has-error' : '' ?>" type="text" name="last_name"
                 placeholder="Surname" value="<?= old('last_name') ?>" required aria-label="Surname">
          <?= field_error('last_name') ?>
        </div>
      </div>

      <div class="field">
        <input class="input <?= has_error('email') ? 'has-error' : '' ?>" type="email" name="email"
               placeholder="Email address" value="<?= old('email') ?>" autocomplete="email" required aria-label="Email address">
        <?= field_error('email') ?>
      </div>

      <div class="field">
        <input class="input <?= has_error('password') ? 'has-error' : '' ?>" type="password" name="password"
               placeholder="New password (at least 8 characters)" autocomplete="new-password" required aria-label="New password">
        <?= field_error('password') ?>
      </div>

      <div class="field">
        <input class="input <?= has_error('password_confirm') ? 'has-error' : '' ?>" type="password"
               name="password_confirm" placeholder="Confirm password" autocomplete="new-password" required
               aria-label="Confirm password">
        <?= field_error('password_confirm') ?>
      </div>

      <div class="field">
        <label class="field-label" for="dob">Date of birth</label>
        <input class="input <?= has_error('dob') ? 'has-error' : '' ?>" type="date" id="dob" name="dob"
               value="<?= old('dob') ?>" max="<?= date('Y-m-d', strtotime('-13 years')) ?>" required>
        <?= field_error('dob') ?>
      </div>

      <div class="field">
        <span class="field-label">Gender</span>
        <div class="field-row" style="grid-template-columns:repeat(3,1fr)">
          <?php foreach (['female' => 'Female', 'male' => 'Male', 'custom' => 'Custom'] as $value => $label): ?>
            <label class="radio-card" style="border:1px solid var(--divider);justify-content:space-between">
              <span><?= $label ?></span>
              <input type="radio" name="gender" value="<?= $value ?>" <?= old('gender', 'custom') === $value ? 'checked' : '' ?>>
            </label>
          <?php endforeach; ?>
        </div>
        <?= field_error('gender') ?>
      </div>

      <p class="tiny muted">
        By clicking Sign Up you agree to the FaceClone Terms and Privacy Policy. This is a demo application.
      </p>

      <div class="center mt-16">
        <button class="btn btn-lg" style="background:#42b72a;color:#fff;padding:0 32px" type="submit">Sign up</button>
      </div>

      <p class="center mt-16"><a href="/login">Already have an account?</a></p>
    </form>
  </div>
</div>
