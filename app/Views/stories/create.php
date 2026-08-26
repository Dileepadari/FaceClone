<div class="layout-narrow" style="max-width:720px">
  <h1 class="page-title">Create a story</h1>
  <p class="muted mb-16">Stories are visible to your friends and disappear after 24 hours.</p>

  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-12"><?= icon('photo', 20) ?> Photo story</h2>
      <form method="post" action="/stories" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="photo">
        <div class="field">
          <input class="input" type="file" name="media" accept="image/*" required
                 data-file-preview="#story-photo-preview">
          <div id="story-photo-preview" class="hidden mt-12"></div>
        </div>
        <button class="btn btn-primary" type="submit">Share photo story</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <h2 class="section-title mb-12"><?= icon('emoji', 20) ?> Text story</h2>
      <form method="post" action="/stories" data-composer>
        <?= csrf_field() ?>
        <input type="hidden" name="type" value="text">
        <input type="hidden" name="background" value="bg1">

        <textarea class="composer-textarea has-bg" name="text" data-composer-text maxlength="500"
                  style="background:linear-gradient(135deg,#7b4dff 0%,#e356a7 100%);min-height:220px"
                  placeholder="What is on your mind?" aria-label="Story text" required></textarea>

        <div class="bg-picker" data-bg-picker>
          <?php foreach (post_backgrounds() as $key => $css): ?>
            <button class="bg-swatch<?= $key === 'bg1' ? ' is-active' : '' ?>" type="button"
                    data-bg="<?= e($key) ?>" data-css="<?= e($css) ?>" style="background:<?= e($css) ?>"
                    aria-label="Background <?= e($key) ?>"></button>
          <?php endforeach; ?>
        </div>

        <button class="btn btn-primary mt-12" type="submit" data-composer-submit>Share text story</button>
      </form>
    </div>
  </div>
</div>
