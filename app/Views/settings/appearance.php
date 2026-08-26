<div class="layout-3col">
  <aside class="rail rail-left"><?= view_partial('partials/settings-nav', ['section' => $section]) ?></aside>

  <div class="column-main">
    <h1 class="page-title">Display</h1>
    <div class="card">
      <div class="card-body">
        <h2 class="section-title">Theme</h2>
        <p class="muted small mb-16">Choose how FaceClone looks. Changes apply immediately.</p>

        <form method="post" action="/settings/appearance">
          <?= csrf_field() ?>
          <?php
            $themes = [
                'light'  => ['Light', 'The standard FaceClone appearance.', 'sun'],
                'dark'   => ['Dark', 'Easier on the eyes in low light.', 'moon'],
                'system' => ['Automatic', 'Follow your device setting.', 'settings'],
            ];
          ?>
          <?php foreach ($themes as $value => [$label, $hint, $glyph]): ?>
            <label class="radio-card" style="border:1px solid var(--divider);margin-bottom:8px">
              <span class="rail-glyph"><?= icon($glyph, 20) ?></span>
              <span class="grow"><strong><?= $label ?></strong><br><span class="small muted"><?= $hint ?></span></span>
              <input type="radio" name="theme" value="<?= $value ?>" <?= $settings['theme'] === $value ? 'checked' : '' ?>>
            </label>
          <?php endforeach; ?>
          <button class="btn btn-primary mt-8" type="submit">Save theme</button>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h2 class="section-title">Accessibility</h2>
        <p class="small muted mt-8">
          FaceClone respects your system's <strong>reduce motion</strong> setting: animations and transitions are
          disabled automatically when it is on. Every interactive control is reachable by keyboard, and
          <kbd>Esc</kbd> closes any open dialog or menu.
        </p>
      </div>
    </div>
  </div>

  <aside class="rail rail-right"></aside>
</div>
