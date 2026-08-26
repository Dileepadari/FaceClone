<?php /** @var array $post */ ?>
<div class="modal-backdrop" id="edit-post-<?= (int) $post['id'] ?>" role="dialog" aria-modal="true" aria-label="Edit post">
  <div class="modal">
    <form method="post" action="/posts/<?= (int) $post['id'] ?>/update">
      <?= csrf_field() ?>
      <div class="modal-head">
        <h2>Edit post</h2>
        <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close', 20) ?></button>
      </div>
      <div class="modal-body">
        <textarea class="textarea" name="content" rows="5" data-autofocus
                  aria-label="Post text"><?= e($post['content']) ?></textarea>
        <div class="field-row mt-12">
          <input class="input" type="text" name="feeling" placeholder="Feeling or activity"
                 value="<?= e($post['feeling']) ?>" maxlength="60">
          <input class="input" type="text" name="location" placeholder="Location"
                 value="<?= e($post['location']) ?>" maxlength="120">
        </div>
        <?php if (empty($post['group_id'])): ?>
        <div class="field mt-12">
          <label class="field-label" for="edit-privacy-<?= (int) $post['id'] ?>">Audience</label>
          <select class="select" id="edit-privacy-<?= (int) $post['id'] ?>" name="privacy">
            <?php foreach (['friends' => 'Friends', 'public' => 'Public', 'only_me' => 'Only me'] as $v => $label): ?>
              <option value="<?= $v ?>" <?= $post['privacy'] === $v ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <p class="field-hint">Attached photos and videos cannot be changed after posting.</p>
      </div>
      <div class="modal-foot">
        <button class="btn btn-primary btn-block" type="submit">Save changes</button>
      </div>
    </form>
  </div>
</div>
