<div class="layout-narrow" style="max-width:700px">
  <div class="card">
    <div class="card-body">
      <h1 class="page-title">Create an event</h1>
      <form method="post" action="/events" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field-label" for="title">Event name</label>
          <input class="input <?= has_error('title') ? 'has-error' : '' ?>" id="title" name="title"
                 value="<?= old('title') ?>" maxlength="150" required autofocus>
          <?= field_error('title') ?>
        </div>
        <div class="field">
          <label class="field-label" for="starts_at">Starts at</label>
          <input class="input <?= has_error('starts_at') ? 'has-error' : '' ?>" type="datetime-local"
                 id="starts_at" name="starts_at" value="<?= old('starts_at', date('Y-m-d\TH:i', strtotime('+7 days 19:00'))) ?>" required>
          <?= field_error('starts_at') ?>
        </div>
        <div class="field">
          <label class="field-label" for="location">Location</label>
          <input class="input" id="location" name="location" value="<?= old('location') ?>"
                 maxlength="190" placeholder="Where is it happening?">
        </div>
        <div class="field">
          <label class="field-label" for="description">Description</label>
          <textarea class="textarea" id="description" name="description" rows="5"
                    maxlength="4000" placeholder="What should people know?"><?= old('description') ?></textarea>
        </div>
        <div class="field">
          <label class="field-label" for="cover">Cover photo (optional)</label>
          <input class="input" type="file" id="cover" name="cover" accept="image/*" data-file-preview="#event-cover-preview">
          <div id="event-cover-preview" class="hidden mt-8"></div>
        </div>
        <button class="btn btn-primary btn-lg" type="submit">Create event</button>
      </form>
    </div>
  </div>
</div>
