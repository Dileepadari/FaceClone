<?= view_partial('partials/profile-header', get_defined_vars()) ?>

<div class="layout-narrow">
  <div class="card">
    <div class="card-body">
      <h2 class="section-title">About <?= e(full_name($owner)) ?></h2>

      <?php if ($isSelf): ?>
        <form method="post" action="/profile/details" class="mt-16">
          <?= csrf_field() ?>
          <div class="field-row">
            <div class="field">
              <label class="field-label" for="first_name">First name</label>
              <input class="input <?= has_error('first_name') ? 'has-error' : '' ?>" id="first_name"
                     name="first_name" value="<?= old('first_name', $owner['first_name']) ?>" required>
              <?= field_error('first_name') ?>
            </div>
            <div class="field">
              <label class="field-label" for="last_name">Surname</label>
              <input class="input <?= has_error('last_name') ? 'has-error' : '' ?>" id="last_name"
                     name="last_name" value="<?= old('last_name', $owner['last_name']) ?>" required>
              <?= field_error('last_name') ?>
            </div>
          </div>

          <div class="field">
            <label class="field-label" for="username">Username</label>
            <input class="input <?= has_error('username') ? 'has-error' : '' ?>" id="username"
                   name="username" value="<?= old('username', $owner['username']) ?>" required>
            <p class="field-hint">Your profile lives at /u/<?= e($owner['username']) ?></p>
            <?= field_error('username') ?>
          </div>

          <div class="field-row">
            <div class="field">
              <label class="field-label" for="dob">Date of birth</label>
              <input class="input" type="date" id="dob" name="dob" value="<?= e($owner['dob']) ?>">
            </div>
            <div class="field">
              <label class="field-label" for="gender">Gender</label>
              <select class="select" id="gender" name="gender">
                <?php foreach (['female' => 'Female', 'male' => 'Male', 'custom' => 'Custom'] as $v => $label): ?>
                  <option value="<?= $v ?>" <?= $owner['gender'] === $v ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <button class="btn btn-primary" type="submit">Save profile</button>
        </form>

        <hr class="divider mt-16 mb-16">
        <h2 class="section-title">Details</h2>
        <?= view_partial('partials/profile-intro', ['owner' => $owner, 'isSelf' => true]) ?>

      <?php else: ?>
        <?php
          $rows = array_filter([
              'Bio'          => $owner['bio'],
              'Work'         => $owner['work'],
              'Education'    => $owner['education'],
              'Lives in'     => $owner['city'],
              'From'         => $owner['hometown'],
              'Relationship' => $owner['relationship'] === 'private' ? null : ucfirst(str_replace('_', ' ', $owner['relationship'])),
              'Website'      => $owner['website'],
              'Joined'       => date('F Y', strtotime($owner['created_at'])),
          ]);
        ?>
        <?php foreach ($rows as $label => $value): ?>
          <div class="spread" style="padding:12px 0;border-bottom:1px solid var(--divider-soft)">
            <span class="muted"><?= e($label) ?></span>
            <span class="bold" style="text-align:right"><?= e($value) ?></span>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
