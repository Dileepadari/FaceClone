<div class="layout-narrow" style="max-width:700px">
  <div class="card">
    <div class="card-body">
      <h1 class="page-title">Create a listing</h1>
      <form method="post" action="/marketplace" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field-label" for="title">What are you selling?</label>
          <input class="input <?= has_error('title') ? 'has-error' : '' ?>" id="title" name="title"
                 value="<?= old('title') ?>" maxlength="150" required autofocus>
          <?= field_error('title') ?>
        </div>
        <div class="field-row">
          <div class="field">
            <label class="field-label" for="price">Price (USD)</label>
            <input class="input <?= has_error('price') ? 'has-error' : '' ?>" type="number" step="0.01" min="0"
                   id="price" name="price" value="<?= old('price', '0') ?>" required>
            <p class="field-hint">Enter 0 to list it as free.</p>
            <?= field_error('price') ?>
          </div>
          <div class="field">
            <label class="field-label" for="category">Category</label>
            <select class="select" id="category" name="category">
              <?php foreach ($categories as $key => $label): ?>
                <option value="<?= e($key) ?>"><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="field">
          <label class="field-label" for="location">Location</label>
          <input class="input" id="location" name="location" value="<?= old('location') ?>" maxlength="120">
        </div>
        <div class="field">
          <label class="field-label" for="description">Description</label>
          <textarea class="textarea" id="description" name="description" rows="5" maxlength="4000"
                    placeholder="Condition, age, why you are selling"><?= old('description') ?></textarea>
        </div>
        <div class="field">
          <label class="field-label" for="image">Photo</label>
          <input class="input" type="file" id="image" name="image" accept="image/*" data-file-preview="#listing-preview">
          <div id="listing-preview" class="hidden mt-8"></div>
        </div>
        <button class="btn btn-primary btn-lg" type="submit">Publish listing</button>
      </form>
    </div>
  </div>
</div>
